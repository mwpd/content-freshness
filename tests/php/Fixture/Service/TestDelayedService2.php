<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Service;

class TestDelayedService2 implements Service, Delayed {
	public static function get_registration_action(): string {
		return 'wp_loaded';
	}
}
