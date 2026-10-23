<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoGlobalState;

final class ChildAdapter extends InputAdapter {
	public function input(): array {
		return $_GET;
	}
}
