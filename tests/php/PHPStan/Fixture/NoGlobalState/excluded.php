<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\NoGlobalState\Excluded;

global $wpdb;
$value = [ $GLOBALS, $_GET ];
