<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\PluginFactoryOnlyAtEdges;

use DateTimeImmutable;

final class Service {
	public function run(): void {
		PluginFactory::create();
		pluginfactory::CREATE();
		\MWPD\ContentFreshness\Tests\PHPStan\Fixture\PluginFactoryOnlyAtEdges\PluginFactory::create();
		OtherFactory::create();
		DateTimeImmutable::createFromFormat( 'Y', '2026' );
	}
}
