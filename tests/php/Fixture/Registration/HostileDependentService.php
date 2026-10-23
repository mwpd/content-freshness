<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Registration;

use MWPD\ContentFreshness\Infrastructure\HasDependencies;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that depends on a service whose identifier is markup.
 */
final class HostileDependentService implements Service, HasDependencies {

	public const DEPENDENCY = '<script>alert("dependency")</script>';

	public static function get_dependencies(): array {
		return [ self::DEPENDENCY ];
	}
}
