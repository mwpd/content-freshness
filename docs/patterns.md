# Patterns

The [design principles](design-principles.md) say why the plugin is built
the way it is, and the [architecture rules](architecture-rules.md) say exactly
what the checks reject. This page answers the question in between, the one you
arrive with: **"I need X. How do I do that here?"**

Each section is named after a need, in the words you would search for. Many
of those needs have a well-known answer elsewhere in WordPress or PHP, often
one this plugin rejects, so every section has the same parts:

- **What people reach for**: the common answer.
- **Why not here**: the principles and the rules that reject it.
- **Instead**: the plugin's pattern. Where one need hides several, the
  section tells them apart.
- **Proven by**: code in this plugin that follows the pattern and passes the
  checks.
- **Rules**: the checks that enforce it. A rule the scaffold is still building
  names its issue in [`mwpd/basic-scaffold`](https://github.com/mwpd/basic-scaffold/issues).

If a check stops you, its message names the principle and the rule. Look up
what you were trying to do here before working around it. If no section fits,
the need may be new: ask on an issue rather than inventing a workaround.

## Contents

- [I need to add a feature](#i-need-to-add-a-feature)
- [I need to add hooks](#i-need-to-add-hooks)
- [I need a service only in some requests](#i-need-a-service-only-in-some-requests)
- [I need to hook in later](#i-need-to-hook-in-later)
- [I need to set something up when the plugin is activated](#i-need-to-set-something-up-when-the-plugin-is-activated)
- [I need to clean up when the plugin is deleted](#i-need-to-clean-up-when-the-plugin-is-deleted)
- [I need to create objects on demand](#i-need-to-create-objects-on-demand)
- [I need to choose an implementation at runtime](#i-need-to-choose-an-implementation-at-runtime)
- [I need a service locator](#i-need-a-service-locator)
- [I need a singleton](#i-need-a-singleton)
- [I need to reach the plugin from a static WordPress callback](#i-need-to-reach-the-plugin-from-a-static-wordpress-callback)
- [I need WordPress state, such as the current post](#i-need-wordpress-state-such-as-the-current-post)
- [I need a default collaborator that can be replaced](#i-need-a-default-collaborator-that-can-be-replaced)
- [I need to start simple and add implementations later](#i-need-to-start-simple-and-add-implementations-later)
- [I need to print output](#i-need-to-print-output)
- [I need to report a failure](#i-need-to-report-a-failure)
- [I need to run code when the plugin file loads](#i-need-to-run-code-when-the-plugin-file-loads)
- [I need a name in a space WordPress shares](#i-need-a-name-in-a-space-wordpress-shares)

## I need to add a feature

**What people reach for:** one more method on the main plugin class, or one
more `if` in an existing service, so that the class grows with every feature.

**Why not here:** [the core idea](design-principles.md#the-core-idea-complexity-that-grows-linearly)
and [principle 4](design-principles.md#4-solid-at-the-top-wordpress-at-the-edges):
each new condition threaded through existing code multiplies the cases that
can go wrong. A god class is in the
[principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
table of anti-patterns.

**Instead:** write one new service for the feature, in its own subsystem
namespace, and declare it in the composition root. The service decides for
itself when it is needed and when it hooks in, through the capability
interfaces in the sections below. No other code changes.

```php
// src/ContentFreshnessPlugin.php
protected function get_service_classes(): array {
	return [
		self::OUTDATED_POST_NOTICE_ID => OutdatedPostNotice::class,
		self::POST_AGE_COLUMN_ID      => PostAgeColumn::class,
		self::FRESHNESS_SETTING_ID    => FreshnessSetting::class,
		self::STALE_CONTENT_DIGEST_ID => StaleContentDigest::class,
	];
}
```

**Proven by:** `src/ContentFreshnessPlugin.php:88-100` declares the four
services; none knows about the others. The notice came first. Adding the
column and then the digest each added one line here, a binding where they
needed a new collaborator, and their own directory: `git log --stat` shows no
other service changed.

**Rules:** `architecture.compositionRootContracts` builds every declared
service; `architecture.dependencyDirection` keeps a subsystem on the
infrastructure's interfaces. `architecture.capabilitiesOnServices` keeps lifecycle
capabilities on services, and `architecture.servicesDeclared` catches an
undeclared subsystem service.

## I need to add hooks

**What people reach for:** `add_action()` in the constructor, or at the top of
the file.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns):
creating an object must not change global state, and including a file must not
do anything.

**Instead:** implement `Registerable` and add the hooks in `register()`. The
plugin calls it once, at the right point in the lifecycle. Activation,
deactivation and uninstalling work the same way, through `Activateable`,
`Deactivateable` and `Uninstallable`.

```php
final class PostAgeColumn implements Service, Registerable, Conditional, Delayed {

	public function __construct( FreshnessPolicy $policy, ViewFactory $view_factory, DebugMode $debug_mode ) {
		$this->policy       = $policy;
		$this->view_factory = $view_factory;
		$this->debug_mode   = $debug_mode;
	}

	public function register(): void {
		\add_filter( 'manage_post_posts_columns', [ $this, 'add_column' ] );
		\add_filter( 'manage_edit-post_sortable_columns', [ $this, 'make_sortable' ] );
		\add_action( 'manage_post_posts_custom_column', [ $this, 'render_cell' ], 10, 2 );
	}
}
```

**Proven by:** `src/PostsList/PostAgeColumn.php:104-116`.

**Rules:** `architecture.noHooksInConstructor`,
`architecture.hooksInLifecycleMethods`, `architecture.constructorsOnlyAssign`.
Service constructors only assign parameters to properties, with optional
poka-yoke defaults. Move other work into `register()` or a callback, or into a
factory if it is construction logic.

## I need a service only in some requests

**What people reach for:** `if ( is_admin() ) { add_action( … ); }`, wrapped
around hook calls throughout the code.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns),
and [the core idea](design-principles.md#how-services-map-to-it): the
condition belongs to the service, stated once.

**Instead:** implement `Conditional` and answer in the static `is_needed()`.
The plugin asks before it builds the service, so in a request that does not
need it, the service never exists.

```php
public static function is_needed(): bool {
	$screen = \get_current_screen();

	return null !== $screen && 'edit-post' === $screen->id;
}
```

**Proven by:** `src/PostsList/PostAgeColumn.php:82-86`. The column is only built on
the Posts list.

When the answer depends on the query, such as `is_singular()`, or on the
screen, also implement `Delayed` and wait for an action late enough to
answer, as shown in [I need to hook in later](#i-need-to-hook-in-later).

Do not add a condition the registration action already implies.
`FreshnessSetting` waits for `admin_init`, which only fires in admin
requests, so it has no `is_admin()` condition
(`src/Settings/FreshnessSetting.php:36-40`). A condition would also keep it from
taking part in lifecycle events where it is false, such as uninstalling
through WP-CLI.

**Rules:** `architecture.noContextChecksInRegister`,
`architecture.queryContextNeedsDelay`.

## I need to hook in later

**What people reach for:** hooking into `plugins_loaded` or `init` just to add
another callback for `wp`, or checking `did_action()` before hooking.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns),
and the lifecycle facts in [the core idea](design-principles.md#what-the-wordpress-lifecycle-means-here):
many conditions only answer once a certain action has run. `is_singular()`, for
example, means nothing before the query.

**Instead:** implement `Delayed` and name the action in
`get_registration_action()`. The plugin waits for it, or registers at once if
it has already fired, and only then asks `is_needed()` and builds the service.

```php
final class OutdatedPostNotice implements Service, Registerable, Conditional, Delayed {

	public static function is_needed(): bool {
		return \is_singular( 'post' ); // Answers correctly, because the query has run.
	}

	public static function get_registration_action(): string {
		return 'wp';
	}
}
```

**Proven by:** `src/Notice/OutdatedPostNotice.php:33-81`, and
`PostAgeColumn`, which waits for `current_screen` before it asks which screen
it is on (`src/PostsList/PostAgeColumn.php:82-94`).

**Rules:** `architecture.noContextChecksInRegister`,
`architecture.queryContextNeedsDelay`, `architecture.registrationActionKnown`.
Hook-timing checks such as `did_action()` in `register()` are reported, as are
unknown registration actions. Whether a known action fires in the service's
request type or runs late enough for its condition remains a review concern.

## I need to set something up when the plugin is activated

**What people reach for:** `register_activation_hook()` next to the code that
needs it, calling the `init` callback from the activation hook so a post type
exists before the flush, or scheduling a cron event on every request.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
and [the core idea](design-principles.md#how-services-map-to-it): the main
plugin file registers the activation and deactivation hooks once, and each
service says for itself what it does there. Calling `register()` early would
also break the promise of `Delayed`, that a service registers at its own
action.

**Instead:** implement `Activateable` and `Deactivateable`. The plugin calls
every service implementing them, in the order they are configured, including
services it stores lazily and `Delayed` services whose action has not fired
yet. A service that is not registered yet is built for the call alone, with
its usual constructor collaborators; its `register()` still runs at its own
action. Then the plugin asks WordPress for one soft flush of the rewrite
rules.

The digest schedules its weekly event on activation and clears it on
deactivation. Its `register()` only hooks the callback, in every request,
because WP-Cron runs in whatever request comes along:

```php
final class StaleContentDigest implements Service, Registerable, Activateable, Deactivateable {

	public function register(): void {
		\add_action( self::CRON_HOOK, [ $this, 'send' ], 10, 0 );
	}

	public function activate(): void {
		if ( false !== \wp_next_scheduled( self::CRON_HOOK ) ) {
			return; // Activating twice must not send two digests a week.
		}

		$result = \wp_schedule_event( …, 'weekly', self::CRON_HOOK, [], true );
		// …
	}

	public function deactivate(): void {
		$result = \wp_clear_scheduled_hook( self::CRON_HOOK, [], true );
		// …
	}
}
```

Three things to know:

- **The flush is soft.** It rebuilds the rules WordPress stores, but does not
  write server configuration such as `.htaccess`. A service that needs that
  calls `\flush_rewrite_rules( true )` at the end of its own `activate()` or
  `deactivate()`. WordPress postpones any flush requested before `wp_loaded`
  until then, and does a hard one if any of the requests asked for it.
- **`Conditional` still applies.** A service whose `is_needed()` is false in
  the activation request is neither built nor called. Setup or cleanup that
  must run whatever the condition belongs in a service without it. That is
  why the digest is not `Conditional`.
- **`HasDependencies` does not order lifecycle calls.** It only governs
  registration, and no dependency is registered early for a lifecycle call.
  Steps that must run in sequence belong in one service that runs them in
  that order.

**Proven by:** `src/Digest/StaleContentDigest.php:147-186`, and the
end-to-end tests, which check that the event is scheduled once, however often
the plugin is activated, and gone after deactivation.

**Rules:** `architecture.compositionRootContracts` runs `activate()` and
`deactivate()` twice and reports a second run that changes anything, so guard
each step or make it set rather than add.

## I need to clean up when the plugin is deleted

**What people reach for:** an `uninstall.php` file that repeats the option
names, or `register_uninstall_hook()` with a function that deletes options
by name, kept in step with the code that creates them by hand.

**Why not here:** [the core idea](design-principles.md#how-services-map-to-it):
each service owns what it stores, so it is the one to remove it. An
`uninstall.php` would be a second file with side effects
([principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)),
and a list of names somewhere else drifts from the code.

**Instead:** implement `Uninstallable`. WordPress only uninstalls a plugin it
has deactivated; then it includes the main plugin file and calls its
uninstall callback, a bridge that asks the plugin to uninstall. The plugin
calls every service implementing `Uninstallable`, chosen the same way as for
activation.

```php
final class FreshnessSetting implements Service, Registerable, Delayed, Uninstallable {

	public function uninstall(): void {
		\delete_option( OptionFreshnessPolicy::OPTION_NAME );
	}
}
```

Deactivation is not uninstalling: a site that deactivates the plugin for a
while keeps its setting. Remove data in `uninstall()`, and stop behaviour in
`deactivate()`.

As with activation, `Conditional` applies: a service that is not needed in the
uninstall request is skipped. Uninstalling can happen in an admin request or
through WP-CLI, so a service that cleans up must not depend on either.

**Proven by:** `src/Settings/FreshnessSetting.php:125-127`, the bridge in
`content-freshness.php:148-150`, and
`tests/php/Integration/Settings/FreshnessSettingTest.php:55-61`.

**Rules:** `architecture.compositionRootContracts` runs `uninstall()` twice
too, and reports a second run that changes anything.

## I need to create objects on demand

**What people reach for:** `new Thing()` inside the class that needs it.

**Why not here:** [principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph):
the composition root builds the object graph, and the class that uses an
object never creates it.

**Instead:** when an object can only be made later, or is needed once per use,
receive a factory and ask it each time. The factory may use `new` internally:
creating things is its whole job.

```php
// The consumer receives the factory, typed to its interface …
public function __construct( FreshnessPolicy $policy, ViewFactory $view_factory, DebugMode $debug_mode ) {
	$this->view_factory = $view_factory;
	// …
}

// … and asks it for a fresh view each time it renders a cell.
$view = $this->view_factory->create( self::VIEW );
echo $view->render( $context );
```

```php
// The factory creates its product.
final class TemplatedViewFactory implements Service, ViewFactory {

	public function create( string $relative_path ): View {
		if ( $this->locations === [] ) {
			$this->locations = $this->get_default_locations();
		}

		return new TemplatedView( $relative_path, $this, $this->locations );
	}
}
```

`TemplatedViewFactory` only stores the optional locations in its constructor.
When none are passed, it reads the theme directories on the first `create()`
call and keeps those locations for later calls. Explicit locations keep their
order and bypass theme lookups; the unit tests pass the plugin directory
alone.

When writing a dedicated factory is overkill, receive the `Injector` itself
and call `make()` on it. A constructor parameter typed `Injector` gets the
plugin's own injector, whichever implementation the plugin was given; the
infrastructure sets that up, so the composition root needs no configuration
for it. A service only ever calls `make()` on the injector: bindings, sharing
and delegations stay in the composition root.

```php
public function __construct( Injector $injector ) {
	$this->injector = $injector;
}

// … later, once per use:
$report = $this->injector->make( Report::class );
```

**Proven by:** `src/PostsList/PostAgeColumn.php:104-108` and
`src/PostsList/PostAgeColumn.php:189-192`, `src/Infrastructure/View/TemplatedViewFactory.php:29` and
`src/Infrastructure/View/TemplatedViewFactory.php:56-61`, bound at `src/ContentFreshnessPlugin.php:129`.
The injector is made injectable at
`src/Infrastructure/ServiceBasedPlugin.php:371-374`, proven by
`tests/php/Unit/ServiceBasedPluginTest.php:689-723`.

**Rules:** `architecture.newOnlyWhereAllowed`, `architecture.noServiceLocator`;
`architecture.dependencyDirection` keeps the consumer on the interface.

## I need to choose an implementation at runtime

**What people reach for:** a switch over a type name that calls `new`, or a
container lookup by a computed identifier, such as
`$container->get( 'gateway.' . $type )`.

**Why not here:** [principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
rules out `new` of collaborators, and a lookup by computed identifier is a
service locator. [Principle 8](design-principles.md#8-types-name-concepts-not-just-data)
wants the choice typed.

**Instead:** a typed factory or registry for that one concept. It takes the
key as a value, returns the interface, and is injected like any other
collaborator. The example below is illustrative; the plugin has no need for a
registry yet.

```php
interface PaymentGateways {
	public function get( GatewayKey $key ): PaymentGateway;
}

final class CheckoutService implements Service, Registerable {

	public function __construct( PaymentGateways $gateways ) {
		$this->gateways = $gateways;
	}
}
```

The composition root binds `PaymentGateways` to an implementation, which
builds or receives the gateways it knows. Nothing outside it knows the
concrete classes.

**Proven by:** no registry in the plugin yet. `ViewFactory` has the same
outer shape (`src/Infrastructure/ViewFactory.php:25`): it is injected, takes a
value (a view path) and returns an interface (`View`). It always builds the
same implementation, though; the path only picks the template file.

**Rules:** `architecture.newOnlyWhereAllowed`,
`architecture.dependencyDirection`, `architecture.noServiceLocator`. Receive
the typed registry through the constructor instead of the service container.

## I need a service locator

**What people reach for:** a static `Services::get( 'id' )` that any code can
call. Every plugin known to be built on the scaffold before this one added one
([mwpd/basic-scaffold#22](https://github.com/mwpd/basic-scaffold/issues/22)).

**Why not here:** [principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
and the singleton row of [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns):
a locator hides dependencies and lets any code reach any object. A locator
that keeps its own registry needs static state, which
`architecture.noStaticState` already rejects. One that only forwards to the
plugin factory, `ContentFreshnessPluginFactory::create()->get_container()->get( $id )`,
holds no state of its own. `architecture.pluginFactoryOnlyAtEdges` rejects
that factory call inside the object graph, and `architecture.noServiceLocator`
keeps the container out of feature subsystems.

**Instead:** find the need behind it. Each one has its own answer:

| You need | Use |
| --- | --- |
| A collaborator, known in advance | Constructor injection: [I need to add a feature](#i-need-to-add-a-feature) |
| An object created later or per use | [A factory, or `Injector::make()`](#i-need-to-create-objects-on-demand) |
| One of several implementations, chosen by a value | [A typed registry](#i-need-to-choose-an-implementation-at-runtime) |
| The same instance in several places | [`share()`](#i-need-a-singleton) |
| Access from code WordPress calls statically, outside the object graph | [An edge bridge](#i-need-to-reach-the-plugin-from-a-static-wordpress-callback) |
| A public PHP API for themes or other plugins | Not settled in the scaffold yet: [mwpd/basic-scaffold#93](https://github.com/mwpd/basic-scaffold/issues/93) |

**Proven by:** the sections linked above.

**Rules:** `architecture.noStaticState`, for locators with a registry of
their own; `architecture.noServiceLocator`, for container dependencies and
reconfiguring the injector from a subsystem; `architecture.pluginFactoryOnlyAtEdges`,
for plugin factory calls outside the main file and listed bridges. Receiving
`Injector` and calling `make()` remains supported for on-demand creation.

## I need a singleton

**What people reach for:** a class with a private constructor, a static
`$instance` and `::instance()`.

**Why not here:** [principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
and [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns):
static state is global state, hidden behind a class name, and it cannot be
replaced in a test.

**Instead:** let the injector share the instance. List the class or interface
in the composition root's shared instances, and every object that asks for it
receives the same one. The class itself stays an ordinary class.

```php
// src/ContentFreshnessPlugin.php
protected function get_shared_instances(): array {
	return [
		// One policy for the whole request: it reads the option once, and
		// every service that asks gets the same answer.
		FreshnessPolicy::class,
	];
}
```

The one exception is the plugin itself, which WordPress needs to reach from
outside the object graph: see the next section.

**Proven by:** `src/ContentFreshnessPlugin.php:185-194` shares the policy;
`Injector::share()` (`src/Infrastructure/Injector.php:78`) is what the plugin calls for
each entry.

**Rules:** `architecture.noStaticState`.

## I need to reach the plugin from a static WordPress callback

**What people reach for:** a global `$my_plugin` variable, or a singleton.

**Why not here:** [principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
and [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns).
Some WordPress entry points still need static access: `register_uninstall_hook()`
only accepts a static method or a function, because WordPress stores it in the
database, and the activation hooks run outside the normal request flow.

**Instead:** an *edge bridge*. The plugin factory is the one place allowed to
keep the plugin instance; a bridge is a static callable that gets the plugin
through it and delegates at once to instance methods:

```php
\register_activation_hook(
	__FILE__,
	static function () {
		\register_uninstall_hook( __FILE__, __NAMESPACE__ . '\\uninstall' );
		ContentFreshnessPluginFactory::create()->activate();
	}
);

function uninstall(): void {
	ContentFreshnessPluginFactory::create()->uninstall();
}
```

The uninstall callback is a named function in the main plugin file, the only
file allowed to have side effects. It is registered on activation rather than
on every request, as the Plugin Handbook recommends: registering it writes to
the database whenever the stored callback differs.

A bridge outside the main plugin file follows the same contract:

- a `final` class with static methods and no state of its own;
- each method gets the plugin through the factory and delegates immediately,
  with no logic of its own;
- it is listed in the configuration, so every way into the object graph from
  outside is visible and reviewed;
- it is the point where WordPress hands over control, so it has to handle
  [principle 10](design-principles.md#10-errors-are-exceptions) there: a call
  before `plugins_loaded`, a `Conditional` service that is not registered in
  this request, or any other failure becomes a dedicated exception, caught
  before control goes back to WordPress. The lifecycle bridges do not catch
  yet, as in the scaffold
  ([mwpd/basic-scaffold#27](https://github.com/mwpd/basic-scaffold/issues/27)).

**Proven by:** `content-freshness.php:125-149`, and the factory at
`src/ContentFreshnessPluginFactory.php:38-55`.

**Rules:** `architecture.noStaticState` allows static state only in the
factory. [`architecture.pluginFactoryOnlyAtEdges`](architecture-rules.md#architecturepluginfactoryonlyatedges)
reports calls to the factory outside the main file and methods listed in
`parameters.architecture.pluginFactoryOnlyAtEdges.bridges`.

## I need WordPress state, such as the current post

**What people reach for:** `global $post;`, `global $wpdb;`, or `get_post()`
deep inside a service.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
(collaborators pulled from globals) and
[principle 9](design-principles.md#9-never-trust-what-wordpress-hands-over):
what WordPress hands over is checked once, at the boundary.

**Instead:** put the WordPress state behind a small interface, and receive
that interface through the constructor. Its WordPress implementation is the
boundary: it reads the state, checks it, and fails with a dedicated exception
when it is not what the service needs. The service asks it when it needs the
value, not when it is built.

That last part matters for state that changes during a request. The current
post is the clearest case: `the_content` runs for the queried post, but also
for every post in a related-posts loop and for excerpts built in the head. A
`WP_Post` injected when the service is built is the post of one moment; an
interface asked inside the filter callback knows which post is being rendered
now.

```php
// src/Notice/WordPressMainLoop.php: the boundary.
public function is_rendering_queried_post(): bool {
	return \in_the_loop()
		&& \is_main_query()
		&& \get_the_ID() === \get_queried_object_id();
}

public function queried_post(): WP_Post {
	$post = \get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		throw FailedToFindPost::for_main_query();
	}

	return $post;
}
```

```php
// The service only says what it needs, and asks when the filter runs.
public function __construct( FreshnessPolicy $policy, MainLoop $main_loop, … ) { … }

public function prepend_notice( $content ) {
	if ( ! \is_string( $content ) || ! $this->main_loop->is_rendering_queried_post() ) {
		return $content;
	}

	$post = $this->main_loop->queried_post();
	// …
}
```

A delegation in the composition root can still build a value the injector
cannot, but it runs once, when the service is built. Use it for state that is
fixed for the request, never for the current post.

Calling a WordPress function inside an implementation detail is still fine
([principle 4](design-principles.md#4-solid-at-the-top-wordpress-at-the-edges)).
The interface is for state a service depends on.

**Proven by:** `src/Notice/WordPressMainLoop.php:25-57`, bound at
`src/ContentFreshnessPlugin.php:143`, and asked at call time in
`src/Notice/OutdatedPostNotice.php:129-135`.

For request input, a boundary adapter reads the superglobals, sanitizes and
normalizes them into an internal type, and supplies that value to the service.
List the adapter class in `parameters.architecture.noGlobalState.inputAdapters`;
this permits request superglobals, never `global` or `$GLOBALS`. Form input
that WordPress already reads for you, such as a setting saved through the
Settings API, arrives through the sanitize callback instead
(`src/Settings/FreshnessSetting.php:140`).

**Rules:** `architecture.newOnlyWhereAllowed` (WordPress classes such as
`WP_Query` come from the injector too), and `architecture.noGlobalState` for
`global`, `$GLOBALS` and request input outside listed adapters.

## I need a default collaborator that can be replaced

**What people reach for:** `new DefaultThing()` in the constructor, which can
never be swapped, or a required parameter that every caller has to wire up.

**Why not here:** [principle 3](design-principles.md#3-defaults-out-of-the-box-poka-yoke):
the default ships built in, and replacing it is a matter of passing your own.

**Instead:** an optional constructor parameter typed to the interface, with the
built-in implementation as the fallback. The fallback is the only place the
default is named.

```php
public function __construct( string $path, ViewFactory $view_factory, ?DebugMode $debug_mode = null ) {
	$this->debug_mode = $debug_mode ?? new WordPressDebugMode();
}
```

**Proven by:** `src/Infrastructure/View/SimpleView.php:73-76`, and the
injector and container defaults of `ServiceBasedPlugin`
(`src/Infrastructure/ServiceBasedPlugin.php:152-170`).

**Rules:** `architecture.newOnlyWhereAllowed` allows `new` in exactly this
shape: a nullable parameter typed to an interface the default implements.

## I need to start simple and add implementations later

**What people reach for:** an interface for everything up front, or names that
say what kind of type something is (`LoggerInterface`, `AbstractLogger`,
`LoggerImpl`).

**Why not here:** [principle 2](design-principles.md#2-self-contained-infrastructure-replaceable-by-design)
keeps implementations simple until a real need appears, and
[principle 8](design-principles.md#8-types-name-concepts-not-just-data) names
types after the concept.

**Instead:** start with one concrete class, named after the concept. When a
second implementation is needed, turn that name into the interface and give
each implementation a name that says how it differs (`Simple…`,
`WordPress…`, `Option…`). Every type hint, `instanceof` and `catch` keeps
working. Only the composition root changes, because it is the only place that
creates collaborators (principle 5), and nothing extends a `final` class
(principle 6).

An interface earns its place from the start when a test needs a second
implementation, as `Clock` does: the tests use a clock that stands still.

**Proven by:** `FreshnessPolicy` and `OptionFreshnessPolicy`
(`src/Freshness/OptionFreshnessPolicy.php:25`), `Clock` and `WordPressClock`,
`StalePosts` and `WordPressStalePosts`, and in the infrastructure `DebugMode`
and `WordPressDebugMode`, `Injector` and `SimpleInjector`.

**Rules:** `architecture.finalByDefault`, `architecture.namesWithoutTypeKind`.

## I need to print output

**What people reach for:** `echo '<div>' . $value . '</div>';` inside a
service.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
(HTML echoed from business logic) and
[principle 9](design-principles.md#9-never-trust-what-wordpress-hands-over):
output is escaped for the context it lands in.

**Instead:** render a view from the injected `ViewFactory` and echo the result,
or return it from a filter. In the template, values read as properties are
escaped already. A value read with `raw()` is the explicit exception: escape it
yourself, with the `esc_*` function for where it is printed.

```php
// views/post-age-column.php
$age = $this->raw( 'age' );
?>
<time datetime="<?= esc_attr( $age->last_updated()->format( DATE_W3C ) ) ?>">
	<?= esc_html( sprintf( __( '%s ago', 'content-freshness' ), human_time_diff( … ) ) ) ?>
</time>
```

**Proven by:** `src/PostsList/PostAgeColumn.php:184-192` and
`views/post-age-column.php:16-40`; `views/freshness-setting-field.php:15-30` prints
only properties, which the view escapes for HTML and attributes alike.

**Rules:** `architecture.outputOnlyThroughViews` requires direct method
calls on typed `Renderable` or `View` objects when printing from services.
`architecture.rawIsEscaped` checks raw values in templates, and PHPCS
(WordPress Coding Standards) checks escaping.

## I need to report a failure

**What people reach for:** returning `false`, `null` or a `WP_Error`, calling
`error_log()` or `trigger_error()`, or throwing a bare `RuntimeException`.

**Why not here:** [principle 10](design-principles.md#10-errors-are-exceptions):
inside the plugin, every error is an exception, and exceptions never reach the
end user.

**Instead:** throw a dedicated exception from `src/Exception/`, created through
a named constructor that states the cause, and pass a caught exception on as
the previous one. Where control goes back to WordPress, catch it: loud with
debug mode on, degrade gracefully with it off.

```php
final class InvalidMaxAge extends InvalidArgumentException implements ContentFreshnessException {

	public static function from_value( $value ): self {
		return new self(
			\sprintf( 'The maximum age "%s" is not a whole number of days.', self::stringify( $value ) )
		);
	}
}
```

```php
// The edge: a filter callback hands control back to WordPress.
try {
	// …
} catch ( ContentFreshnessException $exception ) {
	if ( $this->debug_mode->is_debug_mode() ) {
		throw $exception;
	}

	return $content; // The post shows without the notice.
}
```

**Proven by:** `src/Exception/InvalidMaxAge.php:50-57`,
`src/Notice/OutdatedPostNotice.php:129-158` and the end-to-end tests,
which store a broken setting and check both modes. A translated cause is kept
in `src/Infrastructure/View/SimpleView.php:114-123`.

**Rules:** `architecture.noWordPressErrorMechanisms`, `architecture.noFalseOnFailure`,
`architecture.throwOnlyPluginExceptions`, `architecture.exceptionShape`,
`architecture.exceptionsThroughNamedConstructors`,
`architecture.exceptionsKeepCause`.

## I need to run code when the plugin file loads

**What people reach for:** calls, `define()` or `add_action()` at the top of a
file in `src/`.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
and [principle 12](design-principles.md#12-extension-points-run-at-a-hook-never-at-include-time):
at include time, plugins loaded later have had no chance to hook in, and an
autoloaded file that does something on include does it whenever any class
loads it.

**Instead:** put the work in a service and let `register()` hook it in at the
right action. The main plugin file is the only file with side effects, and it
only loads the autoloader, hooks activation and deactivation, declares the
uninstall bridge, and registers the plugin.

**Proven by:** `content-freshness.php:74-156`.

**Rules:** `architecture.noSideEffectsOnInclude`;
`architecture.bootstrapContract` limits the main file's hook API calls to
registering one `plugins_loaded` action and the activation and deactivation
hooks, leaving extension points for registration.

## I need a name in a space WordPress shares

**What people reach for:** a short, readable name such as `settings`,
`max_age` or `admin-script`.

**Why not here:** [principle 13](design-principles.md#13-one-plugin-to-arbitrary-complexity):
several plugins built from the scaffold run side by side, and a name without
the plugin's prefix can clash with any of them.

**Instead:** start every hook, option, transient, meta key, asset handle,
post type, taxonomy, shortcode, REST namespace, cron hook, admin page slug,
setting group/name, AJAX action and service identifier with the plugin's
prefix. Build hook names and service identifiers from the prefix constants.

```php
protected const HOOK_PREFIX = 'mwpd.content_freshness.';

public const OPTION_NAME = 'mwpd_content_freshness_max_age';

public const CRON_HOOK = 'mwpd_content_freshness_send_digest';
```

**Proven by:** `src/ContentFreshnessPlugin.php:240-256`,
`src/Freshness/OptionFreshnessPolicy.php:32` and `src/Digest/StaleContentDigest.php:47`. The
setting joins WordPress's own `reading` group, which the configuration allows;
its option name carries the prefix.

**Rules:** `architecture.prefixedNames` covers hooks, stored keys, asset
handles, post types, taxonomies, shortcodes, REST namespaces, cron hooks,
admin pages, settings and AJAX actions. Service identifiers and theme
override paths remain tracked in the scaffold
([mwpd/basic-scaffold#29](https://github.com/mwpd/basic-scaffold/issues/29)).
