<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class RemovingDeactivation implements Service, Deactivateable {

	public function deactivate(): void {
		remove_filter( 'the_content', '__return_true' );
	}
}
