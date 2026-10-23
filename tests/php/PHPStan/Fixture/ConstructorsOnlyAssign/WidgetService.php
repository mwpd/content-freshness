<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Fixture\ConstructorsOnlyAssign;

use MWPD\ContentFreshness\Infrastructure\Service;

final class WidgetService extends \WP_Widget implements Service {

	public function __construct( string $id, string $name ) {
		parent::__construct( $id, $name );
		$this->name = $name;
	}
}
