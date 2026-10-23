<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Lifecycle;

use MWPD\ContentFreshness\Infrastructure\Activateable;
use MWPD\ContentFreshness\Infrastructure\Deactivateable;
use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Depends on the delayed service, so it waits for init to register.
 */
final class DependentLifecycleService implements Service, Registerable, HasDependencies, Activateable, Deactivateable {
	use RecordsLifecycle;

	private const NAME = 'dependent';

	public static function get_dependencies(): array {
		return [ 'delayed' ];
	}

	public function register(): void {
		$this->recorder->record( 'register:' . self::NAME );
	}
}
