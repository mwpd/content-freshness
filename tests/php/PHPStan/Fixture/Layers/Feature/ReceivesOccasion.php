<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Observes;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\ObservesMore;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Occasion;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Reports;

final class ReceivesOccasion implements Observes {
	public function observe( Occasion $occasion ): void {
	}
}

final class KeepsOccasion {
	public function keep( Occasion $occasion ): void {
	}
}

// A nullable return type of an implemented interface.
final class ReturnsOccasion implements Reports {
	public function latest(): ?Occasion {
		return null;
	}
}

// A method of an interface the implemented one extends.
final class ObservesMoreOccasions implements ObservesMore {
	public function observe( Occasion $occasion ): void {
	}
}
