<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit;

use MWPD\ContentFreshness\Infrastructure\View\SimpleViewFactory;
use MWPD\ContentFreshness\Infrastructure\ViewFactory;

final class SimpleViewFactoryTest extends TestCase {

	public function test_it_can_be_instantiated(): void {
		$factory = new SimpleViewFactory();

		$this->assertInstanceOf( SimpleViewFactory::class, $factory );
	}

	public function test_it_implements_the_interface(): void {
		$factory = new SimpleViewFactory();

		$this->assertInstanceOf( ViewFactory::class, $factory );
	}
}
