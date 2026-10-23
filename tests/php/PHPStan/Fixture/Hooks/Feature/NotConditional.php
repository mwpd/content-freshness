<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Hooks\Feature;

final class NotConditional {

	public static function is_needed(): bool {
		return is_singular();
	}
}
