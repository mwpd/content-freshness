<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature;

/**
 * @method \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Plugin plugin()
 */
final class Documented {

	public function work( object $thing ): void {
		/** @var \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Concrete $thing */
		$thing->work();
	}
}
