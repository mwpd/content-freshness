<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class PrefixedActionPlugin extends ServiceBasedPlugin {

	protected const HOOK_PREFIX = 'mwpd.content_freshness.';

	protected function get_service_classes(): array {
		return [
			'delayed' => PrefixedActionService::class,
		];
	}
}
