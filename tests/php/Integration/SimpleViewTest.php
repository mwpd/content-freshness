<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use MWPD\ContentFreshness\Infrastructure\View\SimpleView;
use MWPD\ContentFreshness\Infrastructure\View\SimpleViewFactory;
use MWPD\ContentFreshness\Infrastructure\View\ViewConfiguration;
use MWPD\ContentFreshness\Tests\ViewHelper;

final class SimpleViewTest extends TestCase {

	public function test_it_loads_partials_across_overrides(): void {
		$configuration = new ViewConfiguration( ViewHelper::PLUGIN_ROOT, ViewHelper::OVERRIDE_DIRECTORY );
		$view          = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'static-view',
			new SimpleViewFactory( null, $configuration ),
			null,
			$configuration
		);

		$this->assertStringStartsWith(
			'<p>Rendering works.</p>',
			$view->render()
		);
	}
}
