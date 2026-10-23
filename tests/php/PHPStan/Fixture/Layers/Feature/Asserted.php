<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature;

final class Asserted {

	/**
	 * @phpstan-assert \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Concrete $value
	 */
	public function check( object $value ): void {
	}
}
