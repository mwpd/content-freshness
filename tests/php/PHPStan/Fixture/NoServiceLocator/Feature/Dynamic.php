<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Dynamic {
	public function configure( \MWPD\ContentFreshness\Infrastructure\Injector $injector ): void {
		$method = 'bind';
		$injector->$method( self::class, self::class );
	}
}
