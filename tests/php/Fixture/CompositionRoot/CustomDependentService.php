<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

final class CustomDependentService implements Service, Delayed, HasDependencies {

	public static function get_registration_action(): string {
		return 'fixture.custom_action';
	}

	public static function get_dependencies(): array {
		return [ 'b' ];
	}
}
