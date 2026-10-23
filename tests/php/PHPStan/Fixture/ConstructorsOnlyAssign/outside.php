<?php

declare( strict_types=1 );

namespace OtherPlugin;

use MWPD\ContentFreshness\Infrastructure\Service;

final class OutsideService implements Service {

	public function __construct() {
		get_option( 'mwpd_content_freshness_option' );
	}
}
