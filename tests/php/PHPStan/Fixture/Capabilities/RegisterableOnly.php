<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Registerable;

final class RegisterableOnly implements Registerable {
	public function register(): void {}
}
