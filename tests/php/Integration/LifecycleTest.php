<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Digest\StaleContentDigest;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\LifecycleScope;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\Multisite;
use WP_Error;
use WP_Site;

/**
 * The plugin's lifecycle, through the real composition root, on a single site
 * and on a multisite installation with two networks: activation schedules the
 * digest in every site it reaches, a new site of a network gets its own,
 * deactivation clears it, and uninstalling removes the setting and any digest
 * left from every site.
 *
 * @covers \MWPD\ContentFreshness\Digest\StaleContentDigest
 * @covers \MWPD\ContentFreshness\Settings\FreshnessSetting
 */
final class LifecycleTest extends TestCase {

	private const NEXT_WEEK = '2026-10-30 12:00:00 UTC';

	/**
	 * Scheduled cron events, by site, as hook, recurrence, arguments and time.
	 *
	 * @var array<int,list<array{string,string,array<mixed>,int}>>
	 */
	private array $events = [];

	/**
	 * Number of events scheduled.
	 */
	private int $scheduled = 0;

	private Multisite $installation;

	protected function set_up() {
		parent::set_up();

		Functions\when( 'flush_rewrite_rules' )->justReturn( null );
		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof WP_Error );
		Functions\when( 'current_datetime' )->justReturn( new DateTimeImmutable( '2026-10-23 12:00:00 UTC' ) );

		// WP-Cron keeps its events per site, like any other option.
		Functions\when( 'wp_next_scheduled' )->alias(
			function ( string $hook, array $args = [] ) {
				foreach ( $this->events[ \get_current_blog_id() ] ?? [] as [ $event_hook, , $event_args, $time ] ) {
					if ( $event_hook === $hook && $event_args === $args ) {
						return $time;
					}
				}

				return false;
			}
		);
		Functions\when( 'wp_schedule_event' )->alias(
			function ( int $timestamp, string $recurrence, string $hook, array $args = [] ): bool {
				$this->events[ \get_current_blog_id() ][] = [ $hook, $recurrence, $args, $timestamp ];
				++$this->scheduled;

				return true;
			}
		);
		Functions\when( 'wp_clear_scheduled_hook' )->alias(
			function ( string $hook, array $args = [] ): int {
				$site   = \get_current_blog_id();
				$before = $this->events[ $site ] ?? [];
				$after  = array_values(
					array_filter(
						$before,
						static fn( array $event ): bool => $event[0] !== $hook || $event[2] !== $args
					)
				);

				$this->events[ $site ] = $after;
				if ( [] === $after ) {
					unset( $this->events[ $site ] );
				}

				return count( $before ) - count( $after );
			}
		);
	}

	public function test_activating_on_a_single_site_schedules_its_digest_and_deactivating_clears_it(): void {
		$this->install( Multisite::single_site() );
		$plugin = new ContentFreshnessPlugin();

		$plugin->activate_in( LifecycleScope::current_site() );
		$plugin->activate_in( LifecycleScope::current_site() );
		$this->assertSame( [ 1 => [ $this->digest() ] ], $this->events );
		$this->assertSame( 1, $this->scheduled, 'Activating twice schedules once.' );

		$plugin->deactivate_in( LifecycleScope::current_site() );
		$this->assertSame( [], $this->events );
	}

	public function test_network_activation_schedules_a_digest_in_every_site_of_the_network(): void {
		$this->install(
			Multisite::with_networks(
				[
					1 => [ 1, 2 ],
					2 => [ 3 ],
				],
				1
			)
		);

		( new ContentFreshnessPlugin() )->activate_in( LifecycleScope::network_wide() );

		$this->assertSame(
			[
				1 => [ $this->digest() ],
				2 => [ $this->digest() ],
			],
			$this->events
		);
		$this->assertSame( 1, \get_current_blog_id() );
	}

	/**
	 * The digest's set-up for new sites is wired through the composition root:
	 * registering the services hooks wp_initialize_site, and a site created on
	 * a network where the plugin is network-active gets its digest from there.
	 */
	public function test_a_site_created_on_a_network_with_the_plugin_gets_its_own_digest(): void {
		$this->install(
			Multisite::with_networks(
				[
					1 => [ 1, 2 ],
					2 => [ 3 ],
				],
				1
			)
		);
		$this->installation->activate_for_network( 1 );
		$initialize = null;
		Actions\expectAdded( 'wp_initialize_site' )->whenHappen(
			static function ( callable $callback ) use ( &$initialize ): void {
				$initialize = $callback;
			}
		);

		( new ContentFreshnessPlugin() )->register_services();
		$this->assertIsCallable( $initialize );
		$initialize(
			new WP_Site(
				(object) [
					'blog_id' => '2',
					'site_id' => '1',
				]
			)
		);

		$this->assertSame( [ 2 => [ $this->digest() ] ], $this->events );
		$this->assertSame( 1, \get_current_blog_id() );
	}

	/**
	 * A network deactivation leaves a site that keeps the plugin activated on
	 * its own with its digest.
	 */
	public function test_network_deactivation_keeps_the_digest_of_a_site_with_its_own_activation(): void {
		$this->install( Multisite::with_networks( [ 1 => [ 1, 2 ] ], 1 ) );
		$this->installation->activate_on_site( 2 );
		$this->events = [
			1 => [ $this->digest() ],
			2 => [ $this->digest() ],
		];

		( new ContentFreshnessPlugin() )->deactivate_in( LifecycleScope::network_wide() );

		$this->assertSame( [ 2 => [ $this->digest() ] ], $this->events );
	}

	public function test_uninstalling_on_a_single_site_removes_the_setting_and_the_digest(): void {
		$this->install( Multisite::single_site() );
		$this->installation->set_option( 1, OptionFreshnessPolicy::OPTION_NAME, 90 );
		$this->events[1] = [ $this->digest() ];

		( new ContentFreshnessPlugin() )->uninstall();

		$this->assertFalse( $this->installation->has_option( 1, OptionFreshnessPolicy::OPTION_NAME ) );
		$this->assertSame( [], $this->events );
	}

	/**
	 * Uninstalling reaches every site of every network, whether or not the
	 * setting or a digest is there, and returns to the site it started in.
	 */
	public function test_uninstalling_on_multisite_cleans_up_every_site_of_every_network(): void {
		$this->install(
			Multisite::with_networks(
				[
					1 => [ 1, 2 ],
					2 => [ 3, 4 ],
				],
				1
			)
		);
		foreach ( [ 1, 2, 3 ] as $site ) {
			$this->installation->set_option( $site, OptionFreshnessPolicy::OPTION_NAME, 90 );
		}
		$this->events = [
			2 => [ $this->digest() ],
			4 => [ $this->digest() ],
		];

		( new ContentFreshnessPlugin() )->uninstall();

		foreach ( [ 1, 2, 3, 4 ] as $site ) {
			$this->assertFalse(
				$this->installation->has_option( $site, OptionFreshnessPolicy::OPTION_NAME ),
				"Site {$site}"
			);
		}
		$this->assertSame( [], $this->events );
		$this->assertSame( 1, \get_current_blog_id() );
	}

	private function install( Multisite $installation ): void {
		$this->installation = $installation;
		$installation->install();

		// The installation records the plugin under its usual directory,
		// whatever the directory of this checkout is called.
		Functions\when( 'plugin_basename' )->justReturn( Multisite::BASENAME );
	}

	/**
	 * The weekly digest event, as the plugin schedules it.
	 *
	 * @return array{string,string,array<mixed>,int}
	 */
	private function digest(): array {
		return [ StaleContentDigest::CRON_HOOK, 'weekly', [], (int) strtotime( self::NEXT_WEEK ) ];
	}
}
