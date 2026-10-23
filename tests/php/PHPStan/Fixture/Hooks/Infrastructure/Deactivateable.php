<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Hooks\Infrastructure;

interface Deactivateable {

	public function deactivate(): void;
}
