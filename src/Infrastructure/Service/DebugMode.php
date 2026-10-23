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

namespace MWPD\ContentFreshness\Infrastructure\Service;

/**
 * Interface for a debug mode service.
 *
 * This allows for testing the debug mode service without having to rely on
 * the global state of the WordPress constant.
 */
interface DebugMode {

	/**
	 * Check if the application is in debug mode.
	 *
	 * @return bool True if debug mode is active, false otherwise.
	 */
	public function is_debug_mode(): bool;
}
