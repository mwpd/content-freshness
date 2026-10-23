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

namespace MWPD\ContentFreshness;

use MWPD\ContentFreshness\Freshness\{
	Clock,
	FreshnessPolicy,
	OptionFreshnessPolicy,
	WordPressClock
};
use MWPD\ContentFreshness\Infrastructure\{
	ServiceBasedPlugin,
	Service\DebugMode,
	Service\WordPressDebugMode,
	View\TemplatedViewFactory,
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
		return [
			self::OUTDATED_POST_NOTICE_ID => OutdatedPostNotice::class,
			self::POST_AGE_COLUMN_ID      => PostAgeColumn::class,
			self::FRESHNESS_SETTING_ID    => FreshnessSetting::class,
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
			ViewFactory::class     => TemplatedViewFactory::class,

			// Failures surface when WP_DEBUG is on, and degrade when it is off.
			DebugMode::class       => WordPressDebugMode::class,

			// Ages are measured against the time WordPress tells.
			Clock::class           => WordPressClock::class,

			// A post is outdated once it is older than the maximum age set
			// under Settings > Reading. Rebind this to change the rule for
			// every service at once.
			FreshnessPolicy::class => OptionFreshnessPolicy::class,

			// The notice asks WordPress's main loop which post it renders.
			MainLoop::class        => WordPressMainLoop::class,
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
			 * None needed: every constructor here asks for collaborators, not
			 * for scalar values. A scalar would be bound like this:
			 * SomeService::class => [ 'argument_name' => 'value' ],
			 */
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
			 * None needed. A delegation builds an object the injector cannot,
			 * such as the current post at build time. The outdated post
			 * notice resolves its post when the_content runs instead, through
			 * MainLoop, so nothing here depends on when the service is built.
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
		];
	}

	/*
	 * --------------------------------------------------------------------------
	 * -- 3. Define prefixes and identifiers for outside access.               --
	 * --------------------------------------------------------------------------
	 */

	/*
	 * Prefixes to use.
	 *
	 * These are provided so that if multiple plugins use the same boilerplate
	 * code, there hooks and service identifiers are scoped and don't clash.
	 */
	protected const HOOK_PREFIX = 'mwpd.content_freshness.';

	protected const SERVICE_PREFIX = 'mwpd.content_freshness.';

	/*
	 * Service identifiers we know about.
	 *
	 * These can be used from outside code as well to directly refer to a
	 * service when talking to the service container.
	 */
	public const OUTDATED_POST_NOTICE_ID = self::SERVICE_PREFIX . 'notice.outdated-post-notice';

	public const POST_AGE_COLUMN_ID = self::SERVICE_PREFIX . 'posts-list.post-age-column';

	public const FRESHNESS_SETTING_ID = self::SERVICE_PREFIX . 'settings.freshness-setting';
}
