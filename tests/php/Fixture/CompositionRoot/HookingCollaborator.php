<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

final class HookingCollaborator {

	public function __construct() {
		add_action( 'init', [ $this, 'boot' ], 10, 0 );
	}

	public function boot(): void {
	}
}
