<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use MWPD\ContentFreshness\Infrastructure\View;
use MWPD\ContentFreshness\Infrastructure\View\SimpleView;
use MWPD\ContentFreshness\Infrastructure\View\SimpleViewFactory;
use MWPD\ContentFreshness\Infrastructure\View\ViewConfiguration;
use MWPD\ContentFreshness\Tests\ViewHelper;

final class SimpleViewFactoryTest extends TestCase {

	public function test_it_can_create_views(): void {
		$factory = new SimpleViewFactory(
			null,
			new ViewConfiguration( ViewHelper::PLUGIN_ROOT, ViewHelper::OVERRIDE_DIRECTORY )
		);

		$view = $factory->create( ViewHelper::VIEWS_FOLDER . 'static-view' );
		$this->assertInstanceOf( SimpleView::class, $view );
	}

	public function test_created_views_implement_the_interface(): void {
		$factory = new SimpleViewFactory(
			null,
			new ViewConfiguration( ViewHelper::PLUGIN_ROOT, ViewHelper::OVERRIDE_DIRECTORY )
		);

		$view = $factory->create( ViewHelper::VIEWS_FOLDER . 'static-view' );
		$this->assertInstanceOf( View::class, $view );
	}
}
