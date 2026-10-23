<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Schedules a recurring event relative to the current time without checking
 * whether one is scheduled, so every run adds another event.
 */
final class ReschedulingActivation implements Service, Activateable {

	private int $now = 1700000000;

	public function activate(): void {
		wp_schedule_event( ++$this->now, 'weekly', 'fixture_event' );
	}
}
