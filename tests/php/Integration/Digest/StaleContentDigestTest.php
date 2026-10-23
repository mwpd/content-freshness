<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration\Digest;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Digest\StaleContentDigest;
use MWPD\ContentFreshness\Tests\Integration\TestCase;

/**
 * The digest, built from the real composition root, through the plugin's
 * activation, registration and deactivation.
 */
final class StaleContentDigestTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\when( 'did_action' )->justReturn( false );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'flush_rewrite_rules' )->justReturn( null );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'register_uninstall_hook' )->justReturn( null );
		Functions\when( 'current_datetime' )->justReturn( new DateTimeImmutable( '2026-10-23 12:00:00 UTC' ) );
	}

	/**
	 * The cron event can fire in any request, front end included, so the
	 * digest is registered at plugins_loaded, whatever the request.
	 */
	public function test_it_hooks_the_cron_event_in_every_request(): void {
		Functions\when( 'is_admin' )->justReturn( false );

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$digest = $plugin->get_container()->get( ContentFreshnessPlugin::STALE_CONTENT_DIGEST_ID );
		$this->assertInstanceOf( StaleContentDigest::class, $digest );
		$this->assertSame( 10, has_action( StaleContentDigest::CRON_HOOK, [ $digest, 'send' ] ) );
	}
}
