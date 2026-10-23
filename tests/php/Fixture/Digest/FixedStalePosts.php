<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Digest;

use DateTimeImmutable;
use MWPD\ContentFreshness\Digest\StalePosts;
use WP_Post;

/**
 * Stale posts the test chooses, recording what it was asked for.
 */
final class FixedStalePosts implements StalePosts {

	/**
	 * @var list<WP_Post>
	 */
	private array $posts;

	public ?DateTimeImmutable $moment = null;

	public ?int $limit = null;

	/**
	 * @param list<WP_Post> $posts Posts to return.
	 */
	public function __construct( array $posts ) {
		$this->posts = $posts;
	}

	public function last_updated_before( DateTimeImmutable $moment, int $limit ): array {
		$this->moment = $moment;
		$this->limit  = $limit;

		return $this->posts;
	}
}
