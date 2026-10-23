<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\NetworkActivateable;
use MWPD\ContentFreshness\Infrastructure\NetworkDeactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteActivateable;
use MWPD\ContentFreshness\Infrastructure\SiteDeactivateable;

/**
 * Implements the parameterless, site-scoped and network-scoped interfaces.
 */
final class FullLifecycleService implements
	Service,
	Activateable,
	Deactivateable,
	SiteActivateable,
	SiteDeactivateable,
	NetworkActivateable,
	NetworkDeactivateable {

	use RecordsScopedLifecycle;

	private const NAME = 'all';
}
