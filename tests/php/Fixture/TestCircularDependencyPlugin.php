<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestCircularA;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestCircularB;

class TestCircularDependencyPlugin extends ServiceBasedPlugin {
	protected function get_service_classes(): array {
		return [
			'circular_a' => TestCircularA::class,
			'circular_b' => TestCircularB::class,
		];
	}
}
