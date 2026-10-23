<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2019 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture;

final class DummyClassWithDependency implements DummyInterface {

	private DummyClass $dummy;

	public function __construct( DummyClass $dummy ) {
		$this->dummy = $dummy;
	}

	public function get_dummy(): DummyClass {
		return $this->dummy;
	}
}
