<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Errors\Exception;

use Exception;

final class NotSpl extends Exception implements PluginException {

	public static function for_reason(): self {
		return new self( 'Reason.' );
	}
}
