<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2026 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Digest;

use DateTimeImmutable;
use WP_Post;

/**
 * Finds stale posts with a WordPress post query.
 *
 * This is the boundary for the query: the database does the filtering and
 * sorting, and what comes back is checked to be posts before anything else
 * sees it.
 */
final class WordPressStalePosts implements StalePosts {

	/**
	 * Get the published posts last updated before a moment, oldest first.
	 *
	 * @param DateTimeImmutable $moment Moment the posts were last updated before.
	 * @param int               $limit  Largest number of posts to return.
	 *
	 * @return list<WP_Post> Posts, the one updated longest ago first.
	 */
	public function last_updated_before( DateTimeImmutable $moment, int $limit ): array {
		$posts = \get_posts(
			[
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'orderby'             => 'modified',
				'order'               => 'ASC',
				'posts_per_page'      => \max( 1, $limit ),
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'date_query'          => [
					[
						// Compare in UTC, whatever the site's timezone.
						'column'    => 'post_modified_gmt',
						'before'    => \gmdate( 'Y-m-d H:i:s', $moment->getTimestamp() ),
						'inclusive' => false,
					],
				],
			]
		);

		return \array_values(
			\array_filter(
				$posts,
				static fn( $post ): bool => $post instanceof WP_Post
			)
		);
	}
}
