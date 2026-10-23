<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Digest;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use MWPD\ContentFreshness\Digest\WordPressStalePosts;
use MWPD\ContentFreshness\Tests\Unit\TestCase;
use stdClass;
use WP_Post;

/**
 * @covers \MWPD\ContentFreshness\Digest\WordPressStalePosts
 */
final class WordPressStalePostsTest extends TestCase {

	public function test_it_asks_for_the_oldest_published_posts_before_the_moment_in_utc(): void {
		$post = new WP_Post( new stdClass() );
		Functions\expect( 'get_posts' )
			->once()
			->with(
				\Mockery::on(
					static fn( array $query ): bool => 'post' === $query['post_type']
						&& 'publish' === $query['post_status']
						&& 'modified' === $query['orderby']
						&& 'ASC' === $query['order']
						&& 20 === $query['posts_per_page']
						&& 40 === $query['offset']
						&& true === $query['no_found_rows']
						&& [
							[
								'column'    => 'post_modified_gmt',
								'before'    => [
									'year'   => 2025,
									'month'  => 10,
									'day'    => 23,
									'hour'   => 10,
									'minute' => 0,
									'second' => 0,
								],
								'inclusive' => false,
							],
						] === $query['date_query']
				)
			)
			->andReturn( [ $post ] );

		$posts = ( new WordPressStalePosts() )->last_updated_before(
			new DateTimeImmutable( '2025-10-23 12:00:00 +02:00' ),
			20,
			40
		);

		$this->assertSame( [ $post ], $posts );
	}

	public function test_it_returns_only_posts(): void {
		$post = new WP_Post( new stdClass() );
		Functions\when( 'get_posts' )->justReturn( [ 42, $post, null ] );

		$this->assertSame(
			[ $post ],
			( new WordPressStalePosts() )->last_updated_before( new DateTimeImmutable(), 20, 0 )
		);
	}
}
