<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class ChangingPriorityRegistration implements Service, Registerable {

	private int $priority = 10;

	public function register(): void {
		add_action( 'init', [ $this, 'run' ], $this->priority++, 0 );
	}

	public function run(): void {
	}
}
