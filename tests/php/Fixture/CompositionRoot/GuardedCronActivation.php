<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class GuardedCronActivation implements Service, Activateable {

	public function activate(): void {
		if ( wp_next_scheduled( 'fixture_event', [ 42 ] ) === false ) {
			wp_schedule_event( 100, 'daily', 'fixture_event', [ 42 ] );
		}
	}
}
