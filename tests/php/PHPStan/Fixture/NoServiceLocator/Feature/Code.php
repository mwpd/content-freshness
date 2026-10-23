<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Code {
	public function name(): string {
		return \MWPD\ContentFreshness\Infrastructure\ServiceContainer::class;
	}
}
