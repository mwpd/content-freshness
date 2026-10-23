<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteContext;
use MWPD\ContentFreshness\Infrastructure\Registration\RegistrationReport;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteActivateable;

/**
 * A site-scoped service that reads the registration report, which it is
 * built with in the fresh graph of each site.
 */
final class ReportReadingSiteService implements Service, SiteActivateable {

	public static ?RegistrationReport $read = null;

	private RegistrationReport $report;

	public function __construct( RegistrationReport $report ) {
		$this->report = $report;
	}

	public function activate_site( SiteContext $context ): void {
		self::$read = $this->report;
	}
}
