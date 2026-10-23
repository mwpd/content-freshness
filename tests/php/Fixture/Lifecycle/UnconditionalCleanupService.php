<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Lifecycle\NetworkContext;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteContext;
use MWPD\ContentFreshness\Infrastructure\NetworkUninstallable;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteUninstallable;

/**
 * The cleanup the uninstall guidance shows: a focused service without a
 * condition, so it runs whatever the uninstall request looks like, that only
 * deletes the plugin's own, prefixed data and does not mind if it is gone.
 */
final class UnconditionalCleanupService implements Service, SiteUninstallable, NetworkUninstallable {

	public const SITE_OPTION = 'mwpd_content_freshness_settings';

	public const NETWORK_OPTION = 'mwpd_content_freshness_network_settings';

	public function uninstall_site( SiteContext $context ): void {
		// Deleting an option that does not exist is not an error.
		\delete_option( self::SITE_OPTION );
	}

	public function uninstall_network( NetworkContext $context ): void {
		// Switching sites does not switch networks: name the network.
		\delete_network_option( $context->network_id()->to_int(), self::NETWORK_OPTION );
	}
}
