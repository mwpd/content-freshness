<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

/**
 * A composition root whose callable declarations build objects themselves,
 * through the injector the plugin holds.
 */
final class CallbackPlugin extends ServiceBasedPlugin {

	protected const SERVICE_PREFIX = 'fixture.';

	protected function get_service_classes(): array {
		return [
			'fixture.hooking-callback' => function (): string {
				$this->injector->make( HookingCollaborator::class );

				return GoodService::class;
			},
			'fixture.failing-callback' => function (): string {
				$this->injector->make( NeedsContract::class );

				return GoodService::class;
			},
			'fixture.good'             => GoodService::class,
		];
	}
}
