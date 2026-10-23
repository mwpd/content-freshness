<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestServiceA;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestServiceB;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestServiceC;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestDelayedService;
use MWPD\ContentFreshness\Tests\Fixture\Service\TestDependentService;

class TestServiceBasedPlugin extends ServiceBasedPlugin {
	protected function get_service_classes(): array {
		return [
			'service_a'         => TestServiceA::class,
			'service_b'         => TestServiceB::class,
			'service_c'         => TestServiceC::class,
			'delayed_service'   => TestDelayedService::class,
			'dependent_service' => TestDependentService::class,
		];
	}
}
