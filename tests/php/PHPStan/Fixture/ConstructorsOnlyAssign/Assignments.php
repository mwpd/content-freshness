<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\Service\DebugMode;
use MWPD\ContentFreshness\Infrastructure\Service\WordPressDebugMode;

final class Assignments implements Service {

	public function __construct( string $value, ?DebugMode $debug_mode = null, ?DebugMode $anonymous = null ) {
		// Dependencies are stored without doing work.
		$this->value = $value;
		$this->debug_mode = $debug_mode ?? new WordPressDebugMode();
		$this->anonymous = $anonymous ?? new class() implements DebugMode {

			public function is_debug_mode(): bool {
				return true;
			}
		};
		// A trailing comment is also allowed.
	}

	public function register(): void {
		get_option( 'mwpd_content_freshness_option' );
	}
}
