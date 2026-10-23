<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Hooks\Feature;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Hooks\Infrastructure\Conditional;

final class QueryConditional implements Conditional {

	public static function is_needed(): bool {
		return is_singular();
	}
}
