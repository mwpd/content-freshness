<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure;

interface Observes {
	public function observe( Occasion $occasion ): void;
}
