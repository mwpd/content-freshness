<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class TypedCalls {
	public function nullable( ?\MWPD\ContentFreshness\Infrastructure\Injector $injector ): void {
		$injector->bind( self::class, self::class );
	}
	/** @param \MWPD\ContentFreshness\Infrastructure\Injector|\ArrayObject $injector */
	public function union( $injector ): void {
		$injector->share( self::class );
	}
	public function subtype( ExtendedInjector $injector ): void {
		$injector->bind_argument( self::class, 'key', 'value' );
	}
	public function unknown( \MWPD\ContentFreshness\Infrastructure\Injector $injector, string $method ): void {
		$injector->$method( self::class );
	}
	public function create( \MWPD\ContentFreshness\Infrastructure\Injector $injector ): object {
		$method = 'make';
		return $injector->$method( self::class );
	}
}
