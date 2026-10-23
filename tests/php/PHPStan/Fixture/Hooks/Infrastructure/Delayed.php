<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Hooks\Infrastructure;

interface Delayed {

	public static function get_registration_action(): string;
}
