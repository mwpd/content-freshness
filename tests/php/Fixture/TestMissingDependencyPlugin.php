<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestServiceWithMissingDependency;

class TestMissingDependencyPlugin extends ServiceBasedPlugin {
	protected function get_service_classes(): array {
		return [
			'service_with_missing' => TestServiceWithMissingDependency::class,
		];
	}
}
