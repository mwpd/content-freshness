<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\Registration\ObservesRegistration;
use MWPD\ContentFreshness\Infrastructure\Registration\RegistrationReport;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that observes the registration in every request, and keeps the
 * report it receives for the test to read.
 */
final class ObservingService implements Service, ObservesRegistration {

	public RegistrationReport $report;

	public function __construct( RegistrationReport $report ) {
		$this->report = $report;
	}

	public static function observes_registration(): bool {
		return true;
	}
}
