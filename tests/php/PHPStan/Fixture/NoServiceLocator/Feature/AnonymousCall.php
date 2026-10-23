<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class AnonymousCall {
	public function create(): object {
		return new class {
			public function configure( \MWPD\ContentFreshness\Infrastructure\Injector $injector ): void {
				$injector->share( self::class );
			}
		};
	}
}
