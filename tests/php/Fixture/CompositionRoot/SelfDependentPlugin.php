<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class SelfDependentPlugin extends ServiceBasedPlugin {

	protected function get_service_classes(): array {
		return [
			'b' => DependsOnB::class,
		];
	}
}
