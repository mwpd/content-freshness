<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\Uninstallable;

final class ChangingUninstall implements Service, Uninstallable {

	private int $runs = 0;

	public function uninstall(): void {
		update_option( 'fixture_runs', ++$this->runs );
	}
}
