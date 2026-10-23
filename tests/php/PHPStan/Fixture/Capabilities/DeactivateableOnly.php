<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Deactivateable;

final class DeactivateableOnly implements Deactivateable {
	public function deactivate(): void {}
}
