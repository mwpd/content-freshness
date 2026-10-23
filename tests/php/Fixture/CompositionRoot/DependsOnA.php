<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

final class DependsOnA implements Service, HasDependencies {

	public static function get_dependencies(): array {
		return [ 'fixture.a' ];
	}
}
