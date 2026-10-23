<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Conditional;
use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\SiteActivateable;
use MWPD\ContentFreshness\Infrastructure\SiteInitializable;
use MWPD\ContentFreshness\Infrastructure\SiteUninitializable;
use MWPD\ContentFreshness\Infrastructure\SiteUninstallable;

/**
 * Only needed in a site whose own option says so, which is only known in that site.
 */
final class ConditionalScopedService implements
	Service,
	Conditional,
	SiteActivateable,
	SiteInitializable,
	SiteUninitializable,
	SiteUninstallable {

	use RecordsScopedLifecycle;

	private const NAME = 'conditional';

	public const NEEDED_OPTION = 'mwpd_content_freshness_needed';

	public static function is_needed(): bool {
		return 'yes' === \get_option( self::NEEDED_OPTION );
	}
}
