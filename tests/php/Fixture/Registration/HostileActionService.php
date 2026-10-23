<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A delayed service that waits for an action whose name is markup, which the
 * panel must print as text.
 */
final class HostileActionService implements Service, Registerable, Delayed {
	use DefaultRegistrationPriority;

	public const ACTION = '<img src=x onerror="alert(1)">';

	public static function get_registration_action(): string {
		return self::ACTION;
	}

	public function register(): void {}
}
