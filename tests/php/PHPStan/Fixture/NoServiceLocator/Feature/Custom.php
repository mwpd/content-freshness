<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Custom {
	public function configure( \stdClass $container, \ArrayObject $injector, \MWPD\ContentFreshness\Infrastructure\Injector $default ): void {
		$injector->append( 'value' );
		$default->bind( self::class, self::class );
	}
}
