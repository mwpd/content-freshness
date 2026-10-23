<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Conditional;

final class ConditionalOnly implements Conditional {
	public static function is_needed(): bool { return true; }
}
