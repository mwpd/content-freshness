<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Service;

final class CustomActionService implements Service, Delayed {

	use DefaultRegistrationPriority;

	public static function get_registration_action(): string {
		return 'fixture.custom_action';
	}
}
