<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use MWPD\ContentFreshness\Infrastructure\View\SimpleView;
use MWPD\ContentFreshness\Infrastructure\View\SimpleViewFactory;
use MWPD\ContentFreshness\Tests\ViewHelper;

final class SimpleViewTest extends TestCase {

	public function test_it_loads_partials_across_overrides(): void {
		$view = new SimpleView(
			ViewHelper::VIEWS_FOLDER . 'static-view',
			new SimpleViewFactory()
		);

		$this->assertStringStartsWith(
			'<p>Rendering works.</p>',
			$view->render()
		);
	}
}
