<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Creates implements \MWPD\ContentFreshness\Infrastructure\Service {
	private \MWPD\ContentFreshness\Infrastructure\Injector $injector;
	public function __construct( \MWPD\ContentFreshness\Infrastructure\Injector $injector ) {
		$this->injector = $injector;
	}
	public function create(): object {
		return $this->injector->MaKe( self::class );
	}
}
