<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\PostsList;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Exception\FailedToDetermineAge;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\View\TemplatedViewFactory;
use MWPD\ContentFreshness\PostsList\PostAgeColumn;
use MWPD\ContentFreshness\Tests\Fixture\FixedDebugMode;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FailingFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FixedFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Unit\TestCase;
use stdClass;
use WP_Post;
use WP_Screen;

/**
 * The column on its own, with a fixed policy, rendering the
 * views/post-age-column template.
 *
 * @covers \MWPD\ContentFreshness\PostsList\PostAgeColumn
 */
final class PostAgeColumnTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'human_time_diff' )->justReturn( '3 years' );
		Functions\when( 'get_post' )->alias(
			static function ( int $post_id ): ?WP_Post {
				if ( 42 !== $post_id ) {
					return null;
				}

				$fields     = new stdClass();
				$fields->ID = 42;

				return new WP_Post( $fields );
			}
		);
	}

	/**
	 * @return array<string,array{string|null,bool}>
	 */
	public static function data_screens(): array {
		return [
			'posts list'  => [ 'edit-post', true ],
			'pages list'  => [ 'edit-page', false ],
			'post editor' => [ 'post', false ],
			'no screen'   => [ null, false ],
		];
	}

	/**
	 * @dataProvider data_screens
	 */
	public function test_it_is_needed_on_the_posts_list_only( ?string $screen_id, bool $expected ): void {
		$screen = null;
		if ( null !== $screen_id ) {
			$screen     = new WP_Screen();
			$screen->id = $screen_id;
		}
		Functions\when( 'get_current_screen' )->justReturn( $screen );

		$this->assertSame( $expected, PostAgeColumn::is_needed() );
		$this->assertSame( 'current_screen', PostAgeColumn::get_registration_action() );
	}

	public function test_it_hooks_into_the_posts_list(): void {
		$column = $this->make_column( new FixedFreshnessPolicy() );

		$column->register();

		$this->assertSame( 10, has_filter( 'manage_post_posts_columns', [ $column, 'add_column' ] ) );
		$this->assertSame( 10, has_filter( 'manage_edit-post_sortable_columns', [ $column, 'make_sortable' ] ) );
		$this->assertSame( 10, has_action( 'manage_post_posts_custom_column', [ $column, 'render_cell' ] ) );
	}

	public function test_it_adds_a_sortable_column(): void {
		$column = $this->make_column( new FixedFreshnessPolicy() );

		$this->assertSame(
			[
				'title'               => 'Title',
				PostAgeColumn::COLUMN => 'Last updated',
			],
			$column->add_column( [ 'title' => 'Title' ] )
		);
		$this->assertSame( [ PostAgeColumn::COLUMN => 'modified' ], $column->make_sortable( [] ) );
		$this->assertNull( $column->add_column( null ) );
		$this->assertNull( $column->make_sortable( null ) );
	}

	public function test_it_marks_an_outdated_post(): void {
		$cell = $this->render( $this->make_column( new FixedFreshnessPolicy( true ) ), PostAgeColumn::COLUMN, 42 );

		$this->assertStringContainsString( '<time datetime="2023-10-23T12:00:00+00:00">', $cell );
		$this->assertStringContainsString( '3 years ago', $cell );
		$this->assertStringContainsString( 'Outdated', $cell );
	}

	public function test_it_shows_the_age_of_a_fresh_post_without_the_mark(): void {
		$cell = $this->render( $this->make_column( new FixedFreshnessPolicy( false ) ), PostAgeColumn::COLUMN, '42' );

		$this->assertStringContainsString( '3 years ago', $cell );
		$this->assertStringNotContainsString( 'Outdated', $cell );
	}

	/**
	 * @return array<string,array{mixed,mixed}>
	 */
	public static function data_other_cells(): array {
		return [
			'another column'    => [ 'title', 42 ],
			'a missing post'    => [ PostAgeColumn::COLUMN, 7 ],
			'an ID that is not' => [ PostAgeColumn::COLUMN, 'abc' ],
		];
	}

	/**
	 * @dataProvider data_other_cells
	 *
	 * @param mixed $column  Column key.
	 * @param mixed $post_id Post ID.
	 */
	public function test_it_renders_nothing_for_other_cells( $column, $post_id ): void {
		$this->assertSame( '', $this->render( $this->make_column( new FixedFreshnessPolicy() ), $column, $post_id ) );
	}

	public function test_a_failure_leaves_the_cell_empty_without_debug_mode(): void {
		$cell = $this->render(
			$this->make_column( $this->failing_policy(), false ),
			PostAgeColumn::COLUMN,
			42
		);

		$this->assertSame( '', $cell );
	}

	public function test_a_failure_surfaces_in_debug_mode(): void {
		$this->expectException( FailedToDetermineAge::class );

		$this->render( $this->make_column( $this->failing_policy(), true ), PostAgeColumn::COLUMN, 42 );
	}

	private function make_column( FreshnessPolicy $policy, bool $debug_mode = false ): PostAgeColumn {
		return new PostAgeColumn(
			$policy,
			new TemplatedViewFactory( [ dirname( __DIR__, 4 ) ] ),
			new FixedDebugMode( $debug_mode )
		);
	}

	/**
	 * @param mixed $column_key Column key.
	 * @param mixed $post_id    Post ID.
	 */
	private function render( PostAgeColumn $column, $column_key, $post_id ): string {
		ob_start();
		try {
			$column->render_cell( $column_key, $post_id );
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}

	private function failing_policy(): FreshnessPolicy {
		return new FailingFreshnessPolicy();
	}
}
