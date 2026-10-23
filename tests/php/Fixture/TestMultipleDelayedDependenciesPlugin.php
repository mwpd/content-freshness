<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestDelayedService1;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestDelayedService2;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestMultiDependentService;

class TestMultipleDelayedDependenciesPlugin extends ServiceBasedPlugin {
	protected function get_service_classes(): array {
		return [
			'delayed_service_1' => TestDelayedService1::class,
			'delayed_service_2' => TestDelayedService2::class,
			'dependent_service' => TestMultiDependentService::class,
		];
	}
}
