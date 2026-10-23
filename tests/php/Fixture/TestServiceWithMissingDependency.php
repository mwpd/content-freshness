<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture;

use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

class TestServiceWithMissingDependency implements Service, HasDependencies {
	public static function get_dependencies(): array {
		return [ 'non_existent_service' ];
	}
}
