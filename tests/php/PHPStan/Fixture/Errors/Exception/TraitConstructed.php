<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Errors\Exception;

use RuntimeException;

final class TraitConstructed extends RuntimeException implements PluginException {
	use Helper;
}
