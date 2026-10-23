<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Freshness;

use DateTimeImmutable;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use MWPD\ContentFreshness\Freshness\MaxAge;
use stdClass;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Freshness\MaxAge
 */
final class MaxAgeTest extends TestCase {

	public function test_it_keeps_a_number_of_days_in_range(): void {
		$this->assertSame( 1, MaxAge::from_days( MaxAge::MINIMUM_DAYS )->in_days() );
		$this->assertSame( 365, MaxAge::from_days( MaxAge::DEFAULT_DAYS )->in_days() );
		$this->assertSame( 36500, MaxAge::from_days( MaxAge::MAXIMUM_DAYS )->in_days() );
	}

	/**
	 * @return array<string,array{int}>
	 */
	public static function data_days_out_of_range(): array {
		return [
			'zero'          => [ 0 ],
			'negative'      => [ -30 ],
			'above maximum' => [ MaxAge::MAXIMUM_DAYS + 1 ],
		];
	}

	/**
	 * @dataProvider data_days_out_of_range
	 */
	public function test_it_rejects_a_number_of_days_out_of_range( int $days ): void {
		$this->expectException( InvalidMaxAge::class );
		$this->expectExceptionMessage(
			"A maximum age of {$days} days is out of range. Use a number of days from 1 to 36500."
		);

		MaxAge::from_days( $days );
	}

	/**
	 * @return array<string,array{mixed,int}>
	 */
	public static function data_valid_values(): array {
		return [
			'integer'                => [ 90, 90 ],
			'string of digits'       => [ '90', 90 ],
			'surrounding whitespace' => [ " 90 \n", 90 ],
			'leading zeros'          => [ '0090', 90 ],
		];
	}

	/**
	 * @dataProvider data_valid_values
	 *
	 * @param mixed $value Value WordPress hands over.
	 */
	public function test_it_normalizes_a_value_wordpress_hands_over( $value, int $days ): void {
		$this->assertSame( $days, MaxAge::from_value( $value )->in_days() );
	}

	/**
	 * @return array<string,array{mixed}>
	 */
	public static function data_invalid_values(): array {
		return [
			'empty string'   => [ '' ],
			'decimal'        => [ '30.5' ],
			'with a unit'    => [ '30 days' ],
			'negative'       => [ '-30' ],
			'float'          => [ 30.0 ],
			'boolean'        => [ true ],
			'null'           => [ null ],
			'array'          => [ [ 30 ] ],
			'object'         => [ new stdClass() ],
			'too many digit' => [ '1234567890' ],
		];
	}

	/**
	 * @dataProvider data_invalid_values
	 *
	 * @param mixed $value Value WordPress hands over.
	 */
	public function test_it_rejects_a_value_that_is_not_a_whole_number_of_days( $value ): void {
		$this->expectException( InvalidMaxAge::class );

		MaxAge::from_value( $value );
	}

	public function test_a_string_of_digits_out_of_range_is_rejected_for_its_range(): void {
		$this->expectException( InvalidMaxAge::class );
		$this->expectExceptionMessage( 'A maximum age of 0 days is out of range.' );

		MaxAge::from_value( '0' );
	}

	public function test_it_counts_back_from_a_moment(): void {
		$moment = new DateTimeImmutable( '2026-10-23 12:00:00 UTC' );

		$this->assertSame(
			'2026-09-23 12:00:00',
			MaxAge::from_days( 30 )->before( $moment )->format( 'Y-m-d H:i:s' )
		);
	}
}
