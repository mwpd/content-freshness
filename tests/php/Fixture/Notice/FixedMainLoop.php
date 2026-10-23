<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Notice;

use MWPD\ContentFreshness\Exception\FailedToFindPost;
use MWPD\ContentFreshness\Notice\MainLoop;
use WP_Post;

/**
 * A main loop whose state the test sets.
 */
final class FixedMainLoop implements MainLoop {

	private bool $rendering_queried_post;

	private ?WP_Post $queried_post;

	public function __construct( bool $rendering_queried_post, ?WP_Post $queried_post ) {
		$this->rendering_queried_post = $rendering_queried_post;
		$this->queried_post           = $queried_post;
	}

	public function is_rendering_queried_post(): bool {
		return $this->rendering_queried_post;
	}

	public function queried_post(): WP_Post {
		if ( null === $this->queried_post ) {
			throw FailedToFindPost::for_main_query();
		}

		return $this->queried_post;
	}
}
