<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class AdminMenuPlugin extends ServiceBasedPlugin {

	protected const SERVICE_PREFIX = 'fixture.';

	protected const HOOK_PREFIX = 'mwpd.content_freshness.';

	protected function get_service_classes(): array {
		return [
			'fixture.delayed' => AdminMenuService::class,
		];
	}
}
