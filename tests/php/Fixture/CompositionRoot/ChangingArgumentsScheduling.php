<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Service;

final class ChangingArgumentsScheduling implements Service, Activateable {

	private int $runs = 0;

	public function activate(): void {
		wp_clear_scheduled_hook( 'fixture_arguments_event', [ $this->runs ] );
		wp_schedule_event( 1700000000, 'weekly', 'fixture_arguments_event', [ ++$this->runs ] );
	}
}
