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

namespace MWPD\ContentFreshness\Freshness;

use DateInterval;
use DateTimeImmutable;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;

/**
 * How long a post may go without an update before it counts as outdated.
 *
 * WordPress stores the setting as whatever it was given. This value object is
 * where that loose value becomes a number of days the rest of the plugin can
 * rely on: it only exists with a whole number of days in the allowed range.
 */
final class MaxAge {

	/**
	 * Number of days used when the site has not chosen one.
	 *
	 * @var int
	 */
	public const DEFAULT_DAYS = 365;

	/**
	 * Smallest number of days allowed.
	 *
	 * @var int
	 */
	public const MINIMUM_DAYS = 1;

	/**
	 * Largest number of days allowed: a century is "never" for a blog post.
	 *
	 * @var int
	 */
	public const MAXIMUM_DAYS = 36500;

	/**
	 * Maximum age in days.
	 */
	private int $days;

	/**
	 * Instantiate a MaxAge object.
	 *
	 * Private, so a MaxAge only comes from the named constructors that check
	 * the number of days.
	 *
	 * @param int $days Maximum age in days.
	 */
	private function __construct( int $days ) {
		$this->days = $days;
	}

	/**
	 * Create a maximum age from a number of days.
	 *
	 * @param int $days Maximum age in days.
	 *
	 * @throws InvalidMaxAge If the number of days is out of range.
	 *
	 * @return self Maximum age.
	 */
	public static function from_days( int $days ): self {
		if ( $days < self::MINIMUM_DAYS || $days > self::MAXIMUM_DAYS ) {
			throw InvalidMaxAge::from_days( $days, self::MINIMUM_DAYS, self::MAXIMUM_DAYS );
		}

		return new self( $days );
	}

	/**
	 * Create a maximum age from a value WordPress hands over, such as a stored
	 * option or the input of the settings form.
	 *
	 * Integers and strings of digits, with surrounding whitespace, are
	 * accepted. Anything else is rejected rather than guessed at: "30.5",
	 * "30 days" and an empty field are all mistakes worth reporting.
	 *
	 * @param mixed $value Value to normalize.
	 *
	 * @throws InvalidMaxAge If the value is not a whole number of days in range.
	 *
	 * @return self Maximum age.
	 */
	public static function from_value( $value ): self {
		if ( \is_int( $value ) ) {
			return self::from_days( $value );
		}

		if ( \is_string( $value ) && 1 === \preg_match( '/^\s*\d{1,9}\s*$/', $value ) ) {
			return self::from_days( (int) \trim( $value ) );
		}

		throw InvalidMaxAge::from_value( $value );
	}

	/**
	 * Get the maximum age in days.
	 *
	 * @return int Maximum age in days.
	 */
	public function in_days(): int {
		return $this->days;
	}

	/**
	 * Get the moment this maximum age before a given moment.
	 *
	 * @param DateTimeImmutable $moment Moment to count back from.
	 *
	 * @return DateTimeImmutable Moment the maximum age earlier.
	 */
	public function before( DateTimeImmutable $moment ): DateTimeImmutable {
		return $moment->sub( new DateInterval( 'P' . $this->days . 'D' ) );
	}
}
