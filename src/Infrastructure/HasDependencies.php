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

declare(strict_types=1);

namespace MWPD\ContentFreshness\Infrastructure;

/**
 * Interface for services that depend on other services.
 */
interface HasDependencies {

	/**
	 * Get the list of service IDs this service depends on.
	 *
	 * @return string[] List of service IDs.
	 */
	public static function get_dependencies(): array;
}
