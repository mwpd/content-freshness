<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class GuardedScheduling implements Service, Activateable, Deactivateable {

	public function activate(): void {
		if ( false === wp_next_scheduled( 'fixture_event' ) ) {
			wp_schedule_event( 1700000000, 'weekly', 'fixture_event' );
		}
	}

	public function deactivate(): void {
		wp_clear_scheduled_hook( 'fixture_event' );
	}
}
