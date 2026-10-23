<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class ReschedulingActivation implements Service, Activateable {

	public function activate(): void {
		wp_schedule_event( 1700000000, 'weekly', 'fixture_event' );
	}
}
