<?php
declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Fixture\Injector;

/**
 * Needs two instances of the same type.
 */
final class TwoOfTheSameDependency {

	public SharedLeaf $first;

	public SharedLeaf $second;

	public function __construct( SharedLeaf $first, SharedLeaf $second ) {
		$this->first  = $first;
		$this->second = $second;
	}
}
