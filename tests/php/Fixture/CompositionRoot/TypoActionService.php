<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class TypoActionService implements Service, Delayed, Registerable {

	use DefaultRegistrationPriority;

	public static function get_registration_action(): string {
		return 'wp_laoded';
	}

	public function register(): void {
	}
}
