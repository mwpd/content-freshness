<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2019 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Infrastructure\Injector;
use MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin;
use MWPD\ContentFreshness\Freshness\PostAge;
use MWPD\ContentFreshness\Infrastructure\View\ViewConfiguration;
use MWPD\ContentFreshness\Infrastructure\ViewFactory;
use MWPD\ContentFreshness\Tests\ViewHelper;
use ReflectionClass;

/**
 * The composition root configures the view layer, without content-freshness.php.
 */
final class CompositionRootViewConfigurationTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'human_time_diff' )->justReturn( '3 years' );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'did_action' )->justReturn( 0 );
	}

	public function test_it_configures_the_plugin_root_next_to_the_composition_root(): void {
		$configuration = $this->make_injector()->make( ViewConfiguration::class );
		$this->assertInstanceOf( ViewConfiguration::class, $configuration );

		// The plugin root is the parent of the directory that holds the
		// composition root's file, src/.
		$composition_root_file = ( new ReflectionClass( ContentFreshnessPlugin::class ) )->getFileName();
		$this->assertIsString( $composition_root_file );

		$this->assertSame(
			\realpath( \dirname( $composition_root_file, 2 ) ),
			\realpath( $configuration->get_plugin_root() )
		);
		$this->assertSame( 'content-freshness', $configuration->get_override_directory() );
	}

	public function test_the_bound_view_factory_uses_the_plugin_override_directory(): void {
		Functions\when( 'get_stylesheet_directory' )->justReturn( ViewHelper::SCOPED_CHILD_THEME_FOLDER );
		Functions\when( 'get_template_directory' )->justReturn( ViewHelper::SCOPED_PARENT_THEME_FOLDER );

		$factory = $this->make_injector()->make( ViewFactory::class );
		$this->assertInstanceOf( ViewFactory::class, $factory );

		$this->assertStringStartsWith(
			'<p>Notice overridden by the child theme for this plugin.</p>',
			$factory->create( 'views/outdated-post-notice' )->render()
		);
	}

	public function test_the_bound_view_factory_falls_back_to_the_plugin_root(): void {
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nonexistent/child-theme' );
		Functions\when( 'get_template_directory' )->justReturn( '/nonexistent/parent-theme' );

		$factory = $this->make_injector()->make( ViewFactory::class );
		$this->assertInstanceOf( ViewFactory::class, $factory );

		$age = PostAge::between(
			new DateTimeImmutable( '2023-10-23 12:00:00 UTC' ),
			new DateTimeImmutable( '2026-10-23 12:00:00 UTC' )
		);

		$this->assertStringContainsString(
			'This post was last updated 3 years ago',
			$factory->create( 'views/outdated-post-notice' )->render( [ 'age' => $age ] )
		);
	}

	/**
	 * Register the composition root's services and return its injector.
	 */
	private function make_injector(): Injector {
		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$injector = $plugin->get_container()->get( 'mwpd.content_freshness.' . ServiceBasedPlugin::INJECTOR_ID );
		$this->assertInstanceOf( Injector::class, $injector );

		return $injector;
	}
}
