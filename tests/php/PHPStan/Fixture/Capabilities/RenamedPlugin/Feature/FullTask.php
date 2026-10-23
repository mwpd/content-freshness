<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities\RenamedPlugin\Feature;

use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities\RenamedPlugin\Lifecycle\Task;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities\RenamedPlugin\Lifecycle\Hooks;
use MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities\RenamedPlugin\Lifecycle\Later;

final class FullTask implements Task, Hooks, Later {}
