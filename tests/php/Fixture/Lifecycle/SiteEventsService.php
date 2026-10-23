<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteInitializable;
use MWPD\ContentFreshness\Infrastructure\SiteUninitializable;

/**
 * Set up in a new site and cleaned up in a site that is deleted.
 */
final class SiteEventsService implements Service, SiteInitializable, SiteUninitializable {
	use RecordsScopedLifecycle;

	private const NAME = 'site-events';
}
