<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Exception;

use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Exception\FailedToScheduleDigest;
use RuntimeException;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Exception\FailedToScheduleDigest
 */
final class FailedToScheduleDigestTest extends TestCase {

	public function test_it_names_the_step_and_the_reason(): void {
		$scheduling = FailedToScheduleDigest::when_scheduling( 'No such schedule.' );

		$this->assertInstanceOf( RuntimeException::class, $scheduling );
		$this->assertInstanceOf( ContentFreshnessException::class, $scheduling );
		$this->assertSame( 'The weekly digest could not be scheduled: No such schedule.', $scheduling->getMessage() );
		$this->assertSame(
			'The weekly digest could not be unscheduled: Not saved.',
			FailedToScheduleDigest::when_clearing( 'Not saved.' )->getMessage()
		);
	}
}
