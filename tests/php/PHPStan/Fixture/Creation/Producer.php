<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Creation;

final class Producer implements ProducerFactory {

	public function make(): Service {
		return new Service();
	}
}
