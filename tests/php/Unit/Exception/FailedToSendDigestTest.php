<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Exception;

use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Exception\FailedToSendDigest;
use RuntimeException;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Exception\FailedToSendDigest
 */
final class FailedToSendDigestTest extends TestCase {

	public function test_it_names_the_recipients(): void {
		$exception = FailedToSendDigest::to_recipients( [ 'a@example.com', 'b@example.com' ] );

		$this->assertInstanceOf( RuntimeException::class, $exception );
		$this->assertInstanceOf( ContentFreshnessException::class, $exception );
		$this->assertSame(
			'The stale content digest could not be sent to a@example.com, b@example.com.'
			. ' Check that the site can send email.',
			$exception->getMessage()
		);
	}
}
