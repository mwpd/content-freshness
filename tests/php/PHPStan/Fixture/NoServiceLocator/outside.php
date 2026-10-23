<?php

declare( strict_types=1 );

namespace Unrelated;

final class Outside {
	public function configure( \MWPD\ContentFreshness\Infrastructure\ServiceContainer $container, \MWPD\ContentFreshness\Infrastructure\Injector $injector ): void {
		$injector->bind( self::class, self::class );
	}
}
