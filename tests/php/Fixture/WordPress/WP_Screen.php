<?php

declare( strict_types=1 );

/**
 * Stand-in for WordPress's admin screen class, for tests that run without
 * WordPress.
 */
final class WP_Screen {

	/**
	 * ID of the screen.
	 *
	 * @var string
	 */
	public $id = '';
}
