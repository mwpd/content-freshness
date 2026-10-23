<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

final class ServiceFactory {

	public static function service_class(): string {
		return GoodService::class;
	}
}
