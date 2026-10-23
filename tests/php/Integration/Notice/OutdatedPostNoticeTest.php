<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration\Notice;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Notice\OutdatedPostNotice;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FixedFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Integration\TestCase;
use stdClass;
use WP_Post;

/**
 * The outdated post notice on a single post, built from the real composition
 * root, rendering the views/outdated-post-notice template.
 */
final class OutdatedPostNoticeTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'did_action' )->alias( static fn( string $action ): bool => $action === 'wp' );
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nonexistent/child-theme' );
		Functions\when( 'get_template_directory' )->justReturn( '/nonexistent/parent-theme' );
		Functions\when( 'human_time_diff' )->justReturn( '3 years' );

		// The main loop is rendering post 42, the queried post.
		Functions\when( 'in_the_loop' )->justReturn( true );
		Functions\when( 'is_main_query' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 42 );
		Functions\when( 'get_queried_object_id' )->justReturn( 42 );
		Functions\when( 'get_queried_object' )->justReturn( $this->post( '2023-10-23 12:00:00' ) );
		Functions\when( 'get_post_datetime' )->alias(
			static fn( WP_Post $post ): DateTimeImmutable => new DateTimeImmutable(
				$post->post_modified_gmt,
				new DateTimeZone( 'UTC' )
			)
		);
		Functions\when( 'current_datetime' )->justReturn( new DateTimeImmutable( '2026-10-23 12:00:00 UTC' ) );
	}

	public function test_it_warns_readers_of_a_post_older_than_the_maximum_age(): void {
		Functions\when( 'get_option' )->justReturn( '365' );

		$content = $this->registered_notice()->prepend_notice( '<p>Content.</p>' );

		$this->assertIsString( $content );
		$this->assertStringContainsString(
			'This post was last updated 3 years ago; some information may be out of date.',
			$content
		);
		$this->assertStringEndsWith( '<p>Content.</p>', $content );
	}

	public function test_it_leaves_a_post_within_the_maximum_age_alone(): void {
		Functions\when( 'get_option' )->justReturn( '3650' );

		$this->assertSame( '<p>Content.</p>', $this->registered_notice()->prepend_notice( '<p>Content.</p>' ) );
	}

	/**
	 * The services depend on the FreshnessPolicy interface. Rebinding it is
	 * all it takes to change the rule: no service changes, and the option is
	 * never read.
	 */
	public function test_a_rebound_policy_changes_the_rule_without_touching_the_service(): void {
		Functions\expect( 'get_option' )->never();
		Filters\expectApplied( 'mwpd.content_freshness.bindings' )
			->once()
			->andReturnUsing(
				static function ( array $bindings ): array {
					$bindings[ FreshnessPolicy::class ] = FixedFreshnessPolicy::class;

					return $bindings;
				}
			);
		// The fixed policy says "fresh" unless told otherwise.
		Filters\expectApplied( 'mwpd.content_freshness.arguments' )
			->once()
			->andReturnUsing(
				static function ( array $arguments ): array {
					$arguments[ FixedFreshnessPolicy::class ] = [ 'outdated' => false ];

					return $arguments;
				}
			);

		$this->assertSame( '<p>Content.</p>', $this->registered_notice()->prepend_notice( '<p>Content.</p>' ) );
	}

	private function registered_notice(): OutdatedPostNotice {
		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$service = $plugin->get_container()->get( ContentFreshnessPlugin::OUTDATED_POST_NOTICE_ID );
		$this->assertInstanceOf( OutdatedPostNotice::class, $service );
		$this->assertSame( 10, has_filter( 'the_content', [ $service, 'prepend_notice' ] ) );

		return $service;
	}

	private function post( string $modified_gmt ): WP_Post {
		$fields                    = new stdClass();
		$fields->ID                = 42;
		$fields->post_modified_gmt = $modified_gmt;

		return new WP_Post( $fields );
	}
}
