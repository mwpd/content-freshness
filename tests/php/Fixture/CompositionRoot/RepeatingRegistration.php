<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class RepeatingRegistration implements Service, Registerable {

	public function register(): void {
		add_action( 'init', static function (): void {}, 10, 0 );
		add_filter( 'the_content', static fn( string $content ): string => $content );
	}
}
