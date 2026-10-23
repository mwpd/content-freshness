<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class IdenticalCronActivation implements Service, Activateable {

	public function activate(): void {
		wp_schedule_event( 100, 'daily', 'fixture_event', [ 42 ] );
	}
}
