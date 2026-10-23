<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Service;

final class RemovingService implements Service {

	public function __construct() {
		remove_filter( 'the_content', 'wpautop' );
	}
}
