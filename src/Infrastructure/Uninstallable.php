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

namespace MWPD\ContentFreshness\Infrastructure;

/**
 * Something that can be uninstalled.
 *
 * By tagging a service with this interface, the system will automatically hook
 * it up to the WordPress uninstall hook, so the service can remove what it
 * stored: its options, its post meta, its custom tables.
 *
 * WordPress only uninstalls a plugin that is deactivated, so deactivation has
 * already run by then. Uninstalling is for data the plugin keeps while it is
 * deactivated, and has to remove once it is deleted.
 */
interface Uninstallable {

	/**
	 * Uninstall the service.
	 */
	public function uninstall(): void;
}
