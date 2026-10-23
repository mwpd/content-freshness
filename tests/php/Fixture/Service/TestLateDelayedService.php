<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A delayed service that implements its own, later registration priority.
 */
class TestLateDelayedService implements Service, Delayed {
	public static function get_registration_action(): string {
		return 'init';
	}

	public static function get_registration_priority(): int {
		return 20;
	}
}
