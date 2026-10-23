<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\OutputOnlyThroughViews\Infrastructure;

interface Renderable {
	public function render(): string;
}
