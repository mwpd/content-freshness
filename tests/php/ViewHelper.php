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

namespace MWPD\ContentFreshness\Tests;

interface ViewHelper {

	public const VIEWS_FOLDER = 'tests/php/Fixture/views/';

	public const CHILD_THEME_FOLDER = self::VIEWS_FOLDER . 'child_theme';

	public const PARENT_THEME_FOLDER = self::VIEWS_FOLDER . 'parent_theme';

	public const PLUGIN_FOLDER = self::VIEWS_FOLDER . 'plugin';

	public const LOCATIONS = [
		self::CHILD_THEME_FOLDER,
		self::PARENT_THEME_FOLDER,
		self::PLUGIN_FOLDER,
	];
}
