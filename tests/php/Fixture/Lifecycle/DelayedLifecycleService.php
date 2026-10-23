<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Uninstallable;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Registers on init, which has not fired in the tests' lifecycle requests.
 */
final class DelayedLifecycleService implements
	Service,
	Registerable,
	Delayed,
	Activateable,
	Deactivateable,
	Uninstallable {
	use RecordsLifecycle;

	private const NAME = 'delayed';

	public static function get_registration_action(): string {
		return 'init';
	}

	public function register(): void {
		$this->recorder->record( 'register:' . self::NAME );
	}
}
