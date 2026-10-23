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
 * Tells the current time.
 *
 * Ages are measured against "now", and a test that reads the real clock tests
 * a moving target. Code that needs the time asks a Clock, so a test can hand
 * it one that stands still.
 */
interface Clock {

	/**
	 * Get the current moment.
	 *
	 * @return DateTimeImmutable Current moment.
	 */
	public function now(): DateTimeImmutable;
}
