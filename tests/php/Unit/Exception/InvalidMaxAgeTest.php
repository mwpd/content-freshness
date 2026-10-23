<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Exception;

use InvalidArgumentException;
use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Exception\InvalidMaxAge
 */
final class InvalidMaxAgeTest extends TestCase {

	public function test_it_names_the_allowed_range(): void {
		$exception = InvalidMaxAge::from_days( 0, 1, 36500 );

		$this->assertInstanceOf( InvalidArgumentException::class, $exception );
		$this->assertInstanceOf( ContentFreshnessException::class, $exception );
		$this->assertSame(
			'A maximum age of 0 days is out of range. Use a number of days from 1 to 36500.',
			$exception->getMessage()
		);
	}

	public function test_it_names_the_value_that_is_not_a_number(): void {
		$this->assertSame(
			'The maximum age "forever" is not a whole number of days.',
			InvalidMaxAge::from_value( 'forever' )->getMessage()
		);
		$this->assertSame(
			'The maximum age "{array}" is not a whole number of days.',
			InvalidMaxAge::from_value( [ 30 ] )->getMessage()
		);
	}
}
