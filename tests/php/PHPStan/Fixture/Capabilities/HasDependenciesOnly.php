<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\HasDependencies;

final class HasDependenciesOnly implements HasDependencies {
	public static function get_dependencies(): array { return []; }
}
