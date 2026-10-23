<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteActivateable;
use MWPD\ContentFreshness\Infrastructure\SiteDeactivateable;

/**
 * Implements the parameterless and the site-scoped interfaces.
 */
final class LegacyAndSiteLifecycleService implements
	Service,
	Activateable,
	Deactivateable,
	SiteActivateable,
	SiteDeactivateable {

	use RecordsScopedLifecycle;

	private const NAME = 'legacy+site';
}
