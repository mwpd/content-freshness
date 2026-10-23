<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Notice;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Exception\FailedToFindPost;
use MWPD\ContentFreshness\Notice\WordPressMainLoop;
use MWPD\ContentFreshness\Tests\Unit\TestCase;
use stdClass;
use WP_Post;

/**
 * @covers \MWPD\ContentFreshness\Notice\WordPressMainLoop
 */
final class WordPressMainLoopTest extends TestCase {

	/**
	 * @return array<string,array{bool,bool,bool,bool,int|false,bool}>
	 */
	public static function data_loop_states(): array {
		return [
			'queried post in the main loop' => [ true, true, false, false, 42, true ],
			'outside the loop'              => [ false, true, false, false, 42, false ],
			'in a secondary query'          => [ true, false, false, false, 42, false ],
			'in a feed'                     => [ true, true, true, false, 42, false ],
			'building an excerpt'           => [ true, true, false, true, 42, false ],
			'another post in the main loop' => [ true, true, false, false, 7, false ],
			'no post in the loop'           => [ true, true, false, false, false, false ],
		];
	}

	/**
	 * @dataProvider data_loop_states
	 *
	 * @param int|false $current_id ID of the post the loop is on.
	 */
	public function test_it_tells_whether_the_queried_post_is_rendered(
		bool $in_the_loop,
		bool $is_main_query,
		bool $is_feed,
		bool $building_excerpt,
		$current_id,
		bool $expected
	): void {
		Functions\when( 'in_the_loop' )->justReturn( $in_the_loop );
		Functions\when( 'is_main_query' )->justReturn( $is_main_query );
		Functions\when( 'is_feed' )->justReturn( $is_feed );
		Functions\when( 'doing_filter' )->alias(
			static fn( string $hook ): bool => $building_excerpt && 'get_the_excerpt' === $hook
		);
		Functions\when( 'get_the_ID' )->justReturn( $current_id );
		Functions\when( 'get_queried_object_id' )->justReturn( 42 );

		$this->assertSame( $expected, ( new WordPressMainLoop() )->is_rendering_queried_post() );
	}

	public function test_it_returns_the_queried_post(): void {
		$post = new WP_Post( new stdClass() );
		Functions\when( 'get_queried_object' )->justReturn( $post );

		$this->assertSame( $post, ( new WordPressMainLoop() )->queried_post() );
	}

	public function test_it_fails_when_the_main_query_did_not_ask_for_a_post(): void {
		// An archive queries a term, the front page of posts queries nothing.
		Functions\when( 'get_queried_object' )->justReturn( null );

		$this->expectException( FailedToFindPost::class );
		$this->expectExceptionMessage( 'The main query did not ask for a post.' );

		( new WordPressMainLoop() )->queried_post();
	}
}
