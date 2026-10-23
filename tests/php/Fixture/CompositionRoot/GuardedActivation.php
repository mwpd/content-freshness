<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class GuardedActivation implements Service, Activateable {

	private bool $activated = false;

	public function activate(): void {
		if ( $this->activated ) {
			return;
		}

		add_user_meta( 42, 'fixture_runs', 'activated' );
		$this->activated = true;
	}
}
