<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign\Excluded;

use MWPD\ContentFreshness\Infrastructure\Service as ServiceMarker;

final class Service implements ServiceMarker {

	public function __construct() {
		get_option( 'mwpd_content_freshness_option' );
	}
}
