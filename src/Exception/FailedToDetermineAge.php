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

namespace MWPD\ContentFreshness\Exception;

use RuntimeException;

/**
 * Exception for a post whose age cannot be measured.
 */
final class FailedToDetermineAge extends RuntimeException implements ContentFreshnessException {

	/**
	 * Create a new instance of the exception for a post that has no
	 * last-updated date.
	 *
	 * @param int $post_id ID of the post.
	 */
	public static function for_post( int $post_id ): self {
		return new self(
			\sprintf(
				'WordPress cannot tell when post %d was last updated, so its age is unknown.',
				$post_id
			)
		);
	}
}
