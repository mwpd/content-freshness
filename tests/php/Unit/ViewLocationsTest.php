<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://www.mwpd.io/
 * @copyright 2019 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit;

use MWPD\ContentFreshness\Exception\InvalidPath;
use MWPD\ContentFreshness\Infrastructure\View\ViewLocations;
use MWPD\ContentFreshness\Tests\ViewHelper;

final class ViewLocationsTest extends TestCase {

	public function test_it_returns_the_canonical_form_of_a_path_inside_a_location(): void {
		$locations = new ViewLocations( ViewHelper::LOCATIONS );

		$this->assertSame(
			\realpath( ViewHelper::PLUGIN_FOLDER . '/static-view.php' ),
			$locations->confine( ViewHelper::CHILD_THEME_FOLDER . '/../plugin/./static-view.php' )
		);
	}

	public function test_it_rejects_a_path_outside_all_locations(): void {
		$locations = new ViewLocations( ViewHelper::LOCATIONS );

		$this->expectException( InvalidPath::class );
		$this->expectExceptionMessage( 'outside of the allowed view locations' );

		$locations->confine( ViewHelper::VIEWS_FOLDER . 'static-view.php' );
	}

	public function test_it_rejects_a_path_in_a_sibling_that_shares_the_location_prefix(): void {
		// The directory "src/Infrastructure/View" is a string prefix of the
		// file "src/Infrastructure/ViewFactory.php", but does not contain it.
		$locations = new ViewLocations( [ 'src/Infrastructure/View' ] );

		$this->expectException( InvalidPath::class );

		$locations->confine( 'src/Infrastructure/ViewFactory.php' );
	}

	public function test_it_rejects_a_path_that_does_not_exist(): void {
		$locations = new ViewLocations( ViewHelper::LOCATIONS );

		$this->expectException( InvalidPath::class );
		$this->expectExceptionMessage( 'is not accessible or readable' );

		$locations->confine( ViewHelper::PLUGIN_FOLDER . '/missing-view.php' );
	}
}
