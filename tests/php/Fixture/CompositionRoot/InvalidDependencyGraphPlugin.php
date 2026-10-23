<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class InvalidDependencyGraphPlugin extends ServiceBasedPlugin {

	protected function get_service_classes(): array {
		return [
			'a' => DependsOnB::class,
			'b' => NotAService::class,
		];
	}
}
