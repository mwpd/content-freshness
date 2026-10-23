<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Freshness;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use MWPD\ContentFreshness\Exception\FailedToDetermineAge;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Tests\Fixture\Freshness\FrozenClock;
use MWPD\ContentFreshness\Tests\Unit\TestCase;
use stdClass;
use WP_Post;

/**
 * @covers \MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy
 */
final class OptionFreshnessPolicyTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		// Like WordPress, read the last update from the post's GMT field.
		Functions\when( 'get_post_datetime' )->alias(
			static function ( WP_Post $post ) {
				if ( '0000-00-00 00:00:00' === $post->post_modified_gmt ) {
					return false;
				}

				return new DateTimeImmutable( $post->post_modified_gmt, new DateTimeZone( 'UTC' ) );
			}
		);
	}

	public function test_a_post_updated_before_the_maximum_age_is_outdated(): void {
		$this->store_max_age( 30 );
		$policy = new OptionFreshnessPolicy( new FrozenClock( '2026-10-23 12:00:00 UTC' ) );

		$this->assertTrue( $policy->is_outdated( $this->post_updated_at( '2026-09-23 11:59:59' ) ) );
		$this->assertFalse( $policy->is_outdated( $this->post_updated_at( '2026-09-23 12:00:00' ) ) );
		$this->assertFalse( $policy->is_outdated( $this->post_updated_at( '2026-10-22 08:00:00' ) ) );
	}

	public function test_it_tells_the_moment_before_which_posts_are_outdated(): void {
		$this->store_max_age( 30 );
		$policy = new OptionFreshnessPolicy( new FrozenClock( '2026-10-23 12:00:00 UTC' ) );

		$this->assertSame( '2026-09-23 12:00:00', $policy->outdated_before()->format( 'Y-m-d H:i:s' ) );
	}

	public function test_it_measures_the_age_against_its_clock(): void {
		$policy = new OptionFreshnessPolicy( new FrozenClock( '2026-10-23 12:00:00 UTC' ) );

		$age = $policy->age_of( $this->post_updated_at( '2023-10-23 12:00:00' ) );

		$this->assertSame( 1096, $age->in_days() );
		$this->assertSame( '2026-10-23 12:00:00', $age->measured_at()->format( 'Y-m-d H:i:s' ) );
	}

	public function test_it_reads_the_option_once(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( OptionFreshnessPolicy::OPTION_NAME, 365 )
			->andReturn( '30' );
		$policy = new OptionFreshnessPolicy( new FrozenClock() );

		$policy->is_outdated( $this->post_updated_at( '2026-01-01 00:00:00' ) );
		$policy->is_outdated( $this->post_updated_at( '2026-10-01 00:00:00' ) );
		$policy->outdated_before();
	}

	public function test_it_uses_a_year_until_the_site_sets_a_maximum_age(): void {
		// get_option() returns the default it is given when the option is missing.
		Functions\when( 'get_option' )->returnArg( 2 );
		$policy = new OptionFreshnessPolicy( new FrozenClock( '2026-10-23 12:00:00 UTC' ) );

		$this->assertSame( '2025-10-23 12:00:00', $policy->outdated_before()->format( 'Y-m-d H:i:s' ) );
	}

	public function test_a_stored_value_that_is_not_a_maximum_age_fails_loudly(): void {
		$this->store_max_age( 'forever' );
		$policy = new OptionFreshnessPolicy( new FrozenClock() );

		$this->expectException( InvalidMaxAge::class );

		$policy->is_outdated( $this->post_updated_at( '2026-01-01 00:00:00' ) );
	}

	public function test_a_post_without_a_last_update_cannot_be_measured(): void {
		$this->store_max_age( 30 );
		$policy = new OptionFreshnessPolicy( new FrozenClock() );
		$post   = $this->post_updated_at( '0000-00-00 00:00:00' );

		$this->expectException( FailedToDetermineAge::class );
		$this->expectExceptionMessage( 'WordPress cannot tell when post 42 was last updated' );

		$policy->is_outdated( $post );
	}

	/**
	 * @param mixed $value Stored value of the option.
	 */
	private function store_max_age( $value ): void {
		Functions\when( 'get_option' )->justReturn( $value );
	}

	private function post_updated_at( string $modified_gmt ): WP_Post {
		$fields                    = new stdClass();
		$fields->ID                = 42;
		$fields->post_modified_gmt = $modified_gmt;

		return new WP_Post( $fields );
	}
}
