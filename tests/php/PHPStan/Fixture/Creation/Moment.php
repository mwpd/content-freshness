<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Creation;

use DateTimeImmutable;

final class Moment extends DateTimeImmutable {

	public function copy(): DateTimeImmutable {
		return new parent();
	}
}
