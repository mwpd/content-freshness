<?php

declare( strict_types=1 );

/**
 * Stand-in for WordPress's error class, for tests that run without WordPress.
 *
 * The rule tests need it to see classes that extend it, and the service tests
 * to hand out an error with a message, as WordPress functions do.
 */
class WP_Error {

	/**
	 * Message of the error.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Instantiate a WP_Error object.
	 *
	 * @param string|int $code    Error code.
	 * @param string     $message Error message.
	 */
	public function __construct( $code = '', $message = '' ) {
		$this->message = $message;
	}

	/**
	 * Get the error message.
	 *
	 * @return string Error message.
	 */
	public function get_error_message() {
		return $this->message;
	}
}
