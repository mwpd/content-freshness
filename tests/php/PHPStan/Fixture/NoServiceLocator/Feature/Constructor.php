<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class Constructor implements \MWPD\ContentFreshness\Infrastructure\Service {
	public function __construct( \MWPD\ContentFreshness\Infrastructure\ServiceContainer $container ) {}
}
