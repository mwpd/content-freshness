<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteActivateable;
use MWPD\ContentFreshness\Infrastructure\SiteDeactivateable;

/**
 * Set up and cleaned up in each site.
 */
final class SiteLifecycleService implements Service, SiteActivateable, SiteDeactivateable {
	use RecordsScopedLifecycle;

	private const NAME = 'site';
}
