<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Service;

final class NeedsContract implements Service {

	private UnboundContract $contract;

	public function __construct( UnboundContract $contract ) {
		$this->contract = $contract;
	}

	public function get(): UnboundContract {
		return $this->contract;
	}
}
