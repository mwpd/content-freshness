<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

final class DocumentedReturn {
	/** @return \MWPD\ContentFreshness\Infrastructure\ServiceContainer */
	public function container() {}
}
