<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Contract;

final class StrayTraitUser implements Contract {

	use \MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\DefaultWork;
}
