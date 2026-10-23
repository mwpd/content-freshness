<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class DocumentedMethod {
	/** @param \MWPD\ContentFreshness\Infrastructure\ServiceContainer $container */
	public function consume( $container ): void {}
}
