<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Asserted {
	/** @phpstan-assert \MWPD\ContentFreshness\Infrastructure\ServiceContainer $value */
	public function check( $value ): void {}
}
