<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Freshness;

use DateTimeImmutable;
use MWPD\ContentFreshness\Freshness\Clock;

/**
 * A clock that stands still, so ages in tests do not depend on when they run.
 */
final class FrozenClock implements Clock {

	private DateTimeImmutable $now;

	public function __construct( string $now = '2026-10-23 12:00:00 UTC' ) {
		$this->now = new DateTimeImmutable( $now );
	}

	public function now(): DateTimeImmutable {
		return $this->now;
	}
}
