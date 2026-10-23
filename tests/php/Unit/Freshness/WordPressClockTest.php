<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Freshness;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use MWPD\ContentFreshness\Freshness\WordPressClock;
use MWPD\ContentFreshness\Tests\Unit\TestCase;

/**
 * @covers \MWPD\ContentFreshness\Freshness\WordPressClock
 */
final class WordPressClockTest extends TestCase {

	public function test_it_tells_the_time_wordpress_tells(): void {
		$now = new DateTimeImmutable( '2026-10-23 14:00:00 +02:00' );
		Functions\expect( 'current_datetime' )->once()->andReturn( $now );

		$this->assertSame( $now, ( new WordPressClock() )->now() );
	}
}
