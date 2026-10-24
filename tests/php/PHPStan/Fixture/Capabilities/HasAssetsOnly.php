<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\{Asset\AssetContext, HasAssets};

final class HasAssetsOnly implements HasAssets {
	public function get_assets( AssetContext $context ): iterable { return []; }
}
