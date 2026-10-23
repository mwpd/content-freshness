<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Integration;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Digest\StaleContentDigest;
use MWPD\ContentFreshness\Infrastructure\QueryMonitor\RegistrationPanel;
use MWPD\ContentFreshness\Infrastructure\Registration\RegistrationRecord;
use MWPD\ContentFreshness\Infrastructure\Registration\SimpleRegistrationLog;
use MWPD\ContentFreshness\Notice\OutdatedPostNotice;
use MWPD\ContentFreshness\Tests\Fixture\QueryMonitorInstallation;
use QM_Collectors;
use QM_Output_Html;

/**
 * The composition root as it ships, with the optional Query Monitor panel
 * declared through one service entry: the panel renders the real template,
 * views/query-monitor-registration, from the root's own wiring. Without the
 * entry, nothing of it happens, with or without Query Monitor.
 *
 * Every test runs in a process of its own, so that the Query Monitor
 * stand-ins one test loads do not leak into another.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class QueryMonitorPanelTest extends TestCase {

	private const PANEL_ID = 'mwpd-content-freshness-registration';

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nonexistent/child-theme' );
		Functions\when( 'get_template_directory' )->justReturn( '/nonexistent/parent-theme' );
	}

	public function test_the_declared_panel_shows_the_services_of_the_composition_root(): void {
		( new QueryMonitorInstallation() )->install()->run();

		Filters\expectApplied( 'mwpd.content_freshness.services' )->once()->andReturnUsing(
			static fn( array $services ): array => $services
				+ [ ContentFreshnessPlugin::REGISTRATION_PANEL_ID => RegistrationPanel::class ]
		);

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$panel = $plugin->get_container()->get( ContentFreshnessPlugin::REGISTRATION_PANEL_ID );
		$this->assertInstanceOf( RegistrationPanel::class, $panel );

		$panel->add_collector();
		$outputters = $panel->add_output( [] );
		$this->assertIsArray( $outputters );
		$output = $outputters[ self::PANEL_ID ];
		$this->assertInstanceOf( QM_Output_Html::class, $output );
		$this->assertNotNull( QM_Collectors::get( self::PANEL_ID ) );

		$html = (string) $output->get_output();

		$this->assertStringContainsString( 'id="qm-' . self::PANEL_ID . '"', $html );
		$this->assertStringContainsString( 'Content Freshness services', $html );
		$this->assertMatchesRegularExpression(
			'~<code>' . preg_quote( ContentFreshnessPlugin::OUTDATED_POST_NOTICE_ID, '~' ) . '</code>.*?<code>'
				. preg_quote( OutdatedPostNotice::class, '~' ) . '</code>'
				. '.*?Waiting for an action.*?Awaited actions: wp~s',
			$html
		);
		$this->assertMatchesRegularExpression(
			'~<code>' . preg_quote( ContentFreshnessPlugin::STALE_CONTENT_DIGEST_ID, '~' ) . '</code>.*?<code>'
				. preg_quote( StaleContentDigest::class, '~' ) . '</code>.*?Registered~s',
			$html
		);
	}

	/**
	 * @dataProvider data_query_monitor
	 *
	 * @param bool $query_monitor_runs Whether Query Monitor runs.
	 */
	public function test_without_the_entry_nothing_of_the_panel_happens( bool $query_monitor_runs ): void {
		if ( $query_monitor_runs ) {
			( new QueryMonitorInstallation() )->install()->run();
		}

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$this->assertFalse( $plugin->get_container()->has( ContentFreshnessPlugin::REGISTRATION_PANEL_ID ) );
		$this->assertFalse( class_exists( RegistrationPanel::class, false ) );
		$this->assertFalse( class_exists( SimpleRegistrationLog::class, false ) );
		$this->assertFalse( class_exists( RegistrationRecord::class, false ) );
		$this->assertFalse( has_filter( 'qm/outputter/html' ) );
		$this->assertFalse( has_filter( 'qm/output/menus' ) );
	}

	/**
	 * @return array<string,array{bool}>
	 */
	public static function data_query_monitor(): array {
		return [
			'Query Monitor runs'          => [ true ],
			'Query Monitor is not loaded' => [ false ],
		];
	}
}
