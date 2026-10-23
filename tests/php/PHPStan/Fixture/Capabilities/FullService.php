<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\Delayed;

final class FullService extends BaseService implements Conditional, Delayed {
	public function register(): void {}
	public static function is_needed(): bool { return true; }
	public static function get_registration_action(): string { return 'wp'; }
}
