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

namespace MWPD\ContentFreshness\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Composer\Autoload\ClassLoader;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\ContentFreshnessPluginFactory;
use MWPD\ContentFreshness\ContentFreshnessUninstallHook;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\DelayedLifecycleService;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\LifecycleRecorder;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\Multisite;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\UninstallService;

/**
 * The way WordPress uninstalls the plugin: it includes the main plugin file,
 * then calls the callback activation stored. Each test includes the main file
 * in a process of its own, as WordPress does in its own request.
 *
 * @covers \MWPD\ContentFreshness\ContentFreshnessUninstallHook
 */
final class UninstallBridgeTest extends TestCase {

	private const MAIN_FILE = __DIR__ . '/../../../content-freshness.php';

	/**
	 * Callbacks the main file registered, by hook.
	 *
	 * @var array<string,callable>
	 */
	private array $callbacks = [];

	private LifecycleRecorder $recorder;

	protected function set_up() {
		parent::set_up();

		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/' );
		}

		$this->recorder  = new LifecycleRecorder();
		$this->callbacks = [];

		// The plugin's own uninstall services remove the setting, which the
		// installation models, and any digest left in WP-Cron.
		Functions\when( 'wp_clear_scheduled_hook' )->justReturn( 0 );
		Functions\when( 'is_wp_error' )->justReturn( false );

		foreach ( [
			'register_activation_hook'   => 'activate',
			'register_deactivation_hook' => 'deactivate',
		] as $function => $hook ) {
			Functions\when( $function )->alias(
				function ( string $file, callable $callback ) use ( $hook ): void {
					$this->callbacks[ $hook ] = $callback;
				}
			);
		}

		Actions\expectAdded( 'plugins_loaded' )->whenHappen(
			function ( callable $callback ): void {
				$this->callbacks['plugins_loaded'] = $callback;
			}
		);

		// A plugin loaded earlier adds an uninstall service and an unrelated
		// one, which is built and registered in a normal request.
		$recorder = $this->recorder;
		Filters\expectApplied( 'mwpd.content_freshness.services' )->andReturnUsing(
			static function ( array $services ): array {
				return $services + [
					'acme.delayed'   => DelayedLifecycleService::class,
					'acme.uninstall' => UninstallService::class,
				];
			}
		);
		Filters\expectApplied( 'mwpd.content_freshness.delegations' )->andReturnUsing(
			static function ( array $delegations ) use ( $recorder ): array {
				return $delegations + [ LifecycleRecorder::class => static fn(): LifecycleRecorder => $recorder ];
			}
		);
	}

	/**
	 * Include the main plugin file in the method's scope, as WordPress does.
	 */
	private function include_main_file(): void {
		include self::MAIN_FILE;
	}

	/**
	 * Uninstall the way WordPress does it, after including the main file and
	 * without defining WP_UNINSTALL_PLUGIN, and fire plugins_loaded
	 * afterwards: only the uninstall service is built, and nothing registers.
	 */
	private function uninstall_and_fire_plugins_loaded(): void {
		$this->assertFalse( defined( 'WP_UNINSTALL_PLUGIN' ) );

		$this->include_main_file();
		ContentFreshnessUninstallHook::uninstall();
		( $this->callbacks['plugins_loaded'] )();

		$this->assertSame( [ 'construct:uninstall@1', 'uninstall_site:uninstall@1' ], $this->recorder->events() );
		$this->assertFalse(
			ContentFreshnessPluginFactory::create()->get_container()->has( 'mwpd.content_freshness.injector' ),
			'The services were registered.'
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uninstalling_builds_only_the_uninstall_services(): void {
		Multisite::single_site()->install();

		$this->uninstall_and_fire_plugins_loaded();
	}

	/**
	 * The bundled autoloader alone serves the plugin's classes, as it does in
	 * a copy of the plugin without vendor/.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_uninstalling_works_with_the_bundled_autoloader_alone(): void {
		Multisite::single_site()->install();

		// Composer keeps serving everything else, but no longer the plugin's
		// own namespace, so the main file falls back to the bundled autoloader.
		$this->assertFalse( class_exists( ContentFreshnessPlugin::class, false ) );

		foreach ( ClassLoader::getRegisteredLoaders() as $loader ) {
			$loader->setPsr4( 'MWPD\\ContentFreshness\\', [] );
			$this->assertFalse( $loader->findFile( ContentFreshnessUninstallHook::class ) );
		}

		$this->uninstall_and_fire_plugins_loaded();
	}

	/**
	 * Activating the plugin through its main file stores the bridge as the
	 * uninstall callback: a pair of strings that survives serialization and
	 * names a public static method.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_activation_stores_the_bridge_as_the_uninstall_callback(): void {
		$installation = Multisite::with_networks(
			[
				1 => [ 1, 2 ],
				2 => [ 3 ],
			],
			2
		);
		$installation->install();
		Functions\when( 'flush_rewrite_rules' )->justReturn( null );
		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		// The stale content digest is scheduled already on every site.
		Functions\when( 'wp_next_scheduled' )->justReturn( 1700000000 );

		$this->include_main_file();
		( $this->callbacks['activate'] )( true );

		$basename = \plugin_basename( (string) realpath( self::MAIN_FILE ) );
		$expected = [ $basename => [ ContentFreshnessUninstallHook::class, 'uninstall' ] ];

		foreach ( [ 2, 1, 3 ] as $site ) {
			$this->assertSame( $expected, $installation->option( $site, 'uninstall_plugins' ), "Site {$site}" );
		}
	}
}
