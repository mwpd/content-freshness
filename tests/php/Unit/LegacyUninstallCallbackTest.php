<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Fixture\Lifecycle\Multisite;

/**
 * A site that activated version 1.0.0 stored MWPD\ContentFreshness\uninstall()
 * as the uninstall callback, and WordPress keeps calling it until the plugin
 * is activated again. Deleting the plugin there still removes its data.
 */
final class LegacyUninstallCallbackTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_the_callback_of_version_1_0_0_still_uninstalls_the_plugin(): void {
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', '/' );
		}

		$installation = Multisite::single_site();
		$installation->install();
		$installation->set_option( 1, OptionFreshnessPolicy::OPTION_NAME, 90 );
		Functions\when( 'register_activation_hook' )->justReturn( null );
		Functions\when( 'register_deactivation_hook' )->justReturn( null );
		Functions\when( 'wp_clear_scheduled_hook' )->justReturn( 0 );
		Functions\when( 'is_wp_error' )->justReturn( false );

		// WordPress includes the main file, then calls the stored callback.
		include dirname( __DIR__, 3 ) . '/content-freshness.php';
		$this->assertTrue( function_exists( 'MWPD\\ContentFreshness\\uninstall' ) );
		\call_user_func( 'MWPD\\ContentFreshness\\uninstall' );

		$this->assertFalse( $installation->has_option( 1, OptionFreshnessPolicy::OPTION_NAME ) );
	}
}
