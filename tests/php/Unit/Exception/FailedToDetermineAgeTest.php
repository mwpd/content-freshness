<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Exception;

use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Exception\FailedToDetermineAge;
use RuntimeException;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Exception\FailedToDetermineAge
 */
final class FailedToDetermineAgeTest extends TestCase {

	public function test_it_names_the_post(): void {
		$exception = FailedToDetermineAge::for_post( 42 );

		$this->assertInstanceOf( RuntimeException::class, $exception );
		$this->assertInstanceOf( ContentFreshnessException::class, $exception );
		$this->assertSame(
			'WordPress cannot tell when post 42 was last updated, so its age is unknown.',
			$exception->getMessage()
		);
	}
}
