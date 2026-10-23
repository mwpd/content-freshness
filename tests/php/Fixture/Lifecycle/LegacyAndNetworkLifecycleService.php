<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\NetworkActivateable;
use MWPD\ContentFreshness\Infrastructure\NetworkDeactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Implements the parameterless and the network-scoped interfaces.
 */
final class LegacyAndNetworkLifecycleService implements
	Service,
	Activateable,
	Deactivateable,
	NetworkActivateable,
	NetworkDeactivateable {

	use RecordsScopedLifecycle;

	private const NAME = 'legacy+network';
}
