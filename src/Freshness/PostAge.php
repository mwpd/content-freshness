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

use DateTimeImmutable;

/**
 * How long ago a post was last updated, measured at a given moment.
 *
 * It keeps both moments, so a view can say "3 years ago" in the reader's
 * language with WordPress's human_time_diff(), and the digest can sort by
 * days, without either of them asking the clock again.
 */
final class PostAge {

	/**
	 * Number of seconds in a day.
	 *
	 * @var int
	 */
	private const SECONDS_PER_DAY = 86400;

	/**
	 * Moment the post was last updated.
	 */
	private DateTimeImmutable $last_updated;

	/**
	 * Moment the age was measured at.
	 */
	private DateTimeImmutable $measured_at;

	/**
	 * Instantiate a PostAge object.
	 *
	 * @param DateTimeImmutable $last_updated Moment the post was last updated.
	 * @param DateTimeImmutable $measured_at  Moment the age is measured at.
	 */
	private function __construct( DateTimeImmutable $last_updated, DateTimeImmutable $measured_at ) {
		$this->last_updated = $last_updated;
		$this->measured_at  = $measured_at;
	}

	/**
	 * Create the age of a post between its last update and a later moment.
	 *
	 * @param DateTimeImmutable $last_updated Moment the post was last updated.
	 * @param DateTimeImmutable $measured_at  Moment the age is measured at.
	 *
	 * @return self Age of the post.
	 */
	public static function between( DateTimeImmutable $last_updated, DateTimeImmutable $measured_at ): self {
		return new self( $last_updated, $measured_at );
	}

	/**
	 * Get the moment the post was last updated.
	 *
	 * @return DateTimeImmutable Moment the post was last updated.
	 */
	public function last_updated(): DateTimeImmutable {
		return $this->last_updated;
	}

	/**
	 * Get the moment the age was measured at.
	 *
	 * @return DateTimeImmutable Moment the age was measured at.
	 */
	public function measured_at(): DateTimeImmutable {
		return $this->measured_at;
	}

	/**
	 * Get the age in whole days.
	 *
	 * A post updated after the moment of measurement, which a clock that is
	 * off can produce, is zero days old rather than a negative age.
	 *
	 * @return int Age in whole days.
	 */
	public function in_days(): int {
		$seconds = $this->measured_at->getTimestamp() - $this->last_updated->getTimestamp();

		return \intdiv( \max( 0, $seconds ), self::SECONDS_PER_DAY );
	}
}
