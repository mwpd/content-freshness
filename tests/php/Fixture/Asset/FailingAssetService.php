<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Asset;

use MWPD\ContentFreshness\Infrastructure\Asset\AssetContext;
use MWPD\ContentFreshness\Infrastructure\Asset\Style;
use MWPD\ContentFreshness\Infrastructure\HasAssets;
use MWPD\ContentFreshness\Infrastructure\Service;
use RuntimeException;

/**
 * A service whose asset declarations fail after the first one.
 */
final class FailingAssetService implements Service, HasAssets {

	public const HANDLE = 'acme-before-failure';

	public function get_assets( AssetContext $context ): iterable {
		yield Style::file( self::HANDLE, 'assets/before-failure.css' );

		throw new RuntimeException( 'The declarations failed.' );
	}
}
