<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

use MWPD\ContentFreshness\Infrastructure\Service;

final class CallService implements Service {

	public function __construct() {
		$this->option = get_option( 'mwpd_content_freshness_option' );
	}
}
