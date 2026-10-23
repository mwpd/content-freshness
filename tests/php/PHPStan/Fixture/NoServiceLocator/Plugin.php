<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator;

final class Plugin {
	public function configure( \MWPD\ContentFreshness\Infrastructure\ServiceContainer $container, \MWPD\ContentFreshness\Infrastructure\Injector $injector ): void {
		$injector->bind( self::class, self::class );
	}
}
