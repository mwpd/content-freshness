<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Freshness;

use DateTimeImmutable;
use MWPD\ContentFreshness\Exception\FailedToDetermineAge;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Freshness\PostAge;
use WP_Post;

/**
 * A freshness policy that cannot answer, to test how failures degrade.
 */
final class FailingFreshnessPolicy implements FreshnessPolicy {

	public function is_outdated( WP_Post $post ): bool {
		throw FailedToDetermineAge::for_post( $post->ID );
	}

	public function age_of( WP_Post $post ): PostAge {
		throw FailedToDetermineAge::for_post( $post->ID );
	}

	public function outdated_before(): DateTimeImmutable {
		throw InvalidMaxAge::from_value( 'forever' );
	}
}
