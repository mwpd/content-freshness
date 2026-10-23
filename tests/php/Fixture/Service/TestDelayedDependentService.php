<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Registers at "wp", and depends on a service that registers at "init".
 */
class TestDelayedDependentService implements Service, Delayed, HasDependencies {
	use DefaultRegistrationPriority;

	public static function get_registration_action(): string {
		return 'wp';
	}

	public static function get_dependencies(): array {
		return [ 'delayed_service' ];
	}
}
