<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

final class NotService {

	public function __construct() {
		get_option( 'mwpd_content_freshness_option' );
	}
}
