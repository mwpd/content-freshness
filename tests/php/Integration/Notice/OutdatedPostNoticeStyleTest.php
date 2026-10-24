<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration\Notice;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use MWPD\ContentFreshness\Infrastructure\Asset\Style;
use MWPD\ContentFreshness\Infrastructure\Service\DebugMode;
use MWPD\ContentFreshness\Tests\Fixture\Asset\RecordingAssets;
use MWPD\ContentFreshness\Tests\Fixture\FixedDebugMode;
use MWPD\ContentFreshness\Tests\Integration\TestCase;
use stdClass;
use WP_Post;

/**
 * The notice's default style, loaded by the real composition root: in the
 * head of an outdated post, and nowhere else.
 */
final class OutdatedPostNoticeStyleTest extends TestCase {

	private const PLUGIN_ROOT = __DIR__ . '/../../../..';

	private const PLUGIN_URL = 'https://example.com/wp-content/plugins/content-freshness';

	private const HANDLE = 'mwpd-content-freshness-notice';

	private const ENQUEUE_ACTIONS = [
		'wp_enqueue_scripts',
		'admin_enqueue_scripts',
		'enqueue_block_editor_assets',
		'login_enqueue_scripts',
	];

	private RecordingAssets $assets;

	/**
	 * Callbacks added to the enqueue actions, by action.
	 *
	 * @var array<string,list<callable>>
	 */
	private array $callbacks = [];

	protected function set_up() {
		parent::set_up();

		$this->assets    = new RecordingAssets();
		$this->callbacks = [];

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'did_action' )->alias( static fn( string $action ): bool => $action === 'wp' );
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nonexistent/child-theme' );
		Functions\when( 'get_template_directory' )->justReturn( '/nonexistent/parent-theme' );
		Functions\when( 'plugins_url' )->justReturn( self::PLUGIN_URL );

		// The main query asked for post 42, last updated three years ago.
		Functions\when( 'get_queried_object' )->justReturn( $this->post( '2023-10-23 12:00:00' ) );
		Functions\when( 'get_post_datetime' )->alias(
			static fn( WP_Post $post ): DateTimeImmutable => new DateTimeImmutable(
				$post->post_modified_gmt,
				new DateTimeZone( 'UTC' )
			)
		);
		Functions\when( 'current_datetime' )->justReturn( new DateTimeImmutable( '2026-10-23 12:00:00 UTC' ) );

		// Read the one header the plugin asks for from the real main file, as
		// WordPress would.
		Functions\when( 'get_file_data' )->alias(
			static fn( string $file ): array => [ 'Version' => self::version_header_of( $file ) ]
		);

		foreach ( self::ENQUEUE_ACTIONS as $action ) {
			Actions\expectAdded( $action )
				->zeroOrMoreTimes()
				->whenHappen(
					function ( callable $callback ) use ( $action ): void {
						$this->callbacks[ $action ][] = $callback;
					}
				);
		}
	}

	public function test_the_style_loads_in_the_head_of_an_outdated_post(): void {
		Functions\when( 'get_option' )->justReturn( '365' );
		$this->request( true );

		$this->run_action( 'wp_enqueue_scripts' );

		$this->assertSame( [ self::HANDLE ], $this->assets->handles() );

		$style = $this->assets->enqueued[0];
		$this->assertInstanceOf( Style::class, $style );
		$path = $style->get_path();
		$this->assertNotNull( $path, 'The notice declares a file, which a theme can dequeue by handle.' );
		$this->assertFileExists( self::PLUGIN_ROOT . '/' . $path->to_string(), 'The stylesheet ships.' );

		$location = $this->assets->locations[0];
		$this->assertSame( self::PLUGIN_URL . '/assets/css/notice.css', $location->get_url( $path ) );
		$this->assertSame(
			self::version_header_of( self::PLUGIN_ROOT . '/content-freshness.php' ),
			$location->get_version( null ),
			'The version comes from the main file, so every release busts the cache.'
		);
	}

	public function test_no_style_loads_on_a_fresh_post(): void {
		Functions\when( 'get_option' )->justReturn( '3650' );
		$this->request( true );

		$this->run_action( 'wp_enqueue_scripts' );

		$this->assertSame( [], $this->assets->handles() );
	}

	/**
	 * The notice is Conditional on a single post, so on an archive it is never
	 * built, and the plugin has no service to ask for assets: the queried
	 * object is not even looked at.
	 */
	public function test_no_style_loads_on_an_archive_where_the_notice_is_not_needed(): void {
		Functions\when( 'get_option' )->justReturn( '365' );
		Functions\expect( 'get_queried_object' )->never();
		$this->request( false );

		foreach ( self::ENQUEUE_ACTIONS as $action ) {
			$this->run_action( $action, 'index.php' );
		}

		$this->assertSame( [], $this->assets->handles() );
	}

	/**
	 * A broken setting makes the policy throw while the style is declared.
	 * The plugin's enqueue listener catches it: without debug mode, the post
	 * shows without the style, as it shows without the notice.
	 */
	public function test_a_failing_policy_loads_no_style_without_debug_mode(): void {
		Functions\when( 'get_option' )->justReturn( 'forever' );
		$this->request( true, false );

		$this->run_action( 'wp_enqueue_scripts' );

		$this->assertSame( [], $this->assets->handles() );
	}

	public function test_a_failing_policy_surfaces_in_debug_mode(): void {
		Functions\when( 'get_option' )->justReturn( 'forever' );
		$this->request( true, true );

		$this->expectException( InvalidMaxAge::class );

		$this->run_action( 'wp_enqueue_scripts' );
	}

	/**
	 * Register the composition root's services for a front-end request.
	 *
	 * @param bool      $is_singular   Whether the request is for a single post.
	 * @param bool|null $is_debug_mode Optional. Debug mode to bind instead of
	 *                                 WP_DEBUG, which the tests leave undefined.
	 */
	private function request( bool $is_singular, ?bool $is_debug_mode = null ): void {
		Functions\when( 'is_singular' )->justReturn( $is_singular );

		if ( null !== $is_debug_mode ) {
			Filters\expectApplied( 'mwpd.content_freshness.bindings' )
				->once()
				->andReturnUsing(
					static function ( array $bindings ): array {
						$bindings[ DebugMode::class ] = FixedDebugMode::class;

						return $bindings;
					}
				);
			Filters\expectApplied( 'mwpd.content_freshness.arguments' )
				->once()
				->andReturnUsing(
					static function ( array $arguments ) use ( $is_debug_mode ): array {
						$arguments[ FixedDebugMode::class ] = [ 'debug_mode' => $is_debug_mode ];

						return $arguments;
					}
				);
		}

		( new ContentFreshnessPlugin( true, null, null, null, null, null, $this->assets ) )->register_services();
	}

	/**
	 * Run the callbacks added to an action, as do_action() would.
	 *
	 * @param string $action  Action to run.
	 * @param mixed  ...$args Arguments of the action.
	 */
	private function run_action( string $action, ...$args ): void {
		foreach ( $this->callbacks[ $action ] ?? [] as $callback ) {
			$callback( ...$args );
		}
	}

	/**
	 * Read the Version header of a plugin file.
	 *
	 * @param string $file Plugin file to read.
	 *
	 * @return string Version, or an empty string without the header.
	 */
	private static function version_header_of( string $file ): string {
		foreach ( (array) file( $file ) as $line ) {
			if ( preg_match( '/^\s*\*\s*Version:\s*(\S+)/', (string) $line, $match ) === 1 ) {
				return $match[1];
			}
		}

		return '';
	}

	private function post( string $modified_gmt ): WP_Post {
		$fields                    = new stdClass();
		$fields->ID                = 42;
		$fields->post_modified_gmt = $modified_gmt;

		return new WP_Post( $fields );
	}
}
