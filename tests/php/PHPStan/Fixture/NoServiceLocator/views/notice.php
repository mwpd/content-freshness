<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Notice {
	public function configure( \MWPD\ContentFreshness\Infrastructure\ServiceContainer $container, \MWPD\ContentFreshness\Infrastructure\Injector $injector ): void {
		$injector->bind( self::class, self::class );
	}
}
