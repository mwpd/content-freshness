<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoServiceLocator\Feature;

use MWPD\ContentFreshness\Infrastructure as Infra;

/** @method Infra\ServiceContainer container() */
final class Aliased {}
