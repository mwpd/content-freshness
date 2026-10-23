<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Digest;

use DateTimeImmutable;
use MWPD\ContentFreshness\Digest\StalePosts;
use WP_Post;

/**
 * Stale posts the test chooses, paged like a query, recording what it was
 * asked for.
 */
final class FixedStalePosts implements StalePosts {

	/**
	 * @var list<WP_Post>
	 */
	private array $posts;

	public ?DateTimeImmutable $moment = null;

	public ?int $limit = null;

	/**
	 * Offsets asked for, in order.
	 *
	 * @var list<int>
	 */
	public array $offsets = [];

	/**
	 * @param list<WP_Post> $posts Posts to return.
	 */
	public function __construct( array $posts ) {
		$this->posts = $posts;
	}

	public function last_updated_before( DateTimeImmutable $moment, int $limit, int $offset ): array {
		$this->moment    = $moment;
		$this->limit     = $limit;
		$this->offsets[] = $offset;

		return array_slice( $this->posts, $offset, $limit );
	}
}
