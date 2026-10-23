<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A delayed service that is needed once its action fires, and counts how often
 * it was asked and registered.
 */
final class CountingDelayedNeededService implements Service, Registerable, Conditional, Delayed {
	use DefaultRegistrationPriority;

	public static int $asked = 0;

	public static int $registered = 0;

	public static function get_registration_action(): string {
		return 'init';
	}

	public static function is_needed(): bool {
		++self::$asked;

		return true;
	}

	public function register(): void {
		++self::$registered;
	}
}
