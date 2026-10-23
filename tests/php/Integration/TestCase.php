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

namespace MWPD\ContentFreshness\Tests\Integration;

use Brain\Monkey;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PHPUnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

abstract class TestCase extends PHPUnitTestCase {

	// Adds Mockery expectations to the PHPUnit assertions count.
	use MockeryPHPUnitIntegration;

	protected function set_up() {
		parent::set_up();
		Monkey\setUp();

		// The digest sets up sites created on a network, so registering the
		// plugin's services asks whether this is a multisite installation.
		// A test of multisite installs its own answer.
		Monkey\Functions\when( 'is_multisite' )->justReturn( false );
	}

	protected function tear_down() {
		Monkey\tearDown();
		parent::tear_down();
	}
}
