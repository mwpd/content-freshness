<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Asset;

use MWPD\ContentFreshness\Infrastructure\Asset\AssetContext;
use MWPD\ContentFreshness\Infrastructure\HasAssets;
use MWPD\ContentFreshness\Infrastructure\Service;
use TypeError;

/**
 * A service whose code fails with an Error rather than an Exception.
 */
final class ErroringAssetService implements Service, HasAssets {

	public function get_assets( AssetContext $context ): iterable {
		throw new TypeError( 'The service broke.' );
	}
}
