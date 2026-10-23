<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class EmptyServicePrefixPlugin extends ServiceBasedPlugin {

	protected const SERVICE_PREFIX = '';

	protected function get_service_classes(): array {
		return [
			'fixture.service' => GoodService::class,
		];
	}
}
