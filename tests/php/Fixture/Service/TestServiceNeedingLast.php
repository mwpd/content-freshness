<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that waits for a service at the last priority on wp, and for one
 * at the default priority on wp.
 */
class TestServiceNeedingLast implements Service, HasDependencies {
	public static function get_dependencies(): array {
		return [ 'last_service', 'wp_service' ];
	}
}
