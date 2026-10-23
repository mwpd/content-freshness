<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class ThrowingLifecycle implements Service, Registerable, Activateable, Deactivateable {

	public function register(): void {
		throw new \RuntimeException( 'registration failed' );
	}

	private bool $activated = false;

	public function activate(): void {
		if ( $this->activated ) {
			throw new \RuntimeException( 'activation failed' );
		}

		$this->activated = true;
	}

	public function deactivate(): void {
		throw new \RuntimeException( 'deactivation failed' );
	}
}
