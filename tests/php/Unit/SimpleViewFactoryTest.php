<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit;

use MWPD\ContentFreshness\Exception\FailedToLoadView;
use MWPD\ContentFreshness\Exception\InvalidPath;
use MWPD\ContentFreshness\Infrastructure\View;
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

	public function test_it_creates_a_view_that_renders_nothing_for_a_missing_view_in_production(): void {
		$view = ( new SimpleViewFactory( $this->debug_mode( false ) ) )->create( 'non/existent/view' );

		$this->assertInstanceOf( View::class, $view );
		$this->assertSame( '', $view->render( [ 'some_value' => 'ignored' ] ) );
		$this->assertSame( '', $view->render_partial( 'non/existent/partial' ) );
		$this->assertNull( $view->raw( 'some_value' ) );
		$this->assertSame( '', $view->some_value );
	}

	public function test_it_throws_for_a_missing_view_in_debug_mode(): void {
		$this->expectException( InvalidPath::class );

		( new SimpleViewFactory( $this->debug_mode( true ) ) )->create( 'non/existent/view' );
	}

	public function test_its_views_render_nothing_for_a_failing_template_in_production(): void {
		$view = ( new SimpleViewFactory( $this->debug_mode( false ) ) )
			->create( 'tests/php/Fixture/views/plugin/throwing-view' );

		$this->assertSame( '', $view->render() );
	}

	public function test_its_views_throw_for_a_failing_template_in_debug_mode(): void {
		$view = ( new SimpleViewFactory( $this->debug_mode( true ) ) )
			->create( 'tests/php/Fixture/views/plugin/throwing-view' );

		$this->expectException( FailedToLoadView::class );

		$view->render();
	}
}
