<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Layers\Feature\Service;

final class Stray {

	public function service( Service $service ): Service {
		return $service;
	}
}
