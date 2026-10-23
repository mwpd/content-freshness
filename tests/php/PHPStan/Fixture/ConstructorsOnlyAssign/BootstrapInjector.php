<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

use MWPD\ContentFreshness\Infrastructure\Injector;

abstract class BootstrapInjector implements Injector {

	public function __construct() {
		get_option( 'mwpd_content_freshness_option' );
	}
}
