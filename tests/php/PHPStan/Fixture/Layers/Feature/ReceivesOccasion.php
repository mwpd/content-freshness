<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Observes;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Occasion;

final class ReceivesOccasion implements Observes {
	public function observe( Occasion $occasion ): void {
	}
}

final class KeepsOccasion {
	public function keep( Occasion $occasion ): void {
	}
}
