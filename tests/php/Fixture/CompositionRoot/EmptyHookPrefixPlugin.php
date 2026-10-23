<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;

final class EmptyHookPrefixPlugin extends ServiceBasedPlugin {

	protected const HOOK_PREFIX = '';

	protected function get_service_classes(): array {
		return [
			'delayed' => TypoActionService::class,
		];
	}
}
