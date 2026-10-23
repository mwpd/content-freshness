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
 * Finds the published posts that have gone longest without an update.
 */
interface StalePosts {

	/**
	 * Get the published posts last updated before a moment, oldest first.
	 *
	 * @param DateTimeImmutable $moment Moment the posts were last updated before.
	 * @param int               $limit  Largest number of posts to return.
	 *
	 * @return list<WP_Post> Posts, the one updated longest ago first.
	 */
	public function last_updated_before( DateTimeImmutable $moment, int $limit ): array;
}
