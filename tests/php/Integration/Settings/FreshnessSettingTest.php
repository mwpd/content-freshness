<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration\Settings;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Settings\FreshnessSetting;
use MWPD\ContentFreshness\Tests\Integration\TestCase;

/**
 * The setting, built from the real composition root, in an admin request and
 * when the plugin is uninstalled.
 */
final class FreshnessSettingTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\stubTranslationFunctions();
		Functions\when( 'get_current_screen' )->justReturn( null );
		Functions\when( 'register_setting' )->justReturn( null );
		Functions\when( 'add_settings_field' )->justReturn( null );
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nonexistent/child-theme' );
		Functions\when( 'get_template_directory' )->justReturn( '/nonexistent/parent-theme' );
	}

	public function test_it_is_registered_once_admin_init_has_fired(): void {
		Functions\when( 'did_action' )->alias( static fn( string $action ): bool => 'admin_init' === $action );

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$this->assertInstanceOf(
			FreshnessSetting::class,
			$plugin->get_container()->get( ContentFreshnessPlugin::FRESHNESS_SETTING_ID )
		);
	}

	public function test_it_is_not_built_before_admin_init(): void {
		Functions\when( 'did_action' )->justReturn( false );

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$this->assertFalse( $plugin->get_container()->has( ContentFreshnessPlugin::FRESHNESS_SETTING_ID ) );
	}

	/**
	 * Uninstalling happens where admin_init never fires, such as WP-CLI, and
	 * still removes the option.
	 */
	public function test_uninstalling_the_plugin_removes_the_option_outside_the_admin(): void {
		Functions\when( 'did_action' )->justReturn( false );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\expect( 'delete_option' )->once()->with( OptionFreshnessPolicy::OPTION_NAME )->andReturn( true );

		( new ContentFreshnessPlugin() )->uninstall();
	}
}
