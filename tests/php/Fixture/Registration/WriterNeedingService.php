<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\Registration\RegistrationLog;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that asks for the writer of the registration log, which services
 * never receive.
 */
final class WriterNeedingService implements Service {

	public RegistrationLog $log;

	public function __construct( RegistrationLog $log ) {
		$this->log = $log;
	}
}
