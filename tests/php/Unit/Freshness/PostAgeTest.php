<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Freshness;

use DateTimeImmutable;
use MWPD\ContentFreshness\Freshness\PostAge;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Freshness\PostAge
 */
final class PostAgeTest extends TestCase {

	public function test_it_keeps_both_moments(): void {
		$last_updated = new DateTimeImmutable( '2026-01-01 00:00:00 UTC' );
		$measured_at  = new DateTimeImmutable( '2026-10-23 12:00:00 UTC' );

		$age = PostAge::between( $last_updated, $measured_at );

		$this->assertSame( $last_updated, $age->last_updated() );
		$this->assertSame( $measured_at, $age->measured_at() );
	}

	public function test_it_counts_whole_days(): void {
		$age = PostAge::between(
			new DateTimeImmutable( '2026-10-20 12:00:01 UTC' ),
			new DateTimeImmutable( '2026-10-23 12:00:00 UTC' )
		);

		$this->assertSame( 2, $age->in_days() );
	}

	public function test_it_compares_moments_across_timezones(): void {
		$age = PostAge::between(
			new DateTimeImmutable( '2026-10-22 12:00:00 UTC' ),
			new DateTimeImmutable( '2026-10-23 14:00:00 +02:00' )
		);

		$this->assertSame( 1, $age->in_days() );
	}

	public function test_a_post_updated_after_the_measurement_is_zero_days_old(): void {
		$age = PostAge::between(
			new DateTimeImmutable( '2026-10-24 12:00:00 UTC' ),
			new DateTimeImmutable( '2026-10-23 12:00:00 UTC' )
		);

		$this->assertSame( 0, $age->in_days() );
	}
}
