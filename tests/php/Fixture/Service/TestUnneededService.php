<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Service;

use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\Service;

class TestUnneededService implements Service, Conditional {
	public static function is_needed(): bool {
		return false;
	}
}
