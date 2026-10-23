<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Schedules the same event, at the same time with the same arguments, which
 * WordPress stores once however often it is scheduled.
 */
final class SameEventScheduling implements Service, Activateable {

	public function activate(): void {
		wp_schedule_event( 1700000000, 'weekly', 'fixture_same_event', [ 'same' ] );
	}
}
