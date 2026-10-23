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
 * Exception for a digest WordPress could not email to some of its recipients.
 */
final class FailedToSendDigest extends RuntimeException implements ContentFreshnessException {

	/**
	 * Create a new instance of the exception for the recipients the email
	 * could not be sent to.
	 *
	 * @param string[] $recipients Email addresses.
	 */
	public static function to_recipients( array $recipients ): self {
		return new self(
			\sprintf(
				'The stale content digest could not be sent to %s. Check that the site can send email.',
				\implode( ', ', $recipients )
			)
		);
	}
}
