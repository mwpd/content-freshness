<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\NetworkUninstallable;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteUninstallable;

/**
 * Cleans up each site and each network on uninstall.
 */
final class UninstallService implements Service, SiteUninstallable, NetworkUninstallable {
	use RecordsScopedLifecycle;

	private const NAME = 'uninstall';
}
