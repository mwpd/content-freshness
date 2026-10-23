<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\Service\WordPressDebugMode;

final class InvalidAssignments implements Service {

	public function __construct( string $value, ?WordPressDebugMode $debug_mode = null ) {
		$this->value = trim( $value );
		$this->value = 'constant';
		$local = $value;
		$this->debug_mode = $debug_mode ?? new WordPressDebugMode();
		$this->{$value} = $value;
		$this->missing = $missing;
	}
}
