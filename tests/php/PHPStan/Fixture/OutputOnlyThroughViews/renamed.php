<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\OutputOnlyThroughViews;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\OutputOnlyThroughViews\Infrastructure\Renderable;

final class RenamedOutput {
	public function output( Renderable $renderable ): void {
		echo $renderable->render();
	}
}
