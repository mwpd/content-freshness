<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure as Infra;

final class Aliased {

	public function work( Infra\Contract $contract ): void {
		$contract->work();
	}
}
