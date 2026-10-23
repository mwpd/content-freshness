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

use InvalidArgumentException;

/**
 * Exception for a maximum age that is not a whole number of days in range.
 */
final class InvalidMaxAge extends InvalidArgumentException implements ContentFreshnessException {

	use Stringify;

	/**
	 * Create a new instance of the exception for a number of days that is out
	 * of range.
	 *
	 * @param int $days    Number of days that was given.
	 * @param int $minimum Smallest number of days allowed.
	 * @param int $maximum Largest number of days allowed.
	 */
	public static function from_days( int $days, int $minimum, int $maximum ): self {
		return new self(
			\sprintf(
				'A maximum age of %1$d days is out of range. Use a number of days from %2$d to %3$d.',
				$days,
				$minimum,
				$maximum
			)
		);
	}

	/**
	 * Create a new instance of the exception for a value that is not a whole
	 * number.
	 *
	 * @param mixed $value Value that was given.
	 */
	public static function from_value( $value ): self {
		return new self(
			\sprintf(
				'The maximum age "%s" is not a whole number of days.',
				self::stringify( $value )
			)
		);
	}
}
