<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteContext;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteUninstallable;

final class CleaningUninstall implements Service, SiteUninstallable {

	public function uninstall_site( SiteContext $context ): void {
		delete_option( 'fixture_runs' );
	}
}
