<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class UnneededDelayedLifecycleService implements
	Service,
	Registerable,
	Conditional,
	Delayed,
	Activateable,
	Deactivateable {
	use DefaultRegistrationPriority;
	use RecordsLifecycle;

	private const NAME = 'unneeded-delayed';

	public static function is_needed(): bool {
		return false;
	}

	public static function get_registration_action(): string {
		return 'init';
	}

	public function register(): void {
		$this->recorder->record( 'register:' . self::NAME );
	}
}
