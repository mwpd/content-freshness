<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A delayed service that asks for an early priority on init, but depends on a
 * service that may only be ready later.
 *
 * It uses the default trait and overrides its method, to show that a consuming
 * class can.
 */
class TestEarlyDelayedDependentService implements Service, Delayed, HasDependencies {
	use DefaultRegistrationPriority;

	public static function get_registration_action(): string {
		return 'init';
	}

	public static function get_registration_priority(): int {
		return 5;
	}

	public static function get_dependencies(): array {
		return [ 'late_service' ];
	}
}
