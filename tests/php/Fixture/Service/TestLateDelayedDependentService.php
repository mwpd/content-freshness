<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A delayed service with a late priority on wp, depending on a service that is
 * delayed to an earlier action.
 */
class TestLateDelayedDependentService implements Service, Delayed, HasDependencies {
	public static function get_registration_action(): string {
		return 'wp';
	}

	public static function get_registration_priority(): int {
		return 20;
	}

	public static function get_dependencies(): array {
		return [ 'delayed_service' ];
	}
}
