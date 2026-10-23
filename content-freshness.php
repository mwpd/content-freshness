<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2026 Alain Schlesser
 *
 * ------------------------------------------------------------------------------
 * -- 1. Provide the plugin meta information that WordPress needs.             --
 * ------------------------------------------------------------------------------
 *
 * @wordpress-plugin
 * Plugin Name:       Content Freshness
 * Plugin URI:        https://github.com/mwpd/content-freshness
 * Description:       Warns readers about outdated posts, shows editors what is going stale, and emails a weekly digest.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Alain Schlesser
 * Author URI:        https://www.alainschlesser.com/
 * Text Domain:       content-freshness
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness;

use MWPD\ContentFreshness\Infrastructure\Lifecycle\LifecycleScope;

/*
 * This is the plugin's bootstrap file. It serves three main purposes:
 *  1. Provide the plugin meta information that WordPress needs;
 *  2. Prepare the environment so that it is ready to execute our OOP code;
 *  3. Instantiate and kick off our "composition root" (our 'Plugin' class).
 *
 * The bootstrap file should not do anything else, so that we have a clean
 * separation between a.) code that needs to be run sequentially and produces
 * side-effects and b.) declarations that can be taken out of contexts for
 * testing and reuse and have no side-effects.
 *
 * Anything past this bootstrap file should be autoloadable classes, interfaces
 * or traits without any side-effects.
 */

/*
 * As this is the only PHP file having side effects, we need to provide a
 * safeguard, so we want to make sure this file is only run from within
 * WordPress and cannot be directly called through a web request.
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}



/*
 *------------------------------------------------------------------------------
 *-- 2. Prepare the environment so that it is ready to execute our OOP code.  --
 *----------------------------------------------------------------------------
 */

/*
 * We load Composer's autoloader if it exists.
 * If it doesn't exist, we fall back to a basic bundled autoloader
 * implementation. This allows us to just use the plugin as-is without requiring
 * the 'composer install' step.
 * Note: If you use Composer not only for autoloading, but also including
 * dependencies needed in production, the 'composer install' becomes mandatory
 * and the fallback autoloader should probably be removed.
 */
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

if ( ! class_exists( ContentFreshnessPluginFactory::class ) ) {
	// Composer's autoloader was not found, or does not know the plugin's
	// classes, so fall back to our bundled autoloader. Checking a class that
	// exists keeps the fallback from being registered next to Composer's.
	require_once __DIR__ . '/src/Infrastructure/Autoloader.php';

	( new Infrastructure\Autoloader() )
		->add_namespace( __NAMESPACE__, __DIR__ . '/src' )
		->register();
}



/*
 *------------------------------------------------------------------------------
 *-- 3. Instantiate and kick off our "composition root" (our "Plugin" class). --
 *----------------------------------------------------------------------------
 */

/*
 * We use a factory to instantiate the actual plugin.
 *
 * The factory keeps the object as a shared instance, so that the activation
 * and deactivation hooks below reach that same plugin instance through it.
 *
 * This is similar to a Singleton, but without all the drawbacks the Singleton
 * anti-pattern brings along.
 *
 * For more information on why to avoid a Singleton,
 * @see https://www.alainschlesser.com/singletons-shared-instances/
 *
 * Because the factory always returns that same shared instance, we ask it for
 * the plugin wherever we need it, instead of storing it in a variable.
 * WordPress includes this file in the global scope, so any variable defined
 * here would become a global and could clash with WordPress core or with other
 * plugins. This file therefore defines no variables at all.
 */

/*
 * We register activation and deactivation hooks by using closures, as these
 * need static access to work correctly.
 *
 * On multisite, WordPress tells them whether the plugin is activated or
 * deactivated for the whole network. The flag is turned into a LifecycleScope
 * right here, at the boundary, so the plugin never handles the raw value.
 *
 * The uninstall hook is not registered here: WordPress stores it in the
 * database, so the plugin registers it once, when it is activated. The
 * callback it stores is the static ContentFreshnessUninstallHook::uninstall().
 */
\register_activation_hook(
	__FILE__,
	/**
	 * Activate the plugin, for the whole network if WordPress says so.
	 *
	 * @param mixed $network_wide Whether the plugin is network-activated.
	 */
	static function ( $network_wide = false ): void {
		ContentFreshnessPluginFactory::create()->activate_in( LifecycleScope::from_wordpress_flag( $network_wide ) );
	}
);

\register_deactivation_hook(
	__FILE__,
	/**
	 * Deactivate the plugin, for the whole network if WordPress says so.
	 *
	 * @param mixed $network_wide Whether the plugin is network-deactivated.
	 */
	static function ( $network_wide = false ): void {
		ContentFreshnessPluginFactory::create()->deactivate_in( LifecycleScope::from_wordpress_flag( $network_wide ) );
	}
);

/**
 * Uninstall the plugin, for sites that activated version 1.0.0.
 *
 * Version 1.0.0 stored this function as its uninstall callback. WordPress
 * keeps calling the stored callback until the plugin is activated again,
 * when it is replaced with ContentFreshnessUninstallHook::uninstall(), so
 * deleting the plugin on such a site still needs the function. It hands over
 * at once to the bridge.
 */
function uninstall(): void {
	ContentFreshnessUninstallHook::uninstall();
}

/*
 * Finally, we run the plugin's register method to hook the plugin into the
 * WordPress request lifecycle.
 */
ContentFreshnessPluginFactory::create()->register();
