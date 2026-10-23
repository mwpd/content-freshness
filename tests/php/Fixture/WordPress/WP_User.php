<?php

declare( strict_types=1 );

/**
 * Stand-in for WordPress's user class, for tests that run without WordPress.
 */
final class WP_User {

	/**
	 * ID of the user.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Email address of the user.
	 *
	 * @var string
	 */
	public $user_email = '';
}
