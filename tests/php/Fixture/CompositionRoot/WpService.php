<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\Service;

final class WpService implements Service, Delayed {

	public static function get_registration_action(): string {
		return 'wp';
	}
}
