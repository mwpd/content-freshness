<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\NetworkActivateable;
use MWPD\ContentFreshness\Infrastructure\NetworkDeactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Set up and cleaned up once for a network.
 */
final class NetworkLifecycleService implements Service, NetworkActivateable, NetworkDeactivateable {
	use RecordsScopedLifecycle;

	private const NAME = 'network';
}
