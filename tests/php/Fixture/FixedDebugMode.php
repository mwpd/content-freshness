<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture;

use MWPD\ContentFreshness\Infrastructure\Service\DebugMode;

/**
 * Debug mode switched on or off by the test, instead of by WP_DEBUG.
 */
final class FixedDebugMode implements DebugMode {

	private bool $debug_mode;

	public function __construct( bool $debug_mode ) {
		$this->debug_mode = $debug_mode;
	}

	public function is_debug_mode(): bool {
		return $this->debug_mode;
	}
}
