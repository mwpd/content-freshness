<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Delayed;

final class DelayedOnly implements Delayed {
	public static function get_registration_action(): string { return "wp"; }
}
