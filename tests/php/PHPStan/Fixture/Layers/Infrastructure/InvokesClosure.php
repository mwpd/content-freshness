<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure;

final class InvokesClosure {

	public function run( object $service ): void {
		( static function ( \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature\Service $service ): void {
		} )( $service );
	}
}
