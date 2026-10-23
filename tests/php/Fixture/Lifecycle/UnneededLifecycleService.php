<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Uninstallable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class UnneededLifecycleService implements
	Service,
	Conditional,
	Activateable,
	Deactivateable,
	Uninstallable {
	use RecordsLifecycle;

	private const NAME = 'unneeded';

	public static function is_needed(): bool {
		return false;
	}
}
