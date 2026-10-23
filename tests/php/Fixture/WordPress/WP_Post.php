<?php

declare( strict_types=1 );

/**
 * Stand-in for WordPress's post class, for tests that run without WordPress.
 *
 * Like core's class, it copies the fields of the object it is given.
 */
final class WP_Post {

	/**
	 * ID of the post.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Type of the post.
	 *
	 * @var string
	 */
	public $post_type = 'post';

	/**
	 * Status of the post.
	 *
	 * @var string
	 */
	public $post_status = 'publish';

	/**
	 * Title of the post.
	 *
	 * @var string
	 */
	public $post_title = '';

	/**
	 * Date of the post.
	 *
	 * @var string
	 */
	public $post_date = '0000-00-00 00:00:00';

	/**
	 * Date of the last update of the post, in UTC.
	 *
	 * @var string
	 */
	public $post_modified_gmt = '0000-00-00 00:00:00';

	/**
	 * Instantiate a WP_Post object.
	 *
	 * @param object $post Fields of the post.
	 */
	public function __construct( object $post ) {
		foreach ( get_object_vars( $post ) as $key => $value ) {
			$this->$key = $value;
		}
	}
}
