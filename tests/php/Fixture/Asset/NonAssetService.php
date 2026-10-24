<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Asset;

use MWPD\ContentFreshness\Infrastructure\Asset\AssetContext;
use MWPD\ContentFreshness\Infrastructure\HasAssets;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that breaks the contract of get_assets().
 */
final class NonAssetService implements Service, HasAssets {

	/**
	 * @psalm-suppress InvalidReturnType It breaks the contract on purpose.
	 * @psalm-suppress InvalidReturnStatement It breaks the contract on purpose.
	 */
	public function get_assets( AssetContext $context ): iterable {
		return [ 'assets/not-an-asset.css' ]; // @phpstan-ignore return.type (It breaks the contract on purpose.)
	}
}
