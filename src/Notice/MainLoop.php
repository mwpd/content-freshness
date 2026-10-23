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
 * The main loop of a singular view, as a filter callback sees it.
 *
 * Filters such as the_content run for every post WordPress renders: the
 * queried post in the main loop, but also posts in a related-posts widget, or
 * an excerpt built for a meta tag in the head. A service that only acts on the
 * queried post asks this interface, at call time, instead of reaching into
 * WordPress's globals.
 */
interface MainLoop {

	/**
	 * Check whether WordPress is rendering the queried post in the main loop
	 * right now.
	 *
	 * @return bool Whether the main loop is rendering the queried post.
	 */
	public function is_rendering_queried_post(): bool;

	/**
	 * Get the post the main query asked for.
	 *
	 * @throws FailedToFindPost If the main query did not ask for a post.
	 *
	 * @return WP_Post Queried post.
	 */
	public function queried_post(): WP_Post;
}
