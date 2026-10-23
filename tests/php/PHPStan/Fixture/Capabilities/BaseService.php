<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities;

use MWPD\ContentFreshness\Infrastructure\Service;

abstract class BaseService implements Service, Capability {}
