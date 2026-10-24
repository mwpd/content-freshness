<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure;

final class Amount {

	public static function of( int $value ): self {
		return new self();
	}
}
