<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\Registration\RegistrationReport;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that reads the registration report without observing the
 * registration itself.
 */
final class ReportNeedingService implements Service {

	public RegistrationReport $report;

	public function __construct( RegistrationReport $report ) {
		$this->report = $report;
	}
}
