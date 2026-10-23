<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

use MWPD\ContentFreshness\Infrastructure\Service;

final class BranchService implements Service {

	public function __construct( string $value ) {
		if ( $value !== '' ) {
			$this->value = $value;
		}
	}
}
