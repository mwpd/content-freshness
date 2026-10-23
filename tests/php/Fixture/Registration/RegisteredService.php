<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that is registered at once.
 */
final class RegisteredService implements Service, Registerable {

	public function register(): void {
		\add_action( 'wp_footer', [ $this, 'noop' ], 10, 0 );
	}

	public function noop(): void {}
}
