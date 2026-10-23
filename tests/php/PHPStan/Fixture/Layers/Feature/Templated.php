<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature;

/**
 * @template T = \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Concrete
 */
final class Templated {

	/**
	 * @param \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Plugin::NAME $key
	 */
	public function read( string $key ): void {
	}
}
