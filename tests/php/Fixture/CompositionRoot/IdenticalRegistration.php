<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class IdenticalRegistration implements Service, Registerable {

	public function register(): void {
		add_action( 'init', [ $this, 'run' ], 10, 0 );
		add_filter( 'the_content', [ $this, 'filter' ] );
	}

	public function run(): void {
	}

	public function filter( string $content ): string {
		return $content;
	}
}
