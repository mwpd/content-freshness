<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2026 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness;

use MWPD\ContentFreshness\Digest\{
	StaleContentDigest,
	StalePosts,
	WordPressStalePosts
};
use MWPD\ContentFreshness\Freshness\{
	Clock,
	FreshnessPolicy,
	OptionFreshnessPolicy,
	WordPressClock
};
use MWPD\ContentFreshness\Infrastructure\{
	QueryMonitor\CollectorFactory,
	QueryMonitor\OutputFactory,
	QueryMonitor\RegistrationCollectorFactory,
	QueryMonitor\RegistrationOutputFactory,
	QueryMonitor\RegistrationPanel,
	ServiceBasedPlugin,
	Service\DebugMode,
	Service\WordPressDebugMode,
	View\TemplatedViewFactory,
	View\ViewConfiguration,
	ViewFactory
};
use MWPD\ContentFreshness\Notice\{
	MainLoop,
	OutdatedPostNotice,
	WordPressMainLoop
};
use MWPD\ContentFreshness\PostsList\PostAgeColumn;
use MWPD\ContentFreshness\Settings\FreshnessSetting;

/**
 * The ContentFreshnessPlugin class is the composition root of the plugin.
 *
 * In here we assemble our infrastructure, configure it for the specific use
 * case the plugin is meant to solve and then kick off the services so that they
 * can hook themselves into the WordPress lifecycle.
 *
 * Where a configuration method below accepts a callable in place of a class
 * name, the callable is called later with the injector and the service
 * container, and must return the class name. It can be a closure, a static
 * method string ('Class::method'), a callable array ([ $object, 'method' ])
 * or an invokable object. A string naming a function, such as 'my_resolver',
 * is used as it is and never called, so that a value cannot run a function
 * by accident. To resolve through a function, wrap it in a closure:
 * static fn( $injector, $container ) => my_resolver( $injector, $container ).
 *
 * Argument names are always used as they are. Argument values and
 * delegation factories are handed to the injector as they are, so a
 * callable there is passed on rather than called here.
 */
final class ContentFreshnessPlugin extends ServiceBasedPlugin {
	/*
	 * --------------------------------------------------------------------------
	 * -- 1. Define the services that make up this plugin.                     --
	 * --------------------------------------------------------------------------
	 */

	/*
	 * The "plugin" is only a tool to hook arbitrary code up to the WordPress
	 * execution flow.
	 *
	 * The main structure we use to modularize our code is "services". These are
	 * what makes up the actual plugin, and they provide self-contained pieces
	 * of code that can work independently.
	 */

	/**
	 * Get the list of services to register.
	 *
	 * The services array contains a map of <identifier> => <service class name>
	 * associations.
	 *
	 * @return array<string,class-string|callable> Associative array of identifiers
	 *                                             mapped to fully qualified class
	 *                                             names or callables.
	 */
	protected function get_service_classes(): array {
		/*
		 * Each service states for itself where and when it acts, through the
		 * capability interfaces it implements. None of them knows about the
		 * others, so a new feature is one more line here.
		 */
		return [
			self::OUTDATED_POST_NOTICE_ID => OutdatedPostNotice::class,
			self::POST_AGE_COLUMN_ID      => PostAgeColumn::class,
			self::FRESHNESS_SETTING_ID    => FreshnessSetting::class,
			self::STALE_CONTENT_DIGEST_ID => StaleContentDigest::class,

			/*
			 * To see in Query Monitor which services were registered in a
			 * request, which were skipped, and which still wait for an action
			 * or for their dependencies, add the optional panel:
			 *
			 * self::REGISTRATION_PANEL_ID => RegistrationPanel::class,
			 *
			 * It only does anything in requests where Query Monitor runs, and
			 * the plugin only records the registration in those. The bindings
			 * and arguments it needs are already configured below.
			 */
		];
	}

	/*
	 * --------------------------------------------------------------------------
	 * -- 2. Configure the injector so it knows how to assemble them.          --
	 * --------------------------------------------------------------------------
	 */

	/**
	 * Get the bindings for the dependency injector.
	 *
	 * The bindings array contains a map of <interface> => <implementation>
	 * mappings, both of which should be fully qualified class names (FQCNs).
	 *
	 * The <interface> does not need to be the actual PHP `interface` language
	 * construct, it can be a `class` as well.
	 *
	 * Whenever you ask the injector to "make()" an <interface>, it will resolve
	 * these mappings and return an instance of the final <class> it found.
	 *
	 * @return array<class-string,class-string|callable> Associative array of
	 *                                                   fully qualified class names
	 *                                                   mapped to fully
	 *                                                   qualified class names or
	 *                                                   callables.
	 */
	protected function get_bindings(): array {
		return [
			// Views can be overridden by the theme.
			ViewFactory::class      => TemplatedViewFactory::class,

			// Failures surface when WP_DEBUG is on, and degrade when it is off.
			DebugMode::class        => WordPressDebugMode::class,

			/*
			 * The factories the optional Query Monitor panel creates its
			 * collector and output with. They create nothing until the panel
			 * is declared above and Query Monitor runs.
			 */
			CollectorFactory::class => RegistrationCollectorFactory::class,
			OutputFactory::class    => RegistrationOutputFactory::class,

			// Ages are measured against the time WordPress tells.
			Clock::class            => WordPressClock::class,

			// A post is outdated once it is older than the maximum age set
			// under Settings > Reading. Rebind this to change the rule for
			// every service at once.
			FreshnessPolicy::class  => OptionFreshnessPolicy::class,

			// The notice asks WordPress's main loop which post it renders.
			MainLoop::class         => WordPressMainLoop::class,

			// The digest asks the database for the stalest posts.
			StalePosts::class       => WordPressStalePosts::class,
		];
	}

	/**
	 * Get the argument bindings for the dependency injector.
	 *
	 * The arguments array contains a map of <class> => <associative array of
	 * arguments> mappings.
	 *
	 * The array is provided in the form <argument name> => <argument value>.
	 *
	 * @return array<array<string, mixed>> Associative array of arrays mapping
	 *                                     argument names to argument values.
	 */
	protected function get_arguments(): array {
		return [

			/*
			 * The plugin's own services ask for collaborators, not for scalar
			 * values. A scalar is bound like this:
			 * SomeService::class => [ 'argument_name' => 'value' ],
			 */

			/*
			 * How the optional Query Monitor panel is named. Its identifier
			 * carries the plugin's prefix, and its title names the plugin, so
			 * the panels of two plugins built from this scaffold stay apart.
			 */
			RegistrationCollectorFactory::class => [ 'panel_id' => self::REGISTRATION_PANEL_SLUG ],
			RegistrationOutputFactory::class    => [ 'panel_title' => self::REGISTRATION_PANEL_TITLE ],
		];
	}

	/**
	 * Get the shared instances for the dependency injector.
	 *
	 * The shared instances array contains a list of FQCNs that are meant to be
	 * reused. For multiple "make()" requests, the injector will return the same
	 * instance reference for these, instead of always returning a new one.
	 *
	 * This effectively turns these FQCNs into a "singleton", without incurring
	 * all the drawbacks of the Singleton design anti-pattern.
	 *
	 * @return array<class-string|callable> Array of fully qualified class names
	 *                                      or callables that return one.
	 */
	protected function get_shared_instances(): array {
		return [

			/*
			 * One policy for the whole request: it reads the option once, and
			 * every service that asks gets the same answer.
			 */
			FreshnessPolicy::class,
		];
	}

	/**
	 * Get the delegations for the dependency injector.
	 *
	 * The delegations array contains a map of <class> => <callable>
	 * mappings.
	 *
	 * The <callable> is basically a factory to provide custom instantiation
	 * logic for the given <class>.
	 *
	 * @return array<class-string,callable> Associative array of callables.
	 */
	protected function get_delegations(): array {
		return [

			/*
			 * A delegation builds an object the injector cannot. The current
			 * post is not one of them: the outdated post notice resolves its
			 * post when the_content runs, through MainLoop, so nothing here
			 * depends on when the service is built.
			 *
			 * The injector itself needs no delegation either. The
			 * infrastructure hands the plugin's own injector to any
			 * constructor parameter typed Injector. A service that has to
			 * make objects on demand takes the Injector and only ever calls
			 * make() on it. That is the supported alternative to a static
			 * service locator, such as Services::get( 'injector' ): the
			 * dependency stays visible in the constructor, and nothing static
			 * is involved.
			 */

			/*
			 * The view factories need to know where the plugin's views are.
			 * The infrastructure cannot tell where the plugin root is from
			 * its own location, so the composition root says so: it is the
			 * parent of src/, the directory that holds this file. This is
			 * the one place the plugin root is computed.
			 *
			 * The injector hands this configuration to the bound view
			 * factory, which passes it on to its views. Themes override the
			 * plugin's views in the VIEW_OVERRIDE_DIRECTORY defined below.
			 */
			ViewConfiguration::class => static fn(): ViewConfiguration => new ViewConfiguration(
				\dirname( __DIR__ ),
				self::VIEW_OVERRIDE_DIRECTORY
			),
		];
	}

	/*
	 * --------------------------------------------------------------------------
	 * -- 3. Tell WordPress which plugin this is and how to uninstall it.      --
	 * --------------------------------------------------------------------------
	 */

	/**
	 * Get the main plugin file.
	 *
	 * WordPress records whether the plugin is active, and its uninstall
	 * callback, under this file. Services that implement SiteDeactivateable or
	 * SiteInitializable, and the uninstall callback, need it.
	 *
	 * @return string Absolute path of the main plugin file.
	 */
	protected function get_main_file(): string {
		return \dirname( __DIR__ ) . '/content-freshness.php';
	}

	/**
	 * Get the callback WordPress calls to uninstall the plugin.
	 *
	 * WordPress stores the callback in the database and can only call a static
	 * method, so this names the listed bridge, which gets the plugin through
	 * the plugin factory and asks it to uninstall. The plugin registers the
	 * callback when it is activated, never while its main file is included.
	 * Services take part in the uninstall by implementing SiteUninstallable or
	 * NetworkUninstallable.
	 *
	 * @return array{0: class-string, 1: non-empty-string} Uninstall callback.
	 */
	protected function get_uninstall_callback(): array {
		return [ ContentFreshnessUninstallHook::class, 'uninstall' ];
	}

	/*
	 * --------------------------------------------------------------------------
	 * -- 4. Define prefixes and identifiers for hooks and services.           --
	 * --------------------------------------------------------------------------
	 */

	/*
	 * Prefixes to use.
	 *
	 * These are provided so that if multiple plugins use the same boilerplate
	 * code, their hooks and service identifiers are scoped and don't clash.
	 */
	protected const HOOK_PREFIX = 'mwpd.content_freshness.';

	protected const SERVICE_PREFIX = 'mwpd.content_freshness.';

	/*
	 * Directory inside a theme that holds the overrides for this plugin's
	 * views. A theme overrides views/settings.php with
	 * <child or parent theme>/content-freshness/views/settings.php.
	 *
	 * Rename it for your plugin, usually to its slug. It is a public path, so
	 * it carries the plugin's prefix like the hooks do: two plugins built from
	 * this scaffold, or a theme with a views/ folder of its own, then cannot
	 * override each other's views. Existing theme overrides move along when
	 * it changes.
	 */
	protected const VIEW_OVERRIDE_DIRECTORY = 'content-freshness';

	/*
	 * Service identifiers we know about.
	 *
	 * They name the services in the plugin's configuration: they key the
	 * services filter, and other services list them as dependencies. Another
	 * plugin can use them to replace or remove a service through that filter.
	 * They are not handles for fetching a service from the container outside
	 * the plugin, which is not a supported API (principle 13).
	 */
	public const OUTDATED_POST_NOTICE_ID = self::SERVICE_PREFIX . 'notice.outdated-post-notice';

	public const POST_AGE_COLUMN_ID = self::SERVICE_PREFIX . 'posts-list.post-age-column';

	public const FRESHNESS_SETTING_ID = self::SERVICE_PREFIX . 'settings.freshness-setting';

	public const STALE_CONTENT_DIGEST_ID = self::SERVICE_PREFIX . 'digest.stale-content-digest';

	public const REGISTRATION_PANEL_ID = self::SERVICE_PREFIX . 'query-monitor.registration-panel';

	/*
	 * Identifier and title of the optional Query Monitor panel. Query Monitor
	 * uses the identifier in HTML IDs, so it is a slug, with the prefix the
	 * plugin uses for such names.
	 */
	protected const REGISTRATION_PANEL_SLUG = 'mwpd-content-freshness-registration';

	protected const REGISTRATION_PANEL_TITLE = 'Content Freshness services';
}
