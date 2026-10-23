<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Hooks\Feature;

final class NotRegisterable {

	public function register(): void {
		add_action( 'init', [ $this, 'register' ] );
	}
}
