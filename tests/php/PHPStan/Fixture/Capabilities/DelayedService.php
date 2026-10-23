<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\{DefaultRegistrationPriority, Delayed};
use MWPD\ContentFreshness\Infrastructure\Service;

final class DelayedService implements Service, Delayed {
	use DefaultRegistrationPriority;

	public static function get_registration_action(): string { return "wp"; }
}
