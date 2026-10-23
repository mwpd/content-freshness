<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Anonymous {
	public function create(): object {
		return new class {
			private \MWPD\ContentFreshness\Infrastructure\ServiceContainer $container;
		};
	}
}
