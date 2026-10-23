<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Freshness;

use DateTimeImmutable;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Freshness\PostAge;
use WP_Post;

/**
 * A freshness policy with its answers set by the test.
 *
 * Bound in place of OptionFreshnessPolicy, it shows that the services depend
 * on the FreshnessPolicy interface alone: none of them changes to use it.
 */
final class FixedFreshnessPolicy implements FreshnessPolicy {

	public const LAST_UPDATED = '2023-10-23 12:00:00 UTC';

	public const MEASURED_AT = '2026-10-23 12:00:00 UTC';

	private bool $outdated;

	/**
	 * @var list<int>
	 */
	private array $fresh_post_ids;

	/**
	 * @param bool      $outdated       Whether posts are outdated.
	 * @param list<int> $fresh_post_ids Posts that are fresh whatever their age, as an evergreen rule would.
	 */
	public function __construct( bool $outdated = true, array $fresh_post_ids = [] ) {
		$this->outdated       = $outdated;
		$this->fresh_post_ids = $fresh_post_ids;
	}

	public function is_outdated( WP_Post $post ): bool {
		return $this->outdated && ! in_array( $post->ID, $this->fresh_post_ids, true );
	}

	public function age_of( WP_Post $post ): PostAge {
		return PostAge::between(
			new DateTimeImmutable( self::LAST_UPDATED ),
			new DateTimeImmutable( self::MEASURED_AT )
		);
	}

	public function outdated_before(): DateTimeImmutable {
		return new DateTimeImmutable( '2025-10-23 12:00:00 UTC' );
	}
}
