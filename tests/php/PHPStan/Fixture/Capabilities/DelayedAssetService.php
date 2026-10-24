<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Asset\AssetContext;
use MWPD\ContentFreshness\Infrastructure\{DefaultRegistrationPriority, Delayed, HasAssets};
use MWPD\ContentFreshness\Infrastructure\Service;

final class DelayedAssetService implements Service, Delayed, HasAssets {
	use DefaultRegistrationPriority;

	public static function get_registration_action(): string { return "wp"; }

	public function get_assets( AssetContext $context ): iterable { return []; }
}
