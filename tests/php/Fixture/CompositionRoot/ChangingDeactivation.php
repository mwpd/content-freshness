<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class ChangingDeactivation implements Service, Deactivateable {

	private int $runs = 0;

	public function deactivate(): void {
		update_option( 'fixture_runs', ++$this->runs );
	}
}
