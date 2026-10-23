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

namespace MWPD\ContentFreshness\Notice;

use MWPD\ContentFreshness\Exception\FailedToFindPost;
use WP_Post;

/**
 * The main loop, read from WordPress's query functions.
 *
 * This is the boundary for the loop's state: WordPress keeps it in globals,
 * and this class turns it into a yes-or-no answer and a checked WP_Post.
 */
final class WordPressMainLoop implements MainLoop {

	/**
	 * Check whether WordPress is rendering the queried post in the main loop
	 * right now.
	 *
	 * @return bool Whether the main loop is rendering the queried post.
	 */
	public function is_rendering_queried_post(): bool {
		return \in_the_loop()
			&& \is_main_query()
			&& \get_the_ID() === \get_queried_object_id();
	}

	/**
	 * Get the post the main query asked for.
	 *
	 * @throws FailedToFindPost If the main query did not ask for a post.
	 *
	 * @return WP_Post Queried post.
	 */
	public function queried_post(): WP_Post {
		$post = \get_queried_object();

		if ( ! $post instanceof WP_Post ) {
			throw FailedToFindPost::for_main_query();
		}

		return $post;
	}
}
