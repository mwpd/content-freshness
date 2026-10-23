<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class SampleServiceIdPlugin extends ServiceBasedPlugin {

	protected const SERVICE_PREFIX = 'generated.plugin.';

	protected function get_service_classes(): array {
		return [
			'mwpd.content_freshness.service' => GoodService::class,
		];
	}
}
