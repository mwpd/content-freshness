<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

class TestDependentService implements Service, HasDependencies {
	public static function get_dependencies(): array {
		return [ 'delayed_service' ];
	}
}
