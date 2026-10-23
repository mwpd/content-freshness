<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Notice;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Exception\FailedToFindPost;
use MWPD\ContentFreshness\Infrastructure\View\TemplatedViewFactory;
use MWPD\ContentFreshness\Notice\OutdatedPostNotice;
use MWPD\ContentFreshness\Tests\Fixture\FixedDebugMode;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FixedFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Fixture\Notice\FixedMainLoop;
use MWPD\ContentFreshness\Tests\Unit\TestCase;
use stdClass;
use WP_Post;

/**
 * The notice on its own, with test doubles for everything it asks, rendering
 * the views/outdated-post-notice template.
 *
 * @covers \MWPD\ContentFreshness\Notice\OutdatedPostNotice
 */
final class OutdatedPostNoticeTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\expect( 'human_time_diff' )
			->zeroOrMoreTimes()
			->with( strtotime( FixedFreshnessPolicy::LAST_UPDATED ), strtotime( FixedFreshnessPolicy::MEASURED_AT ) )
			->andReturn( '3 years' );
	}

	public function test_it_is_needed_on_a_single_post_once_the_query_has_run(): void {
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( true );

		$this->assertTrue( OutdatedPostNotice::is_needed() );
		$this->assertSame( 'wp', OutdatedPostNotice::get_registration_action() );
	}

	public function test_it_filters_the_content(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), $this->main_loop() );

		$notice->register();

		$this->assertSame( 10, has_filter( 'the_content', [ $notice, 'prepend_notice' ] ) );
	}

	public function test_it_prepends_the_notice_to_an_outdated_post(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), $this->main_loop() );

		$content = $notice->prepend_notice( '<p>Content.</p>' );

		$this->assertIsString( $content );
		$this->assertStringContainsString( '<p class="mwpd-content-freshness-notice" role="note">', $content );
		$this->assertStringContainsString(
			'This post was last updated 3 years ago; some information may be out of date.',
			$content
		);
		$this->assertStringEndsWith( '<p>Content.</p>', $content );
	}

	public function test_it_prepends_the_notice_once_to_content_filtered_twice(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), $this->main_loop() );

		$once  = $notice->prepend_notice( '<p>Content.</p>' );
		$twice = $notice->prepend_notice( $once );

		$this->assertSame( $once, $twice );
	}

	public function test_it_leaves_a_fresh_post_alone(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( false ), $this->main_loop() );

		$this->assertSame( '<p>Content.</p>', $notice->prepend_notice( '<p>Content.</p>' ) );
	}

	public function test_it_leaves_other_posts_on_the_page_alone(): void {
		// A related-posts loop, or an excerpt built for a meta tag.
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), new FixedMainLoop( false, $this->post() ) );

		$this->assertSame( '<p>Content.</p>', $notice->prepend_notice( '<p>Content.</p>' ) );
	}

	public function test_it_passes_on_content_that_is_not_a_string(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), $this->main_loop() );

		$this->assertNull( $notice->prepend_notice( null ) );
	}

	public function test_a_failure_leaves_the_content_as_it_was_without_debug_mode(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), new FixedMainLoop( true, null ), false );

		$this->assertSame( '<p>Content.</p>', $notice->prepend_notice( '<p>Content.</p>' ) );
	}

	public function test_a_failure_surfaces_in_debug_mode(): void {
		$notice = $this->make_notice( new FixedFreshnessPolicy( true ), new FixedMainLoop( true, null ), true );

		$this->expectException( FailedToFindPost::class );

		$notice->prepend_notice( '<p>Content.</p>' );
	}

	private function make_notice(
		FixedFreshnessPolicy $policy,
		FixedMainLoop $main_loop,
		bool $debug_mode = false
	): OutdatedPostNotice {
		return new OutdatedPostNotice(
			$policy,
			$main_loop,
			new TemplatedViewFactory( [ dirname( __DIR__, 4 ) ] ),
			new FixedDebugMode( $debug_mode )
		);
	}

	private function main_loop(): FixedMainLoop {
		return new FixedMainLoop( true, $this->post() );
	}

	private function post(): WP_Post {
		$fields     = new stdClass();
		$fields->ID = 42;

		return new WP_Post( $fields );
	}
}
