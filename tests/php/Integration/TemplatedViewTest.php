<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use MWPD\ContentFreshness\Infrastructure\View\TemplatedView;
use MWPD\ContentFreshness\Infrastructure\View\TemplatedViewFactory;
use MWPD\ContentFreshness\Tests\ViewHelper;

final class TemplatedViewTest extends TestCase {

	public function test_it_loads_partials_across_overrides(): void {
		$partials = new TemplatedView(
			'partial-a',
			new TemplatedViewFactory( ViewHelper::LOCATIONS ),
			ViewHelper::LOCATIONS
		);

		$this->assertStringStartsWith(
			'partial A from plugin - partial B from parent theme - partial C from child theme - '
			. 'partial D from parent theme - partial E from plugin',
			$partials->render()
		);
	}
}
