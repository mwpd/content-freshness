<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Digest;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Digest\StaleContentDigest;
use MWPD\ContentFreshness\Digest\StalePosts;
use MWPD\ContentFreshness\Exception\FailedToScheduleDigest;
use MWPD\ContentFreshness\Exception\FailedToSendDigest;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\LifecycleScope;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\NetworkId;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteContext;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteId;
use MWPD\ContentFreshness\Infrastructure\View\TemplatedViewFactory;
use MWPD\ContentFreshness\Tests\Fixture\Digest\FixedStalePosts;
use MWPD\ContentFreshness\Tests\Fixture\FixedDebugMode;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FailingFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FixedFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FrozenClock;
use MWPD\ContentFreshness\Tests\Unit\TestCase;
use stdClass;
use WP_Error;
use WP_Post;
use WP_User;

/**
 * The digest on its own, with test doubles for the policy, the stale posts and
 * the clock, rendering the views/stale-content-digest template.
 *
 * @covers \MWPD\ContentFreshness\Digest\StaleContentDigest
 */
final class StaleContentDigestTest extends TestCase {

	/**
	 * Emails sent, as recipient, subject, body and headers.
	 *
	 * @var list<array{string,string,string,array<mixed>}>
	 */
	private array $sent = [];

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof WP_Error );
		Functions\when( 'human_time_diff' )->justReturn( '3 years' );
		Functions\when( 'get_the_title' )->alias( static fn( WP_Post $post ): string => $post->post_title );
		Functions\when( 'admin_url' )->alias(
			static fn( string $path ): string => 'https://example.com/wp-admin/' . $path
		);
		Functions\when( 'add_query_arg' )->alias(
			static fn( array $args, string $url ): string => $url . '?' . http_build_query( $args )
		);
		Functions\when( 'get_option' )->alias(
			static fn( string $name ) => 'blogname' === $name ? 'Tom &amp; Jerry' : false
		);
		Functions\when( 'wp_specialchars_decode' )->alias(
			static fn( string $text ): string => htmlspecialchars_decode( $text, ENT_QUOTES )
		);
		Functions\when( 'is_email' )->alias(
			static fn( string $email ) => false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ? false : $email
		);
		Functions\when( 'get_users' )->justReturn(
			[
				$this->user( 1, 'editor@example.com' ),
				$this->user( 2, 'admin@example.com' ),
				$this->user( 3, 'not an address' ),
				$this->user( 1, 'editor@example.com' ),
				// Has the role, but the capability was taken away from them.
				$this->user( 4, 'denied@example.com' ),
			]
		);
		Functions\when( 'user_can' )->alias(
			static fn( WP_User $user, string $capability ): bool => 'edit_others_posts' === $capability
				&& 4 !== $user->ID
		);
		Functions\when( 'wp_mail' )->alias(
			function ( string $to, string $subject, string $body, array $headers ): bool {
				$this->sent[] = [ $to, $subject, $body, $headers ];

				return true;
			}
		);
	}

	public function test_it_hooks_the_cron_event(): void {
		$digest = $this->make_digest( new FixedStalePosts( [] ) );

		$digest->register();

		$this->assertSame( 10, has_action( StaleContentDigest::CRON_HOOK, [ $digest, 'send' ] ) );
	}

	public function test_activation_schedules_the_first_digest_of_the_site_a_week_out(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\expect( 'wp_schedule_event' )
			->once()
			->with( strtotime( '2026-10-30 12:00:00 UTC' ), 'weekly', StaleContentDigest::CRON_HOOK, [], true )
			->andReturn( true );

		$this->make_digest( new FixedStalePosts( [] ) )->activate_site( $this->site() );
	}

	public function test_activation_keeps_a_digest_that_is_scheduled_already(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( 1700000000 );
		Functions\expect( 'wp_schedule_event' )->never();

		$this->make_digest( new FixedStalePosts( [] ) )->activate_site( $this->site() );
	}

	public function test_activation_fails_loudly_when_wordpress_cannot_schedule(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\when( 'wp_schedule_event' )->justReturn(
			new WP_Error( 'invalid_schedule', 'Event schedule does not exist.' )
		);

		$this->expectException( FailedToScheduleDigest::class );
		$this->expectExceptionMessage( 'The weekly digest could not be scheduled: Event schedule does not exist.' );

		$this->make_digest( new FixedStalePosts( [] ) )->activate_site( $this->site() );
	}

	public function test_deactivation_clears_the_schedule(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( StaleContentDigest::CRON_HOOK, [], true )
			->andReturn( 1 );

		$this->make_digest( new FixedStalePosts( [] ) )->deactivate_site( $this->site() );
	}

	public function test_a_new_site_on_a_network_with_the_plugin_gets_its_own_digest(): void {
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\expect( 'wp_schedule_event' )
			->once()
			->with( strtotime( '2026-10-30 12:00:00 UTC' ), 'weekly', StaleContentDigest::CRON_HOOK, [], true )
			->andReturn( true );

		$this->make_digest( new FixedStalePosts( [] ) )->initialize_site( $this->site() );
	}

	public function test_uninstalling_clears_a_digest_left_in_the_site(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( StaleContentDigest::CRON_HOOK, [], true )
			->andReturn( 0 );

		$this->make_digest( new FixedStalePosts( [] ) )->uninstall_site( $this->site() );
	}

	public function test_deactivation_fails_loudly_when_wordpress_cannot_clear(): void {
		Functions\when( 'wp_clear_scheduled_hook' )->justReturn(
			new WP_Error( 'could_not_set', 'The cron event list could not be saved.' )
		);

		$this->expectException( FailedToScheduleDigest::class );
		$this->expectExceptionMessage(
			'The weekly digest could not be unscheduled: The cron event list could not be saved.'
		);

		$this->make_digest( new FixedStalePosts( [] ) )->deactivate_site( $this->site() );
	}

	public function test_it_emails_each_editor_the_stale_posts(): void {
		$stale_posts = new FixedStalePosts( [ $this->post( 7, 'Old <b>news</b>' ), $this->post( 8, '' ) ] );

		$this->make_digest( $stale_posts )->send();

		$this->assertNotNull( $stale_posts->moment );
		$this->assertSame( '2025-10-23 12:00:00', $stale_posts->moment->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( StaleContentDigest::SIZE, $stale_posts->limit );
		$this->assertSame( [ 'editor@example.com', 'admin@example.com' ], array_column( $this->sent, 0 ) );

		[ , $subject, $body, $headers ] = $this->sent[0];
		$this->assertSame( '[Tom & Jerry] Posts that need a refresh', $subject );
		$this->assertSame( [ 'Content-Type: text/html; charset=UTF-8' ], $headers );
		$this->assertStringContainsString(
			'<a href="https://example.com/wp-admin/post.php?post=7&action=edit">Old &lt;b&gt;news&lt;/b&gt;</a>',
			$body
		);
		$this->assertStringContainsString( '(no title)', $body );
		$this->assertStringContainsString( 'last updated 3 years ago', $body );
		$this->assertStringContainsString( 'https://example.com/wp-admin/edit.php?orderby=modified&order=asc', $body );
	}

	public function test_it_follows_a_policy_that_leaves_old_posts_out(): void {
		$posts       = array_map(
			fn( int $id ): WP_Post => $this->post( $id, "Post {$id}" ),
			range( 1, 30 )
		);
		$stale_posts = new FixedStalePosts( $posts );
		// An evergreen rule: the oldest 15 posts never count as outdated.
		$policy = new FixedFreshnessPolicy( true, range( 1, 15 ) );

		$this->make_digest( $stale_posts, $policy )->send();

		[ , , $body ] = $this->sent[0];
		$this->assertStringNotContainsString( '>Post 15<', $body );
		$this->assertStringContainsString( '>Post 16<', $body );
		$this->assertStringContainsString( '>Post 30<', $body );
		$this->assertSame( [ 0, 20 ], $stale_posts->offsets );
	}

	public function test_it_lists_at_most_a_digest_full_of_posts(): void {
		$posts       = array_map(
			fn( int $id ): WP_Post => $this->post( $id, "Post {$id}" ),
			range( 1, 30 )
		);
		$stale_posts = new FixedStalePosts( $posts );

		$this->make_digest( $stale_posts )->send();

		[ , , $body ] = $this->sent[0];
		$this->assertSame( StaleContentDigest::SIZE, substr_count( $body, '<li>' ) );
		$this->assertSame( [ 0 ], $stale_posts->offsets );
	}

	public function test_it_sends_nothing_when_the_policy_leaves_every_candidate_out(): void {
		$this->make_digest(
			new FixedStalePosts( [ $this->post( 7, 'Old news' ) ] ),
			new FixedFreshnessPolicy( true, [ 7 ] )
		)->send();

		$this->assertSame( [], $this->sent );
	}

	public function test_it_sends_nothing_when_nothing_is_stale(): void {
		$this->make_digest( new FixedStalePosts( [] ) )->send();

		$this->assertSame( [], $this->sent );
	}

	public function test_a_failure_ends_the_run_quietly_without_debug_mode(): void {
		$digest = $this->make_digest(
			new FixedStalePosts( [ $this->post( 7, 'Old news' ) ] ),
			new FailingFreshnessPolicy()
		);

		$digest->send();

		$this->assertSame( [], $this->sent );
	}

	public function test_a_failure_surfaces_in_debug_mode(): void {
		$digest = $this->make_digest(
			new FixedStalePosts( [ $this->post( 7, 'Old news' ) ] ),
			new FailingFreshnessPolicy(),
			true
		);

		$this->expectException( InvalidMaxAge::class );

		$digest->send();
	}

	public function test_it_tries_every_editor_and_reports_the_ones_it_could_not_reach(): void {
		Functions\when( 'wp_mail' )->alias(
			function ( string $to ): bool {
				$this->sent[] = [ $to, '', '', [] ];

				return 'admin@example.com' === $to;
			}
		);
		$digest = $this->make_digest( new FixedStalePosts( [ $this->post( 7, 'Old news' ) ] ), null, true );

		try {
			$digest->send();
			$this->fail( 'Sending should have failed.' );
		} catch ( FailedToSendDigest $exception ) {
			$this->assertStringContainsString( 'could not be sent to editor@example.com.', $exception->getMessage() );
		}

		$this->assertSame( [ 'editor@example.com', 'admin@example.com' ], array_column( $this->sent, 0 ) );
	}

	private function make_digest(
		StalePosts $stale_posts,
		?FreshnessPolicy $policy = null,
		bool $debug_mode = false
	): StaleContentDigest {
		return new StaleContentDigest(
			$policy ?? new FixedFreshnessPolicy( true ),
			$stale_posts,
			new FrozenClock( '2026-10-23 12:00:00 UTC' ),
			new TemplatedViewFactory( [ dirname( __DIR__, 4 ) ] ),
			new FixedDebugMode( $debug_mode )
		);
	}

	private function site(): SiteContext {
		return SiteContext::for_site( SiteId::from_int( 1 ), NetworkId::from_int( 1 ), LifecycleScope::current_site() );
	}

	private function post( int $id, string $title ): WP_Post {
		$fields             = new stdClass();
		$fields->ID         = $id;
		$fields->post_title = $title;

		return new WP_Post( $fields );
	}

	private function user( int $id, string $email ): WP_User {
		$user             = new WP_User();
		$user->ID         = $id;
		$user->user_email = $email;

		return $user;
	}
}
