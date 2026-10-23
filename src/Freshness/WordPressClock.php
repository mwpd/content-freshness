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
 * A clock that tells the time the way WordPress does.
 */
final class WordPressClock implements Clock {

	/**
	 * Get the current moment, in the site's timezone.
	 *
	 * @return DateTimeImmutable Current moment.
	 */
	public function now(): DateTimeImmutable {
		return \current_datetime();
	}
}
