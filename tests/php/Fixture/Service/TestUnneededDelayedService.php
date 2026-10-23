<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Service;

class TestUnneededDelayedService implements Service, Conditional, Delayed {
	use DefaultRegistrationPriority;

	public static function get_registration_action(): string {
		return 'init';
	}

	public static function is_needed(): bool {
		return false;
	}
}
