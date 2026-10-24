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
- [I need to load a script or style](#i-need-to-load-a-script-or-style)
- [I need to set something up when the plugin is activated](#i-need-to-set-something-up-when-the-plugin-is-activated)
- [I need to clean up when the plugin is uninstalled](#i-need-to-clean-up-when-the-plugin-is-uninstalled)
- [I need to create objects on demand](#i-need-to-create-objects-on-demand)
- [I need to choose an implementation at runtime](#i-need-to-choose-an-implementation-at-runtime)
- [I need a service locator](#i-need-a-service-locator)
- [I need a singleton](#i-need-a-singleton)
- [I need to reach the plugin from a static WordPress callback](#i-need-to-reach-the-plugin-from-a-static-wordpress-callback)
- [I need a public API for themes or other plugins](#i-need-a-public-api-for-themes-or-other-plugins)
- [I need WordPress state, such as the current post](#i-need-wordpress-state-such-as-the-current-post)
- [I need a default collaborator that can be replaced](#i-need-a-default-collaborator-that-can-be-replaced)
- [I need to start simple and add implementations later](#i-need-to-start-simple-and-add-implementations-later)
- [I need to print output](#i-need-to-print-output)
- [I need to report a failure](#i-need-to-report-a-failure)
- [I need to run code when the plugin file loads](#i-need-to-run-code-when-the-plugin-file-loads)
- [I need a name in a space WordPress shares](#i-need-a-name-in-a-space-wordpress-shares)
- [I need to see why a service did not run](#i-need-to-see-why-a-service-did-not-run)

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

**Proven by:** `src/ContentFreshnessPlugin.php:94-118` declares the four
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
`Deactivateable` and the site- and network-scoped lifecycle interfaces such
as `SiteActivateable` and `SiteUninstallable`.

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

**Proven by:** `src/PostsList/PostAgeColumn.php:109-121`.

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

**Proven by:** `src/PostsList/PostAgeColumn.php:87-91`. The column is only built on
the Posts list.

When the answer depends on the query, such as `is_singular()`, also implement
`Delayed` and wait for `wp` or a later action as shown in
[I need to hook in later](#i-need-to-hook-in-later).

When the answer depends on the admin screen, such as `get_current_screen()`,
also implement `Delayed` and wait for `current_screen` or a later admin
action, as the column does. WordPress has not set the screen at
`plugins_loaded`, when the plugin normally asks `is_needed()`.

Do not add a condition the registration action already implies.
`FreshnessSetting` waits for `admin_init`, which only fires in admin
requests, so it has no `is_admin()` condition
(`src/Settings/FreshnessSetting.php:38-42`). A condition would also keep it from
taking part in lifecycle events where it is false, such as uninstalling
through WP-CLI, as [I need to clean up when the plugin is uninstalled](#i-need-to-clean-up-when-the-plugin-is-uninstalled)
explains.

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
`get_registration_priority()` places the service among the action's other
callbacks; the `DefaultRegistrationPriority` trait supplies WordPress's default
of 10 for a service that has no reason to choose.

```php
final class OutdatedPostNotice implements Service, Registerable, Conditional, Delayed {

	use DefaultRegistrationPriority; // Registers at priority 10 on the action.

	public static function is_needed(): bool {
		return \is_singular( 'post' ); // Answers correctly, because the query has run.
	}

	public static function get_registration_action(): string {
		return 'wp';
	}
}
```

A service that has to run before or after other callbacks on its action
implements the method itself instead of using the trait:

```php
public static function get_registration_priority(): int {
	return 20; // After the callbacks at the default priority of 10.
}
```

**Dependencies take precedence over the priority.** A `Delayed` service that
also implements `HasDependencies` never registers before its dependencies.
When they are ready before its action, it still waits for the action and
registers at its declared priority. When they are only ready after its
priority on the action has passed, or after the action itself, it registers
as soon as they are, at the end of the action they arrive on.

**Proven by:** `src/Notice/OutdatedPostNotice.php:40-110`, and
`PostAgeColumn`, which waits for `current_screen` before it asks which screen
it is on (`src/PostsList/PostAgeColumn.php:87-99`).

**Rules:** `architecture.noContextChecksInRegister`,
`architecture.queryContextNeedsDelay`, `architecture.registrationActionKnown`.
Hook-timing checks such as `did_action()` in `register()` are reported, as are
unknown registration actions. Whether a known action fires in the service's
request type or runs late enough for its condition remains a review concern.

## I need to load a script or style

**What people reach for:** `add_action( 'wp_enqueue_scripts', … )` in the
service, with `wp_enqueue_style()` and a `plugins_url( 'css/x.css', __FILE__ )`
or a `MY_PLUGIN_URL` constant inside it, and an `is_singular()` or
`get_current_screen()` check to keep the file off pages that do not need it.

**Why not here:** [the core idea](design-principles.md#how-services-map-to-it):
when and where a service acts is the infrastructure's job, so every service
wiring its own enqueue hooks spreads lifecycle logic again.
[Principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
rules out the constant, and [principle 1](design-principles.md#1-runs-on-php-and-wordpress-alone)
any build step to produce the files.

**Instead:** implement `HasAssets` and return what the service needs from
`get_assets()`. The plugin hooks the enqueue actions once, asks every
registered service at each of them, and loads what they return. `Style` and
`Script` are immutable values that a service creates itself, through their
named constructors and chained options. The notice declares its default style
like this:

```php
public function get_assets( AssetContext $context ): iterable {
	if ( ! $context->is_front_end() || ! $this->policy->is_outdated( $this->main_loop->queried_post() ) ) {
		return [];
	}

	return [ Style::file( 'mwpd-content-freshness-notice', 'assets/css/notice.css' ) ];
}
```

Most of "where" is already decided: a service that `Conditional` rules out,
or that `Delayed` has not registered yet, is never asked. The notice only
exists on a single post, so `get_assets()` only has to pick the front end out
of the enqueue points of that request, and to leave a fresh post alone:

| Context | WordPress action |
| --- | --- |
| `AssetContext::front_end()` | `wp_enqueue_scripts` |
| `AssetContext::admin( $hook_suffix )` | `admin_enqueue_scripts`; `is_admin( 'settings_page_x' )` checks the screen |
| `AssetContext::block_editor()` | `enqueue_block_editor_assets`, for the editor's own interface |
| `AssetContext::login()` | `login_enqueue_scripts` |

Several of them can run in one request: the block editor fires its own action
and then the admin one. Because the plugin asks at the action, the answer can
also depend on the state of the request. The head comes before the loop, so
the notice asks about the queried post there, not about what the loop is
rendering; printed in the head, the style is in place before the notice shows.

- **Where the files are served from** comes from the main file: its directory
  URL, and its `Version` header as the version of every asset that sets none
  with `with_version()`. A missing header is an `InvalidAsset`, never a silent
  fallback. To serve the assets from elsewhere, delegate `AssetLocation` in
  the composition root.
- **A few lines of CSS** can be printed inline with `Style::inline()`, which
  saves a request and can still be dequeued by its handle.
- **Scripts** can be `in_footer()`, `deferred()` or `async()`. WordPress before
  6.3 ignores the strategy and loads the script the classic way.
- **A service registered late**, after an enqueue point has run, is asked for
  it at once. WordPress then prints a style in the footer, or not at all once
  the footer has been printed, so register a service with assets no later than
  it needs them.
- **Two services may declare the same asset.** Declaring a handle again with
  another file or other options is a conflict: debug mode throws, production
  skips the service's assets for that enqueue point and loads the others.
- **A failure while declaring** is caught by the plugin's enqueue listener, as
  the service's own hook callbacks catch theirs: loud with debug mode on, and
  no asset with it off, while the other services still load theirs.

**Proven by:** `src/Notice/OutdatedPostNotice.php:139-170` (a style on
outdated posts), the orchestration in
`src/Infrastructure/ServiceBasedPlugin.php:1936-2140`, and
`tests/php/Unit/AssetLoadingTest.php` with
`tests/php/Integration/Notice/OutdatedPostNoticeStyleTest.php`.

**Rules:** `architecture.prefixedNames` checks the handle where a service
declares it, in `Style::file()`, `Style::inline()` and `Script::file()`.
`architecture.capabilitiesOnServices` accepts a `Delayed` service whose only
work is its assets.

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

So a service with rewrite rules sets them up in `activate()` and removes them
in `deactivate()`, rather than relying on its normal registration having run
in that request. Whether it has depends on how the plugin was activated. The
example is illustrative: Content Freshness has no post type, and its one
service with setup, the digest, works per site, as the next section shows.

```php
final class BookPostType implements Service, Registerable, Delayed, Activateable, Deactivateable {

	use DefaultRegistrationPriority;

	public static function get_registration_action(): string {
		return 'init';
	}

	public function register(): void {
		$this->register_post_type();
	}

	public function activate(): void {
		$this->register_post_type(); // Present for the flush that follows.
	}

	public function deactivate(): void {
		\unregister_post_type( self::POST_TYPE ); // Gone for the flush that follows.
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
  must run whatever the condition belongs in a separate service without it.
- **`HasDependencies` does not order lifecycle calls.** It only governs
  registration, and no dependency is registered early for a lifecycle call.
  Steps that must run in sequence belong in one service that runs them in
  that order.

### On multisite: in every site, or once for the network

`activate()` and `deactivate()` run once, in the site the plugin is
activated in. When the work belongs in each site, or once for a whole
network, implement the scoped interfaces instead. Each one receives the
`SiteContext` or `NetworkContext` it runs in:

| Interface | Called |
| --- | --- |
| `SiteActivateable`, `SiteDeactivateable` | In the current site, or in every site of the network for a network activation or deactivation, with that site switched to. A network deactivation skips a site that keeps the plugin activated on its own, and deactivating one site tears nothing down while the plugin stays network-active. |
| `NetworkActivateable`, `NetworkDeactivateable` | Once for a network activation or deactivation, in the network's main site. Setup runs for the network first, then the sites; teardown runs the sites first. |
| `SiteInitializable` | In a new site, once core has set it up, when the plugin is network-active on the new site's network. |
| `SiteUninitializable` | In a site about to be deleted, before core drops its tables, whether or not the plugin is still active there. |

The digest is the plugin's example. Each site has its own posts, editors and
WP-Cron events, so it schedules its weekly event in every site an activation
reaches, and in a site created later on a network where the plugin is
network-active. Its `register()` only hooks the callback, in every request,
because WP-Cron runs in whatever request comes along:

```php
final class StaleContentDigest implements
	Service,
	Registerable,
	SiteActivateable,
	SiteDeactivateable,
	SiteInitializable,
	SiteUninstallable {

	public function register(): void {
		\add_action( self::CRON_HOOK, [ $this, 'send' ], 10, 0 );
	}

	public function activate_site( SiteContext $context ): void {
		$this->schedule(); // In the site that is switched to.
	}

	public function initialize_site( SiteContext $context ): void {
		$this->schedule(); // A site added after the network activation.
	}

	public function deactivate_site( SiteContext $context ): void {
		$this->unschedule();
	}

	private function schedule(): void {
		if ( false !== \wp_next_scheduled( self::CRON_HOOK ) ) {
			return; // Activating twice must not send two digests a week.
		}
		// …
	}
}
```

Things to know:

- **A scoped interface replaces the parameterless one.** A service that
  implements any scoped interface of an event no longer receives `activate()`
  or `deactivate()` for that event, in any scope, even where its scoped
  interface does not apply. Setup that several methods share goes into a
  private method each of them calls, as `schedule()` above, so it never runs
  twice.
- **Each site gets an object graph of its own.** The plugin builds it after
  switching to the site: a fresh injector, the composition root's
  configuration and services applied there with their filters, and
  `is_needed()` asked there. Shared instances, such as the plugin's
  `FreshnessPolicy`, are shared within that site only, nothing goes into the
  plugin's container and nothing is registered.
- **Address network options by ID.** Switching sites does not switch networks,
  so use `$context->network_id()` with `get_network_option()` and its
  siblings.
- **The work is done when the call returns.** Sites are visited one after the
  other in the activation request, looked up in pages: that bounds memory, not
  time, so a large network can run into the time limit. A custom `SiteRunner`
  passed to the plugin constructor replaces the default, for example one that
  is driven from WP-CLI.
- **A failure stops the event, visibly.** The first exception propagates after
  the site context is restored, in debug mode and in production alike
  ([principle 10](design-principles.md#10-errors-are-exceptions)). Sites
  already done stay done, so run the activation again once the cause is fixed.
  The digest's `FailedToScheduleDigest` takes that path.
- **New and deleted sites are only reached where the plugin is loaded.** A
  site created from a network where the plugin is not active, or a site
  deleted from the network admin while the plugin is only active on that one
  site, is not reached, because WordPress does not load the plugin in that
  request.
- **The soft flush of the rewrite rules happens once,** in the site the event
  starts in.

**Proven by:** `src/Digest/StaleContentDigest.php:49-245`, and
`tests/php/Integration/LifecycleTest.php:109-122`, which activates the real composition root
on an in-memory multisite installation. The infrastructure is covered by
`tests/php/Unit/ScopedLifecycleTest.php` and
`tests/php/Unit/SiteLifecycleHooksTest.php`, and the end-to-end tests check
that activating twice keeps one weekly event.

**Rules:** `architecture.compositionRootContracts` runs `activate()` and
`deactivate()`, and the scoped methods, twice and reports a second run that
changes anything, so guard each step or make it set rather than add.

## I need to clean up when the plugin is uninstalled

**What people reach for:** an `uninstall.php` that deletes options with
`delete_option()` in the current site only, or `register_uninstall_hook()` at
the top of the main plugin file with a closure.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns):
the main plugin file is the only file with side effects, and an
`uninstall.php` would be a second one. WordPress stores the uninstall
callback in the database and only accepts a function or a static method, so
it needs a bridge into the object graph
([principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)).
Registering it at include time would write to the database on every request,
and would even add the entry back while WordPress is uninstalling the plugin.
Deleting only in the current site leaves the data of every other site on a
network behind. And [the core idea](design-principles.md#how-services-map-to-it):
each service cleans up its own data.

**Instead:** implement `SiteUninstallable` for what the service keeps in
each site and `NetworkUninstallable` for what it keeps for a network, in a
service without a condition. The composition root names the main plugin file
and the uninstall bridge, and the plugin registers the bridge when it is
activated:

```php
// src/ContentFreshnessPlugin.php
protected function get_uninstall_callback(): array {
	return [ ContentFreshnessUninstallHook::class, 'uninstall' ];
}
```

The setting owns the option, so it removes it, from every site:

```php
final class FreshnessSetting implements Service, Registerable, Delayed, SiteUninstallable {

	public function uninstall_site( SiteContext $context ): void {
		\delete_option( OptionFreshnessPolicy::OPTION_NAME ); // Fine if it is gone already.
	}
}
```

The digest clears any weekly event deactivation did not reach. Content
Freshness keeps nothing for a whole network, so it has no
`NetworkUninstallable`; one would address network options by
`$context->network_id()`, because switching sites does not switch networks.

When the plugin is deleted, WordPress includes its main file and calls the
bridge, which hands over to `ServiceBasedPlugin::uninstall()`. On a single
site, that cleans up the current site. On multisite, it visits every site of
every network, whether or not the plugin was ever active there, then calls
the network cleanup once for each network, in its main site. Each call gets
an object graph of its own, built in the site it runs in, so a `Conditional`
service is asked there. Nothing else is built or registered, even if
`plugins_loaded` fires afterwards, and `WP_UNINSTALL_PLUGIN` plays no part.

Things to know:

- **Keep cleanup unconditional.** A `Conditional` cleanup service only runs
  in the sites where its condition holds in the uninstall request, which is
  rarely what the condition was written for, and data stays behind elsewhere.
  That is why `FreshnessSetting` has no `is_admin()` condition: deleting the
  plugin through WP-CLI is not an admin request.
- **Only the plugin's own data, and absence is fine.** Delete prefixed names
  only, and expect that a site never had the data or that an earlier attempt
  already removed it.
- **Deactivation is not uninstalling.** A site that deactivates the plugin
  for a while keeps its setting. Stop behaviour in `deactivate_site()`, and
  remove data in `uninstall_site()`.
- **A failure stops the uninstall, visibly.** The first exception propagates
  after the site context is restored; later sites and networks are not
  visited, and what was cleaned up stays cleaned up. Before the exception
  leaves, the plugin registers its uninstall callback again in the site the
  plugin was deleted in, so deleting it again, once the cause is fixed,
  retries the cleanup from the start.
- **Where the callback is registered.** Activation registers it on the
  activating site and on the main site of every network, because each
  network admin reads it from its main site. A successful uninstall removes
  those copies. A network added later, or a network whose main site changes,
  gets the callback at the next activation. A site that activated version
  1.0.0 still has the callback that version stored, a function in the main
  file; the function stays and hands over to the bridge
  (`content-freshness.php:163-165`), until the next activation replaces the
  callback. Updating does not run activation, so a network where 1.0.0 is
  network-active is deactivated and activated again once, to set up the
  digest in every site and the callback on every network's main site.
- **Large installations.** The uninstall runs within the request that deletes
  the plugin. Sites are looked up in pages, which bounds memory but not time,
  so a very large installation can run into the time limit. A custom
  `SiteRunner`, for example one driven from WP-CLI, is the answer.

**Proven by:** `src/Settings/FreshnessSetting.php:137-139`, the bridge in
`src/ContentFreshnessUninstallHook.php:31-39`, and
`tests/php/Integration/LifecycleTest.php:179-199`. The end-to-end
tests delete the plugin in WordPress and check that the setting and a digest
left behind are gone. The infrastructure is covered by
`tests/php/Unit/UninstallLifecycleTest.php` and
`tests/php/Unit/UninstallBridgeTest.php`.

**Rules:** `architecture.pluginFactoryOnlyAtEdges` lists the bridge,
`architecture.bootstrapContract` keeps `register_uninstall_hook()` out of the
main file, and `architecture.compositionRootContracts` runs
`uninstall_site()` and `uninstall_network()` twice and reports a second run
that changes anything.

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
		try {
			if ( $this->locations === [] ) {
				$this->locations = $this->get_default_locations( $relative_path );
			}

			return new TemplatedView( $relative_path, $this, $this->locations, $this->debug_mode );
		} catch ( Throwable $throwable ) {
			// … rethrow in debug mode, render nothing in production.
			return new EmptyView();
		}
	}
}
```

`TemplatedViewFactory` only stores its optional collaborators in its
constructor. When no locations are passed, it works out the theme
directories and the plugin root on the first `create()` call, from the
`ViewConfiguration` the composition root hands it, and keeps them for later
calls. Explicit locations keep their order and bypass theme lookups; the
unit tests pass the plugin directory alone.

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

Never bind `Injector` in the composition root's bindings or the plugin's
bindings filter: it is reserved, and either binding is rejected with
`InvalidConfiguration`. Services must receive the plugin's own injector. To
use a custom implementation, pass that injector to the plugin constructor
instead:

```php
$plugin = new ContentFreshnessPlugin( true, $custom_injector );
```

**Proven by:** `src/PostsList/PostAgeColumn.php:109-113` and
`src/PostsList/PostAgeColumn.php:194-197`, `src/Infrastructure/View/TemplatedViewFactory.php:39` and
`src/Infrastructure/View/TemplatedViewFactory.php:90-111`, bound at `src/ContentFreshnessPlugin.php:147`.
The injector is made injectable at
`src/Infrastructure/ServiceBasedPlugin.php:1219-1222`, proven by
`tests/php/Unit/ServiceBasedPluginTest.php:1013-1047`.

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
| Access from a theme or another plugin | [The plugin's hooks](#i-need-a-public-api-for-themes-or-other-plugins) |

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

**Proven by:** `src/ContentFreshnessPlugin.php:219-228` shares the policy;
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
// content-freshness.php
\register_activation_hook(
	__FILE__,
	static function ( $network_wide = false ): void {
		ContentFreshnessPluginFactory::create()->activate_in( LifecycleScope::from_wordpress_flag( $network_wide ) );
	}
);
```

```php
// src/ContentFreshnessUninstallHook.php
final class ContentFreshnessUninstallHook {

	public static function uninstall(): void {
		ContentFreshnessPluginFactory::create()->uninstall();
	}
}
```

The activation hook also turns WordPress's network flag into a
`LifecycleScope` at the boundary, so the plugin never handles the raw value.
The uninstall bridge is a class of its own, because WordPress stores the
uninstall callback in the database: the plugin registers it when it is
activated, never while the main file is included.

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
  before control goes back to WordPress. The activation hooks do not catch
  yet, as in the scaffold
  ([mwpd/basic-scaffold#143](https://github.com/mwpd/basic-scaffold/issues/143)).
  The uninstall bridge is the approved exception: a failed uninstall
  propagates, so that it is visible and can be retried.

**Proven by:** `content-freshness.php:130-152`,
`src/ContentFreshnessUninstallHook.php:31-39`, listed as a bridge in `phpstan.neon.dist`, and the
factory at `src/ContentFreshnessPluginFactory.php:45-62`.

**Rules:** `architecture.noStaticState` allows static state only in the
factory. [`architecture.pluginFactoryOnlyAtEdges`](architecture-rules.md#architecturepluginfactoryonlyatedges)
reports calls to the factory outside the main file and methods listed in
`parameters.architecture.pluginFactoryOnlyAtEdges.bridges`.

## I need a public API for themes or other plugins

**What people reach for:** a static `ContentFreshness::get( 'policy' )`, a file of
global template-tag functions, or telling other developers to call
`ContentFreshnessPluginFactory::create()->get_container()->get( $id )`, because
nothing stops them.

**Why not here:** [principle 13](design-principles.md#13-one-plugin-to-arbitrary-complexity)
makes the plugin's hooks its public integration surface, and
[principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
gives code outside the plugin no way into the object graph. A static lookup
by identifier is the service locator from the
[principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
table. A typed facade or template-tag functions could avoid that, but each
would be a new public API with its own contract to keep, and none is part of
the supported surface for now. The container door has three problems of its
own:

- **It hands out more than services.** Next to the registered services, the
  container holds the injector that builds their collaborators, under
  `mwpd.content_freshness.injector`
  (`src/Infrastructure/ServiceBasedPlugin.php:1236-1240`), so outside code could
  call `bind()`, `share()` or `delegate()` on it, which
  `architecture.noServiceLocator` forbids even the plugin's own subsystems.
- **Whether a service is there depends on the moment.** `get()` throws
  `InvalidService` (`src/Infrastructure/ServiceContainer/SimpleServiceContainer.php:41-43`)
  before `plugins_loaded`, for a `Conditional` service that this request does
  not need, and for a `Delayed` service whose action has not fired yet. None
  of that is visible from the identifier.
- **It returns concrete classes.** Services are `final` classes, so every
  public method on one would become API by accident.

**Instead:** integrate through hooks. There are two kinds:

- **The plugin's configuration filters.** While it registers, the plugin
  applies its `services` filter and the injector filters `bindings`,
  `arguments`, `shared_instances` and `delegations`, each with its hook
  prefix (`mwpd.content_freshness.services`, and so on). That happens at
  `plugins_loaded`, once every plugin has been loaded but before the theme
  is, so these filters are for other plugins. A service added this way is
  built by the plugin's injector and receives its collaborators through its
  constructor, like any other service.
- **The hooks your services offer.** When a feature should be open to other
  code, its service applies a prefixed filter or fires a prefixed action at
  the point where it acts, and you document it, including when it fires. A
  hook that fires after the theme has loaded, such as one on `init`, `wp` or
  a rendering hook, serves themes as well as plugins; one that fires during
  registration at `plugins_loaded` comes too early for a theme. Treat what
  comes back from a filter as untrusted input
  ([principle 9](design-principles.md#9-never-trust-what-wordpress-hands-over)).

A theme can also change the plugin's markup without any hook: the views
look in the plugin's own override directory in the child theme, then in the
parent theme, before the plugin
(`src/Infrastructure/View/TemplatedViewFactory.php:25-37`). The notice, for example, is overridden by
`<theme>/content-freshness/views/outdated-post-notice.php`.

```php
// In another plugin, before plugins_loaded: change the freshness rule.
\add_filter(
	'mwpd.content_freshness.bindings',
	static function ( $bindings ) {
		if ( is_array( $bindings ) ) {
			$bindings[ \MWPD\ContentFreshness\Freshness\FreshnessPolicy::class ] = Evergreen_Aware_Policy::class;
		}

		return $bindings; // Anything else is left for the plugin to reject.
	}
);
```

The notice, the column and the digest all ask the bound `FreshnessPolicy`,
so all three follow the new rule, and none of them changes.

Service identifiers are how these filters name services: they key the
`services` array, and `HasDependencies` lists them. They are not handles for
fetching a service from outside.

**Not supplied, for now.** The plugin offers no other public PHP API: no
static facade, no template-tag functions file, no generic service lookup and
no hook that hands an API object over. `ContentFreshnessPluginFactory::create()`
and `Plugin::get_container()` stay public because the main file, the listed
bridges and the tests need them; that makes them callable, not supported for
other code. Two investigations are open, and neither is approved:

- [mwpd/basic-scaffold#144](https://github.com/mwpd/basic-scaffold/issues/144) looks at an API for extensions that need more than hooks, including
  the order in which several extensions register. It waits for a concrete
  extension that hooks cannot serve.
- [mwpd/basic-scaffold#145](https://github.com/mwpd/basic-scaffold/issues/145) looks at helpers that a template calls through `$this` while the
  plugin renders it. They would only reach the plugin's own views, including
  a theme's overrides of them, never a theme's other files or another plugin.

**Proven by:** the configuration filters at
`src/Infrastructure/ServiceBasedPlugin.php:1305-1308`, and
`tests/php/Integration/Notice/OutdatedPostNoticeTest.php:78-101`, which rebinds the
policy through the bindings filter and changes the notice without touching
it.

**Rules:** none checks code outside the plugin, so this boundary is held by
documentation. Inside the plugin, `architecture.pluginFactoryOnlyAtEdges`
keeps factory calls to the main file and listed bridges, and
`architecture.prefixedNames` checks that the hooks your services add carry the
plugin's prefix.

## I need WordPress state, such as the current post

**What people reach for:** `global $post;`, `global $wpdb;`, or `get_post()`
deep inside a service. Or a `WP_Post` in the constructor, produced by a
delegation that calls `get_post()`.

**Why not here:** [principle 7](design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns)
(collaborators pulled from globals) and
[principle 9](design-principles.md#9-never-trust-what-wordpress-hands-over):
what WordPress hands over is checked once, at the boundary. A `WP_Post` in
the constructor keeps both, but it is the wrong post most of the time. The
service is built once, at `wp`, and `the_content` runs many times after
that, and not only for the queried post: for every post in a related-posts
block or a secondary loop, for every post in a feed, and whenever WordPress
builds an excerpt from the content, for example for a meta description. A
post fixed at build time gives each of them the queried post's data.

**Instead:** when the state changes while the request runs, put a small
interface in front of it, and ask that interface each time the callback runs.
Its WordPress implementation is the boundary: it reads WordPress's query
functions when it is asked, checks what they return, and fails with a
dedicated exception when it is not what the service needs. The composition
root binds the interface to that implementation.

```php
// src/Notice/MainLoop.php: what the service needs to know.
interface MainLoop {
	public function is_rendering_queried_post(): bool;
	public function queried_post(): WP_Post; // Throws FailedToFindPost.
}
```

```php
// src/Notice/WordPressMainLoop.php: the boundary.
public function is_rendering_queried_post(): bool {
	return \in_the_loop()
		&& \is_main_query()
		&& ! \is_feed()
		&& ! \doing_filter( 'get_the_excerpt' )
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

A test then passes a double of the interface, and needs no WordPress
functions to tell the service which post is being rendered.

A delegation in the composition root still builds a value the injector
cannot, but it runs once, when the service is built. Use it for values that
are fixed for the request, such as where the plugin is
(`ViewConfiguration` in `src/ContentFreshnessPlugin.php`), never for the
current post.

Calling a WordPress function inside an implementation detail is still fine
([principle 4](design-principles.md#4-solid-at-the-top-wordpress-at-the-edges)).
The interface is for state a service depends on.

**Proven by:** `src/Notice/MainLoop.php:28-47` and
`src/Notice/WordPressMainLoop.php:25-65`, bound at
`src/ContentFreshnessPlugin.php:169`, and asked at call time in
`src/Notice/OutdatedPostNotice.php:190-196`.
`tests/php/Unit/Notice/WordPressMainLoopTest.php` covers each state of the
loop, and the end-to-end tests check that feeds and lists of posts get no
notice.

For request input, a boundary adapter reads the superglobals, sanitizes and
normalizes them into an internal type, and supplies that value to the service.
List the adapter class in `parameters.architecture.noGlobalState.inputAdapters`;
this permits request superglobals, never `global` or `$GLOBALS`. Form input
that WordPress already reads for you, such as a setting saved through the
Settings API, arrives through the sanitize callback instead
(`src/Settings/FreshnessSetting.php:152`).

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
public function __construct(
	string $path,
	ViewFactory $view_factory,
	?DebugMode $debug_mode = null,
	?ViewConfiguration $configuration = null
) {
	// …
	$this->debug_mode = $debug_mode ?? new WordPressDebugMode();
}
```

**Proven by:** `src/Infrastructure/View/SimpleView.php:86-96`, and the
injector and container defaults of `ServiceBasedPlugin`
(`src/Infrastructure/ServiceBasedPlugin.php:309-344`).

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

A view that cannot be created or rendered throws in debug mode and renders
an empty string in production, so a broken template leaves its own place on
the page empty while the rest of the page renders. The plugin's services
still catch around rendering, because what they render depends on the
freshness policy too, which can fail on its own; see
[I need to report a failure](#i-need-to-report-a-failure).

```php
// views/post-age-column.php
$age = $this->raw( 'age' );
?>
<time datetime="<?= esc_attr( $age->last_updated()->format( DATE_W3C ) ) ?>">
	<?= esc_html( sprintf( __( '%s ago', 'content-freshness' ), human_time_diff( … ) ) ) ?>
</time>
```

**Proven by:** `src/PostsList/PostAgeColumn.php:189-197` and
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

The view system does this for views on its own: the view catches a failing
template, throws in debug mode and renders nothing in production, and the
view factories hand out an `EmptyView` in production for a view they cannot
create. An exception from anywhere else, such as a service's own code in a
hook callback, is not caught by the scaffold yet
([mwpd/basic-scaffold#143](https://github.com/mwpd/basic-scaffold/issues/143)),
so each of the plugin's hook callbacks catches its own.

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
`src/Notice/OutdatedPostNotice.php:190-219` and the end-to-end tests,
which store a broken setting and check both modes. A translated cause is kept
in `src/Infrastructure/View/SimpleView.php:135-137`.

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
only loads the autoloader, hooks activation and deactivation, keeps the
uninstall callback of version 1.0.0, and registers the plugin. Even the
uninstall callback is not registered there: activation stores it, because
WordPress keeps it in the database.

**Proven by:** `content-freshness.php:76-171`.

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
Theme overrides of the plugin's views live in a directory of their own,
named by `VIEW_OVERRIDE_DIRECTORY`: a theme overrides
`views/outdated-post-notice.php` with
`<theme>/content-freshness/views/outdated-post-notice.php`, never with its own
`views/outdated-post-notice.php`.

```php
protected const HOOK_PREFIX = 'mwpd.content_freshness.';

protected const VIEW_OVERRIDE_DIRECTORY = 'content-freshness';

public const OPTION_NAME = 'mwpd_content_freshness_max_age';

public const CRON_HOOK = 'mwpd_content_freshness_send_digest';
```

**Proven by:** `src/ContentFreshnessPlugin.php:325-357`,
`src/Freshness/OptionFreshnessPolicy.php:32` and `src/Digest/StaleContentDigest.php:62`. The
setting joins WordPress's own `reading` group, which the configuration allows;
its option name carries the prefix.

**Rules:** `architecture.prefixedNames` covers hooks, stored keys, asset
handles, post types, taxonomies, shortcodes, REST namespaces, cron hooks,
admin pages, settings and AJAX actions. `architecture.serviceIdsPrefixed`
checks declared service identifiers against the composition root's own
`SERVICE_PREFIX`. IDs added by runtime filters and the value of
`VIEW_OVERRIDE_DIRECTORY` are reviewed by hand.

## I need to see why a service did not run

**What people reach for:** `error_log()` calls in `is_needed()` and
`register()`, or a debug panel that reads the service container through a
static accessor and lists what it finds there.

**Why not here:** [principle 10](design-principles.md#10-errors-are-exceptions)
keeps `error_log()` out of `src/`, and
[principle 5](design-principles.md#5-one-composition-root-builds-the-object-graph)
rules out the static accessor. The container would not answer the question
anyway: it holds lazily built services as a wrapper that hides their class,
and it knows nothing about a service that was skipped, or that still waits for
its action or its dependencies. Asking each condition again to find out would
run application code a second time, at another point in the request, and
could get another answer.

**Instead:** declare the optional Query Monitor panel. It is one more entry in
the composition root's services, which ships commented out
(`src/ContentFreshnessPlugin.php:107-115`):

```php
// src/ContentFreshnessPlugin.php, in get_service_classes()
self::REGISTRATION_PANEL_ID => RegistrationPanel::class,
```

The bindings and arguments it needs are already in the composition root, so
nothing else changes. In a request where [Query Monitor](https://wordpress.org/plugins/query-monitor/)
runs, its menu then has a panel, "Content Freshness services". It lists every service
with the class its declaration resolved to and its current state: registered,
skipped, waiting for an action, waiting for dependencies, or failed, with the
action or the dependency identifiers as evidence. Below that is the full
history of the request, one row for each decision, in the order the plugin
made them, so a service that waited for `init` and was then registered shows
both.

How it works, and what it costs:

- **Nothing happens without both the entry and a running Query Monitor.**
  Without the entry, no class of the panel is loaded. With it, the plugin asks
  the panel once, before it schedules any service, whether it observes the
  registration (`ObservesRegistration`). The panel says yes only while Query
  Monitor runs and supports the request, which it checks without loading
  anything of Query Monitor
  (`src/Infrastructure/QueryMonitor/RegistrationPanel.php:240-245`). Otherwise
  the plugin records nothing and creates no log, and the panel is skipped like
  any other `Conditional` service. Query Monitor only tells a Customizer
  preview apart later in the request, so the panel stays off in every request
  that loads the Customizer, its own screen included.
- **The history is recorded as it happens.** While the panel observes, the
  plugin writes each decision into a `RegistrationLog`, request by request and
  in memory only. It costs one small record per decision and nothing is
  stored. Nothing asks a condition or a resolver again, and no lazy service is
  built to be shown. The panel receives the history through the read-only
  `RegistrationReport`; no service can receive the writer.
- **Query Monitor's classes stay behind factories.** The collector and the
  output of the panel extend Query Monitor's classes, so their factories
  declare them as anonymous classes and only create them once Query Monitor
  asks for the panel. The panel is a view,
  `views/query-monitor-registration.php`, escaped like any other; Query
  Monitor prints it on the server and shows it when the panel is opened.
- **Supported release:** Query Monitor 4.0.7. The panel relies on its
  `QueryMonitor` class only being loaded when it runs, on `QM_Collectors::add()`
  and on the `qm/outputter/html` and `qm/output/menus` filters.
- **A custom log** can be passed to the plugin constructor as the last
  optional argument, as in `new ContentFreshnessPlugin( true, null, null, null, null, $log )`.
  It is only written to while a service observes the registration.
- **An override can bypass the record.** `schedule_potential_service_registration()`
  and `maybe_register_service()` are protected and record their decisions; an
  override that does not call the parent method records nothing for the
  services it handles.

Another service can show the same history: implement `ObservesRegistration`,
return whether it shows it in this request, and ask for a `RegistrationReport`
in its constructor.

**Proven by:** `tests/php/Integration/QueryMonitorPanelTest.php`, which declares
the panel next to the plugin's four services and finds the notice waiting for
`wp` and the digest registered; the recording by
`tests/php/Unit/RegistrationRecordingTest.php`; the panel, its quiet path and
both plugin load orders by `tests/php/Unit/RegistrationPanelTest.php`, against
stand-ins in `tests/php/Fixture/QueryMonitor/`.

**Rules:** `architecture.capabilitiesOnServices` keeps `ObservesRegistration`
on services, `architecture.outputOnlyThroughViews` puts the panel's markup in a
view, and the bundled autoloader check loads every class of the panel without
Query Monitor.
