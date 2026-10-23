<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\LifecycleScope;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\Multisite;
use WP_Error;

/**
 * The plugin's lifecycle, through the real composition root, on a single site
 * and on a multisite installation with two networks: activation schedules the
 * digest in every site it reaches, deactivation clears it, and uninstalling
 * removes the setting and any digest left from every site.
 *
 * @covers \MWPD\ContentFreshness\Digest\StaleContentDigest
 * @covers \MWPD\ContentFreshness\Settings\FreshnessSetting
 */
final class LifecycleTest extends TestCase {

	/**
	 * Scheduled digests, by site.
	 *
	 * @var array<int,int>
	 */
	private array $digests = [];

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
			fn() => $this->digests[ \get_current_blog_id() ] ?? false
		);
		Functions\when( 'wp_schedule_event' )->alias(
			function ( int $timestamp ): bool {
				$this->digests[ \get_current_blog_id() ] = $timestamp;

				return true;
			}
		);
		Functions\when( 'wp_clear_scheduled_hook' )->alias(
			function (): int {
				$cleared = isset( $this->digests[ \get_current_blog_id() ] ) ? 1 : 0;
				unset( $this->digests[ \get_current_blog_id() ] );

				return $cleared;
			}
		);
	}

	public function test_activating_on_a_single_site_schedules_its_digest_and_deactivating_clears_it(): void {
		$this->install( Multisite::single_site() );
		$plugin = new ContentFreshnessPlugin();

		$plugin->activate_in( LifecycleScope::current_site() );
		$this->assertSame( [ 1 => strtotime( '2026-10-30 12:00:00 UTC' ) ], $this->digests );

		$plugin->deactivate_in( LifecycleScope::current_site() );
		$this->assertSame( [], $this->digests );
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

		$this->assertSame( [ 1, 2 ], array_keys( $this->digests ) );
		$this->assertSame( 1, \get_current_blog_id() );
	}

	public function test_uninstalling_on_a_single_site_removes_the_setting_and_the_digest(): void {
		$this->install( Multisite::single_site() );
		$this->installation->set_option( 1, OptionFreshnessPolicy::OPTION_NAME, 90 );
		$this->digests[1] = 1700000000;

		( new ContentFreshnessPlugin() )->uninstall();

		$this->assertFalse( $this->installation->has_option( 1, OptionFreshnessPolicy::OPTION_NAME ) );
		$this->assertSame( [], $this->digests );
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
		$this->digests = [
			2 => 1700000000,
			4 => 1700000000,
		];

		( new ContentFreshnessPlugin() )->uninstall();

		foreach ( [ 1, 2, 3, 4 ] as $site ) {
			$this->assertFalse(
				$this->installation->has_option( $site, OptionFreshnessPolicy::OPTION_NAME ),
				"Site {$site}"
			);
		}
		$this->assertSame( [], $this->digests );
		$this->assertSame( 1, \get_current_blog_id() );
	}

	private function install( Multisite $installation ): void {
		$this->installation = $installation;
		$installation->install();
	}
}
