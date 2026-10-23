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

namespace MWPD\ContentFreshness\Infrastructure\View;

use MWPD\ContentFreshness\Infrastructure\Service;
use MWPD\ContentFreshness\Infrastructure\View;
use MWPD\ContentFreshness\Infrastructure\ViewFactory;

/**
 * Factory to create the simplified view objects.
 */
final class SimpleViewFactory implements Service, ViewFactory {

	/**
	 * Create a new view object for a given relative path.
	 *
	 * @param string $relative_path Relative path to create the view for.
	 *
	 * @return SimpleView Instantiated view object.
	 */
	public function create( string $relative_path ): View {
		return new SimpleView( $relative_path, $this );
	}
}
