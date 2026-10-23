<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2019 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Exception;

use RuntimeException;

/**
 * Exception for a post that WordPress cannot provide.
 */
final class FailedToFindPost extends RuntimeException implements ContentFreshnessException {

	/**
	 * Create a new instance of the exception for a main query that did not
	 * ask for a post.
	 */
	public static function for_main_query(): self {
		return new self(
			'The main query did not ask for a post. A service that needs the queried post has to wait for the main'
			. ' query, for example by implementing Delayed with the "wp" action, be needed only on singular views,'
			. ' and ask for the post only while the main loop renders it.'
		);
	}
}
