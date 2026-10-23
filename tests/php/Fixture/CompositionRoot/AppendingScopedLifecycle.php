<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Lifecycle\NetworkContext;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteContext;
use MWPD\ContentFreshness\Infrastructure\NetworkActivateable;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteUninstallable;

final class AppendingScopedLifecycle implements Service, NetworkActivateable, SiteUninstallable {

	public function activate_network( NetworkContext $context ): void {
		add_user_meta( 42, 'fixture_runs', 'network activated' );
	}

	public function uninstall_site( SiteContext $context ): void {
		add_post_meta( 42, 'fixture_runs', 'uninstalled' );
	}
}
