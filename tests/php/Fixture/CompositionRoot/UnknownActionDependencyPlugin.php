<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class UnknownActionDependencyPlugin extends ServiceBasedPlugin {

	protected const HOOK_PREFIX = 'fixture.';

	protected function get_service_classes(): array {
		return [
			'a'     => DependsOnB::class,
			'b'     => CustomActionService::class,
			'later' => WpDependentService::class,
		];
	}
}
