<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\CompositionRoot;

use MWPD\ContentFreshness\Infrastructure\Service;

final class HookingService implements Service {

	private HookingCollaborator $collaborator;

	public function __construct( HookingCollaborator $collaborator ) {
		$this->collaborator = $collaborator;
	}

	public function get(): HookingCollaborator {
		return $this->collaborator;
	}
}
