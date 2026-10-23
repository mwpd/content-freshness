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
 * Exception for a weekly digest WordPress cannot schedule or unschedule.
 */
final class FailedToScheduleDigest extends RuntimeException implements ContentFreshnessException {

	/**
	 * Create a new instance of the exception for an event that could not be
	 * scheduled.
	 *
	 * @param string $reason Reason WordPress gave.
	 */
	public static function when_scheduling( string $reason ): self {
		return new self( \sprintf( 'The weekly digest could not be scheduled: %s', $reason ) );
	}

	/**
	 * Create a new instance of the exception for an event that could not be
	 * cleared.
	 *
	 * @param string $reason Reason WordPress gave.
	 */
	public static function when_clearing( string $reason ): self {
		return new self( \sprintf( 'The weekly digest could not be unscheduled: %s', $reason ) );
	}
}
