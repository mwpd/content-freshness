<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Not Registerable, so the plugin stores it lazily.
 */
final class LazyLifecycleService implements Service, Activateable, Deactivateable {
	use RecordsLifecycle;

	private const NAME = 'lazy';
}
