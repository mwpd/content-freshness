<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Amount;

final class StrayValueUser {

	public function amount(): Amount {
		return Amount::of( 1 );
	}
}
