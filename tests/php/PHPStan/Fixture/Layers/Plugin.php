<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature\Service;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Infrastructure\Concrete;

final class Plugin {

	public const NAME = 'plugin';

	public function service(): Service {
		return new Service( new Concrete() );
	}

	public function fake(): object {
		return new TestDouble\Fake();
	}
}
