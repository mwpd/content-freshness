<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Integration\PostsList;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\ContentFreshnessPlugin;
use MWPD\ContentFreshness\PostsList\PostAgeColumn;
use MWPD\ContentFreshness\Tests\Integration\TestCase;
use WP_Screen;

/**
 * The column is built from the real composition root on the Posts list, and
 * nowhere else.
 */
final class PostAgeColumnTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\when( 'is_admin' )->justReturn( true );
		Functions\when( 'did_action' )->alias(
			static fn( string $action ): bool => in_array( $action, [ 'admin_init', 'current_screen' ], true )
		);
	}

	public function test_it_is_registered_on_the_posts_list(): void {
		Functions\when( 'get_current_screen' )->justReturn( $this->screen( 'edit-post' ) );

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$this->assertTrue( $plugin->get_container()->has( ContentFreshnessPlugin::POST_AGE_COLUMN_ID ) );
		$column = $plugin->get_container()->get( ContentFreshnessPlugin::POST_AGE_COLUMN_ID );
		$this->assertInstanceOf( PostAgeColumn::class, $column );
		$this->assertSame( 10, has_filter( 'manage_post_posts_columns', [ $column, 'add_column' ] ) );
	}

	public function test_it_is_not_built_on_other_screens(): void {
		Functions\when( 'get_current_screen' )->justReturn( $this->screen( 'dashboard' ) );

		$plugin = new ContentFreshnessPlugin();
		$plugin->register_services();

		$this->assertFalse( $plugin->get_container()->has( ContentFreshnessPlugin::POST_AGE_COLUMN_ID ) );
		$this->assertFalse( has_filter( 'manage_post_posts_columns' ) );
	}

	private function screen( string $id ): WP_Screen {
		$screen     = new WP_Screen();
		$screen->id = $id;

		return $screen;
	}
}
