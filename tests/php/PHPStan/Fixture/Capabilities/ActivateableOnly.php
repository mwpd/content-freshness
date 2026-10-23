<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Activateable;

final class ActivateableOnly implements Activateable {
	public function activate(): void {}
}
