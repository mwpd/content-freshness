<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

use MWPD\ContentFreshness\Infrastructure\ServiceContainer;

final class Repeated {
	/** @var ServiceContainer */
	private ServiceContainer $container;
	public function __construct( ServiceContainer $container ) {
		$this->container = $container;
	}
}
