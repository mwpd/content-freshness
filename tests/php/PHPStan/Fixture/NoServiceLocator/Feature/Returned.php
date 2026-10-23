<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Returned {
	public function container(): \MWPD\ContentFreshness\Infrastructure\ServiceContainer {}
}
