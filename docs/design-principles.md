# Design principles

These are the goals every technical decision in Content Freshness is measured
against. When a change has more than one reasonable shape, pick the one that
fits them. When a change would contradict one, it needs a maintainer ruling
before any code is written.

The plugin is built on the [`mwpd/basic-scaffold`](https://github.com/mwpd/basic-scaffold)
plugin template and inherits its principles. They are the reason the
infrastructure in `src/Infrastructure/` looks the way it does, and the reason
the four features look the way they do.

## The core idea: complexity that grows linearly

Code that hooks into WordPress has to act at different points in time, under
different conditions, across several subsystems. Written the usual way, every
new piece of logic has to account for every existing one: nested conditionals
spread through the codebase, and each addition multiplies the combinations that
can go wrong. Complexity grows exponentially.

This plugin linearizes that complexity. Every concern is split off into its
own **service** (or a subsystem of services). Each service decides, in
isolation, where and when it acts within the WordPress lifecycle. The plugin's
infrastructure then works out when to trigger what across all of them. Adding
logic means adding one more service that manages its own lifecycle, not
threading one more condition through all the others.

Content Freshness shows the result. A reader's notice, a column in the Posts
list, a field under Settings › Reading and a weekly email act in four
different kinds of request, and the four services that implement them never
mention each other.

### What "the WordPress lifecycle" means here

The lifecycle is everything WordPress does between receiving a request and
shutting down, and every point where the plugin API lets code run along the
way:

- **Boot**: WordPress loads its configuration, must-use plugins, then
  network-activated and site-activated plugins (`plugins_loaded`), then the
  theme (`after_setup_theme`), and finishes setting up (`init`, `wp_loaded`).
- **Routing and output**: depending on the request type (front end, admin,
  AJAX, REST, cron or WP-CLI), WordPress parses the request and runs the query
  (`wp`), then renders the response through its template and output hooks
  (`template_redirect`, `wp_head`, `the_content`, `admin_notices`, …).
- **Shutdown**: the `shutdown` action, then PHP ends the request.
- **Plugin lifecycle events**, which happen outside the normal request flow:
  activation and deactivation (including network activation on multisite),
  uninstall, and upgrades.

The same plugin code runs in all of these contexts. Many WordPress conditions
are only meaningful after a certain point: `is_singular()`, for example, only
answers once the query has run. A hook that has already fired does not fire
again. These two facts are what make lifecycle logic hard to get right.

For the full picture, see the Plugin Handbook on
[hooks](https://developer.wordpress.org/plugins/hooks/),
[activation and deactivation](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/)
and [uninstall](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/),
and the [action reference](https://developer.wordpress.org/apis/hooks/action-reference/)
for the order of actions in a typical request.

### How services map to it

Each lifecycle question a service has to answer is a small interface in
`src/Infrastructure/`. A service implements only the ones it needs:

| Lifecycle question | Interface | How the plugin uses it |
| --- | --- | --- |
| Is it needed in this request at all? | `Conditional::is_needed()` | Checked before the service is built; if `false`, it never exists. A query-dependent condition also needs `Delayed` to wait for `wp` or later. |
| When does it hook in? | `Delayed::get_registration_action()` | Registration waits for that action, or runs at once if it has already fired. |
| Which services must exist first? | `HasDependencies::get_dependencies()` | Registration waits until those services are registered, and fails with `FailedToResolveDependencies` when they never can be. |
| What does it hook into? | `Registerable::register()` | Called once the service is built, to add its actions and filters. |
| What happens on activation or deactivation? | `Activateable`, `Deactivateable` | Called from the plugin's activation and deactivation hooks. |
| What does it remove when the plugin is deleted? | `Uninstallable` | Called from the plugin's uninstall callback. |

The four services of this plugin answer them like this:

| Service | Implements | So it acts |
| --- | --- | --- |
| `OutdatedPostNotice` | `Registerable`, `Conditional`, `Delayed` | on a single post (`is_singular( 'post' )`), once the query has run (`wp`) |
| `PostAgeColumn` | `Registerable`, `Conditional`, `Delayed` | on the Posts list screen (`edit-post`), once the screen is known (`current_screen`) |
| `FreshnessSetting` | `Registerable`, `Delayed`, `Uninstallable` | in admin requests (`admin_init`), and removes its option on uninstall |
| `StaleContentDigest` | `Registerable`, `Activateable`, `Deactivateable` | in every request, because WP-Cron can run in any of them; schedules on activation, unschedules on deactivation |

The plugin itself only hooks into `plugins_loaded`
(`src/Infrastructure/ServiceBasedPlugin.php:79`) and the activation,
deactivation and uninstall hooks (`content-freshness.php:125-149`).
Everything else is decided per service. `OutdatedPostNotice` declares that it
runs at `wp` and only on single posts
(`src/Notice/OutdatedPostNotice.php:71-81`), and no other code has to
know.

The questions are static methods on purpose: the plugin can **decide before it
builds**. A service that is not needed in a request is never instantiated, so
each request only pays for the services it actually uses. On the front end of
a single post, only the notice and the digest exist; on the Posts list, only
the column, the setting and the digest.

When a service needs a lifecycle point that no interface covers yet, the
answer is a new capability interface in the infrastructure. It is never a
conditional inside the service. Uninstalling was such a point when this plugin
started: the scaffold had no interface for it, so the plugin added
`Uninstallable` (`src/Infrastructure/Uninstallable.php:27-33`) rather than a check
inside a service for whether the plugin is being deleted.

## 1. Runs on PHP and WordPress alone

Content Freshness needs nothing on the production server except PHP and
WordPress. There is no `composer install`, no vendor build and no asset
pipeline before it runs: copy the plugin directory and activate it.

Composer is a **development tool**, not a runtime dependency. The plugin uses
it for tests, static analysis and coding standards, and loads its autoloader
when one is present. When it is absent, the bundled
`Infrastructure\Autoloader` takes over (`content-freshness.php:74-86`).

In practice:

- `composer.json` has no runtime `require` entries beyond PHP itself. Anything
  in `require-dev` must never be needed at runtime.
- Both autoloading paths must keep working. A change to class loading is
  proven with and without `vendor/`. The end-to-end tests install the plugin
  the way a site does, without `vendor/`.
- `.distignore` lists what stays out of the plugin a site installs.

## 2. Self-contained infrastructure, replaceable by design

The plugin brings every piece of infrastructure it needs to work: dependency
injection, a service container, view rendering and autoloading. It does not
pull in third-party packages for these common needs. Each one is a
deliberately simple implementation behind an interface, so a stronger
third-party implementation can replace it when a real need appears.

In the code:

- `Injector` is implemented by `SimpleInjector`
  (`src/Infrastructure/Injector/SimpleInjector.php:27`), and `ServiceContainer`
  by `SimpleServiceContainer`
  (`src/Infrastructure/ServiceContainer/SimpleServiceContainer.php:29`).
- `ServiceBasedPlugin` accepts both through its constructor and only falls back
  to the simple versions when none is passed
  (`src/Infrastructure/ServiceBasedPlugin.php:152-170`). Swapping in an adapter
  for a full-featured container changes one constructor call, not the plugin.
- `ViewFactory` is bound to `TemplatedViewFactory` in the composition root
  (`src/ContentFreshnessPlugin.php:129`) and can be rebound the same way.

When improving infrastructure, keep the simple implementation simple. Add
capability only when the plugin's own use needs it, and keep the interface
the stable part.

## 3. Defaults out of the box (poka-yoke)

The infrastructure lets you write the cleanest possible code, but does not
force you to wire everything up before anything works. Where a collaborator
has an obvious default, the default ships built in. Choosing the default needs
no configuration at all, and replacing it is just a matter of passing your
own.

The pattern is an optional constructor parameter typed to the interface, with
the built-in implementation as the fallback:

- `ServiceBasedPlugin` accepts any `Injector` and `ServiceContainer`, and falls
  back to `SimpleInjector` and `SimpleServiceContainer`
  (`src/Infrastructure/ServiceBasedPlugin.php:152-170`). The code calls this a
  poka-yoke: a design that makes the easy path also the correct one.
- `SimpleView` accepts any `DebugMode` and falls back to `WordPressDebugMode`
  (`src/Infrastructure/View/SimpleView.php:76`).

The fallback is the only place the default is named. Callers never have to
repeat it, and swapping it never requires touching the class.

Not every implementation is a replacement point. `TemplatedView` layers
WordPress core's theme-override behaviour (child theme, then parent theme,
then plugin) on top of `SimpleView`. That behaviour is fixed on the WordPress
side, so `TemplatedView` is meant to be used or not used, not replaced or
reconfigured. Changes to rendering itself belong in `SimpleView`, and
`TemplatedView` picks them up.

## 4. SOLID at the top, WordPress at the edges

The plugin aims for the best compromise between well-structured
object-oriented code and code that works without friction in WordPress. It is
a WordPress project, not a Symfony project in WordPress clothing. That
compromise has a clear line in it.

**The high-level design follows SOLID strictly.** This covers the
infrastructure, the composition root, service boundaries and every interface
another part of the plugin depends on:

- **Single responsibility:** each service does one thing. The plugin class only
  wires services together; it does not implement features.
- **Open/closed:** features are added as new services and bindings in the
  composition root, not by editing existing services. The column and the
  digest were added that way.
- **Liskov substitution:** any implementation of an interface (`Injector`,
  `ServiceContainer`, `ViewFactory`, `DebugMode`, `FreshnessPolicy`, `Clock`)
  can stand in for another without its callers noticing. The tests swap
  `FreshnessPolicy` for a fixed one and no service changes
  (`tests/php/Integration/Notice/OutdatedPostNoticeTest.php:78-100`).
- **Interface segregation:** small capability interfaces (`Registerable`,
  `Conditional`, `Delayed`, `HasDependencies`, `Activateable`,
  `Deactivateable`, `Uninstallable`) that a service implements only when it
  needs them.
- **Dependency inversion:** high-level code depends on interfaces, and the
  injector supplies the implementations. `OutdatedPostNotice` receives a
  `FreshnessPolicy`, a `MainLoop` and a `ViewFactory` through its constructor
  (`src/Notice/OutdatedPostNotice.php:92-97`) and never creates one.

**Implementation details may take shortcuts.** Inside a class, behind its
interface, it is fine to call WordPress functions, use the plugin API and read
WordPress state directly. `WordPressClock` asks WordPress for the time
(`src/Freshness/WordPressClock.php:28-33`) behind the `Clock` interface the rest of the
code depends on, and `WordPressStalePosts` runs a post query behind
`StalePosts`. Hooks are the integration mechanism: use actions and filters
rather than building a parallel event system over them.

Rule of thumb: introduce an abstraction where something higher up needs to be
decoupled from WordPress, for testability or because a second implementation
is plausible. Otherwise, call WordPress. `PostAgeColumn` looks a row's post up
with `get_post()` (`src/PostsList/PostAgeColumn.php:177`): that is an implementation
detail, not state the service depends on.

## 5. One composition root builds the object graph

The plugin's objects are assembled as one tree from a single composition
root. `ContentFreshnessPlugin` (`src/ContentFreshnessPlugin.php`) declares the
services, bindings, arguments, shared instances and delegations, and
`ServiceBasedPlugin` turns that configuration into running services through
the injector. Every object that takes part in the application flow is obtained
from there, directly or indirectly, through constructor injection.

There are exactly two ways to defer part of that tree:

- **Lazy instantiation**, for performance or to break a circular dependency.
  A service that does not need to hook into WordPress is stored as a
  `LazilyInstantiatedService` and only built when first retrieved
  (`src/Infrastructure/ServiceBasedPlugin.php:795-798`). `Conditional` and
  `Delayed` services are not built at all until they are needed.
- **On-demand instantiation through the injector**, when an object can only be
  created later or is needed once per use. Such objects are still produced by
  the injector, or by a factory the injector provides, never by the class that
  needs them. `ViewFactory` is the example: services receive the factory and
  ask it for a view each time they render.

**`new` is reserved for three cases:**

- **Value objects**: small, immutable objects that represent a value rather
  than a collaborator, such as an identifier, a validated path or a date
  range. `MaxAge` and `PostAge` are this plugin's own. Exceptions, created
  through their named constructors, fall in the same category.
- **Factories**: a factory's whole job is instantiation, and its internals are
  encapsulated, so it may create what it produces with `new`
  (`src/Infrastructure/View/TemplatedViewFactory.php:61`). Going through the
  injector instead is allowed but not required: the factory is assumed to
  know best how to build its product. The same goes for managing instances:
  `ContentFreshnessPluginFactory` keeps the one plugin instance in a static
  variable (`src/ContentFreshnessPluginFactory.php:38-55`). That works around a
  limitation of WordPress core, which needs static access to the plugin from
  outside the object graph, for example for the activation hooks. Because the
  state is encapsulated in the factory, it does not leak into the rest of the
  code. Static state anywhere else is not acceptable.
- **Poka-yoke defaults**: an optional constructor dependency that falls back to
  the built-in implementation when none is passed (principle 3).

Anything else with behaviour that is part of the application flow (a service,
a repository, a view, an adapter around a WordPress API) is never created with
`new` in the class that uses it.

### Reaching the object graph from outside it

Some code needs a service but is not part of the graph: a callback that
WordPress only accepts as a static method or function, such as the uninstall
callback, or the activation hooks in the main plugin file. The answer is never
a service locator. It depends on which side of the graph's edge the code is
on:

- **Inside the graph**, a class receives what it needs through its
  constructor. When it needs an object later or once per use, it receives a
  factory, or the injector (`src/Infrastructure/ServiceBasedPlugin.php:371-374`),
  and calls only `make()` on it. When it has to
  choose between implementations at runtime, it receives a typed registry for
  that one concept. It never looks a service up in the container.
- **At the edge**, where WordPress calls in from outside, a *bridge* gets the
  plugin through the plugin factory and delegates at once to instance
  methods, as the activation, deactivation and uninstall callbacks in the
  main file do (`content-freshness.php:125-149`). A bridge holds no state
  and no logic of its own, and it is where exceptions have to be caught
  before control goes back to WordPress (principle 10). The lifecycle
  bridges do not do that yet, as in the scaffold
  ([mwpd/basic-scaffold#27](https://github.com/mwpd/basic-scaffold/issues/27)):
  a failing activation reports its exception, which is loud but not graceful.

The [patterns](patterns.md#i-need-a-service-locator) show each case with
code.

## 6. Final by default

Concrete classes are `final`. Variation happens through interfaces and
injected collaborators, not through inheritance. Only classes deliberately
designed as a base are open: `ServiceBasedPlugin` is abstract so the
composition root can extend it (`src/Infrastructure/ServiceBasedPlugin.php:28`),
and `SimpleView` is the base that `TemplatedView` builds on
(`src/Infrastructure/View/SimpleView.php:34`). Every other class in `src/` is
`final`, and new classes follow suit unless they are designed for extension
from the start.

## 7. Show the alternative to WordPress anti-patterns

Many patterns that are common throughout the WordPress ecosystem work against
object-oriented design. The plugin avoids them and, as the worked example of
the scaffold, shows the better alternative.

| Common anti-pattern | What the plugin does instead |
| --- | --- |
| Registering hooks in a constructor, so creating an object changes global state | Constructors only store dependencies. Hooks are added in `register()` (`Registerable`), called by the plugin at the right time (`src/PostsList/PostAgeColumn.php:113-117`). [Pattern](patterns.md#i-need-to-add-hooks) |
| Singletons with `::instance()` and static state | Shared instances come from the injector (`share()`): every service gets the same `FreshnessPolicy` (`src/ContentFreshnessPlugin.php:192`). The plugin factory keeps one shared plugin instance without the Singleton pattern (`src/ContentFreshnessPluginFactory.php:38-55`). [Pattern](patterns.md#i-need-a-singleton) |
| A service locator, such as a static `Services::get( 'id' )` | Each need behind it has its own answer: constructor injection, a factory, a typed registry, `share()`, or a bridge at the edge of the object graph (principle 5). [Pattern](patterns.md#i-need-a-service-locator) |
| A global variable or singleton so that WordPress callbacks can reach the plugin | A bridge gets the plugin through the plugin factory and delegates at once (`content-freshness.php:148-150`). [Pattern](patterns.md#i-need-to-reach-the-plugin-from-a-static-wordpress-callback) |
| Creating collaborators with `new` inside a class, or pulling them from globals | Dependencies are injected through the constructor and configured as bindings in the composition root. The current post comes from `MainLoop`, not `global $post`. [Pattern: creating objects](patterns.md#i-need-to-create-objects-on-demand), [WordPress state](patterns.md#i-need-wordpress-state-such-as-the-current-post) |
| `is_admin()` checks wrapped around hook calls throughout the code | A service states when it is needed once, in `Conditional::is_needed()` (`src/PostsList/PostAgeColumn.php:82-86`). [Pattern](patterns.md#i-need-a-service-only-in-some-requests) |
| Hooking to an early action just to wait for a later one | A service names its registration action through `Delayed`, and the plugin defers it. [Pattern](patterns.md#i-need-to-hook-in-later) |
| Asking query-dependent conditions before the query has run | A `Conditional` service also implements `Delayed` and returns `wp` or a later action from `get_registration_action()`, so the plugin waits before asking `is_needed()`. [Pattern](patterns.md#i-need-to-hook-in-later) |
| Doing work when a file is included | Only `content-freshness.php` has side effects; everything else is autoloadable declarations. [Pattern](patterns.md#i-need-to-run-code-when-the-plugin-file-loads) |
| One god class holding the whole plugin | A composition root wires small services, each with one responsibility. [Pattern](patterns.md#i-need-to-add-a-feature) |
| Echoing HTML from business logic | Output goes through views from a `ViewFactory`, escaped by default. [Pattern](patterns.md#i-need-to-print-output) |

When reviewing a change, an anti-pattern from this list is a defect even if
the code works.

## 8. Types name concepts, not just data

Names say what a type is, never what kind of type it is. A type that starts
as one concrete class can later be promoted to an interface with several
implementations without breaking a single type hint, `instanceof` or `catch`.
Principle 5 confines `new` of collaborators to factories and the composition
root, so the promotion breaks nothing outside them; principle 6 makes classes
`final`, so no subclass breaks either. A name that encodes the kind
(`FooInterface`, `IFoo`, `AbstractFoo`, `FooImpl`) is wrong the moment the kind
changes, and renaming it breaks every hint. Implementations are named by what
distinguishes them (`Simple…`, `WordPress…`, `Option…`), never `…Impl`,
`Default…` or `Base…`. The interfaces `FreshnessPolicy`, `Clock`, `MainLoop`
and `StalePosts` name their concepts; `OptionFreshnessPolicy`,
`WordPressClock`, `WordPressMainLoop` and `WordPressStalePosts` name what
distinguishes their implementations, as `SimpleInjector` and
`TemplatedViewFactory` do in the infrastructure. Exceptions name the failure,
such as `InvalidMaxAge` and `FailedToSendDigest`; only the configured
exception marker interface (`ContentFreshnessException`) may end in
`Exception`.

Types are used for concepts as well as for data structures. When two things
mean different things, they get different types, even if they share the same
underlying data.

In the code:

- `MaxAge` is a number of days, but not any number: it only exists in the
  allowed range (`src/Freshness/MaxAge.php:76-81`). `PostAge` keeps the two moments an
  age is measured between, so a view can say "3 years ago" without asking
  the clock again.
- `Service` is a pure marker interface with no methods: it exists only to name
  the concept. `Registerable`, `Conditional`, `Delayed` and the other service
  interfaces each name one capability.
- Failures are typed by category (`src/Exception/`), rather than generic
  exceptions with different messages.

For new code: prefer a small value object or a dedicated interface over a bare
`int`, `string` or `array` whenever the meaning matters. A post ID and a user ID
are both integers, but mixing them up should be a type error, not a runtime
surprise. The strict static analysis (PHPStan at `max`, Psalm at level 2) is
there to enforce exactly this.

## 9. Never trust what WordPress hands over

WordPress data is loosely typed: options, post meta, filter return values,
request parameters, globals and query results may hold anything, whatever their
documentation claims. The plugin makes no assumptions about such input.

At every boundary where data comes from WordPress:

1. **Sanitize, validate and normalize** it as fully as possible.
2. **Transform** it into an internal type, failing loudly with a specific
   exception when that is impossible.
3. From then on, internal code relies on the internal type and does not
   re-check it.

In the code:

- The maximum age is stored as an option, which may hold anything. The policy
  reads it once and turns it into a `MaxAge`, or fails with `InvalidMaxAge`
  (`src/Freshness/OptionFreshnessPolicy.php:103-108`). The settings form goes through
  the same value object (`src/Settings/FreshnessSetting.php:140-157`).
- Filters hand over whatever the last callback returned. The notice and the
  column only change a value of the type they expect, and pass anything else
  on (`src/Notice/OutdatedPostNotice.php:130-131`).
- The digest keeps only the posts the query returned
  (`src/Digest/WordPressStalePosts.php:70-75`), and only the users who really have the
  capability and a valid email address
  (`src/Digest/StaleContentDigest.php:292-308`): the user query matches roles
  and stored capabilities, not a capability taken away from one user.
- The infrastructure does the same for its own extension points: the service
  list passes through a filter that any plugin can hook, so
  `ServiceBasedPlugin` checks that it is an array of identifiers mapped to
  service classes before using it, and throws `InvalidConfiguration` or
  `InvalidService` on anything unexpected
  (`src/Infrastructure/ServiceBasedPlugin.php:410-437`).

**Output is escaped for the context it lands in.** The reverse direction gets
the same care. Values a view reads as properties are escaped by default
(`src/Infrastructure/View/SimpleView.php:251-280`). A value fetched with
`raw()` is the explicit exception, and the template must then escape it for
where it is printed: HTML text, an attribute, a URL or JavaScript each need
their own escaping, using WordPress's matching `esc_*` function. The digest is
an HTML email, so it is escaped for HTML too.

## 10. Errors are exceptions

Inside the plugin, every error condition is an exception. The plugin does not
use WordPress's usual error mechanisms internally: it does not return
`WP_Error` objects, return `false` or `null` to signal a failure, write to the
server log with `error_log()`, or raise notices with `trigger_error()` or
`_doing_it_wrong()`. None of these appear anywhere in `src/`.

WordPress itself still speaks in those terms, so the translation happens at
the boundary, in the same place as principle 9:

- **Coming in:** a `WP_Error` or a falsy failure value returned by a WordPress
  function is turned into an exception right where the call is made. The
  digest does that with the result of scheduling
  (`src/Digest/StaleContentDigest.php:172-173`), and the policy with a post that has
  no date (`src/Freshness/OptionFreshnessPolicy.php:125-126`).
- **Going out:** where WordPress expects a `WP_Error` (a REST callback, for
  example) or a user-facing message, the edge code catches the exception and
  converts it. The setting turns an invalid maximum age into a settings error
  (`src/Settings/FreshnessSetting.php:143-155`). Internal code never does that conversion itself.

The exceptions follow a fixed shape (`src/Exception/`):

- **They keep PHP's own hierarchy.** Each exception extends the SPL exception
  that matches its category: `InvalidArgumentException` for invalid input or
  configuration (`InvalidMaxAge`, `InvalidService`, `InvalidConfiguration`),
  and `RuntimeException` for failures while running (`FailedToDetermineAge`,
  `FailedToSendDigest`, `FailedToLoadView`). Callers can catch by category
  without knowing the plugin's classes.
- **They share one marker interface.** Every exception implements
  `ContentFreshnessException` (`src/Exception/ContentFreshnessException.php:25`), so
  everything this plugin throws can be caught in one place.
- **Each kind of failure gets its own class.** A new kind of failure gets a
  dedicated, `final` exception rather than a generic exception with a
  different message. Related conditions within one kind share that class
  through separate named constructors, and get their own class once callers
  need to tell them apart.
- **Named constructors build the message.** Exceptions are created through
  static constructors that state the cause, such as
  `InvalidMaxAge::from_value()` (`src/Exception/InvalidMaxAge.php:50`) or
  `FailedToScheduleDigest::when_clearing()`. Messages stay consistent, and the
  `Stringify` trait renders arbitrary values safely inside them.
- **They keep their cause.** When a lower-level failure is translated, the
  original is passed on as the previous exception, as `FailedToLoadView` does
  (`src/Exception/FailedToLoadView.php:32`).

**Exceptions never reach the end user.** They are for internal communication
and for developers. At the point where control is handed back to WordPress (a
hook callback, a rendered view, a REST response), the exception is caught and
the code falls back to how WordPress works:

- **With debug mode on** (`DebugMode`, backed by `WP_DEBUG`), problems surface
  immediately and loudly, so a developer sees what went wrong and where.
- **With debug mode off**, the broken part degrades gracefully and the rest of
  the site keeps working. A view that fails renders nothing. That field or
  notice is still broken, but the page around it is not taken down with it.

Each of the plugin's hook callbacks is such an edge. The notice catches its
failures and returns the content as it was
(`src/Notice/OutdatedPostNotice.php:151-156`); the column leaves its cell empty;
the digest ends the run and tries again next week. The end-to-end tests break
the stored setting on purpose and check both modes.

This is the same compromise as principle 4: clean, strict code inside, and the
WordPress way at the edges, where forcing the clean approach would create more
problems than it solves.

## 11. Lifecycle steps are idempotent

Registering, activating, deactivating and uninstalling are safe to run more
than once. WordPress can reach the same step through more than one path:
activation, for example, registers the services itself because
`plugins_loaded` has already fired in that request. So the plugin skips
registration when it has already happened
(`src/Infrastructure/ServiceBasedPlugin.php:347-349`), and never registers the
same service twice (`src/Infrastructure/ServiceBasedPlugin.php:750-753`).
The digest only schedules its weekly event when none is scheduled
(`src/Digest/StaleContentDigest.php:160-162`), so activating twice does not send two
digests a week. A step that runs a second time must leave the plugin exactly
as the first run did.

## 12. Extension points run at a hook, never at include time

Filters that let other code change the plugin, such as its services or its
injector configuration, are applied on a WordPress action, never while the
plugin file is being included. At include time, plugins loaded after this one
have had no chance to add their callbacks, so an extension point that runs
then only works for code that happens to load earlier. So the plugin applies
its filters during registration at `plugins_loaded`: the `services` filter
(`src/Infrastructure/ServiceBasedPlugin.php:397-400`), and the injector
filters, because the injector is configured there and not in the constructor
(`src/Infrastructure/ServiceBasedPlugin.php:361`).

The injector filters are how a site can change the freshness rule without
touching the plugin: binding `FreshnessPolicy` to its own class through the
`mwpd.content_freshness.bindings` filter changes it for the notice, the
column and the digest at once.

## 13. One plugin, to arbitrary complexity

The plugin solves everything it needs, however large it grows. It
deliberately contains no mechanisms for interoperability, collaboration or
synchronization between plugins.

- A plugin's filters and service identifiers are its public extension surface
  for other code. That is the extent of cross-plugin support.
- When plugins genuinely need to share infrastructure or coordinate, that
  belongs in a separate, dedicated mechanism, not here.

**Everything public carries the plugin's prefix**, so that several plugins
built from the scaffold can run side by side without clashing. That covers
hook names and service identifiers (`src/ContentFreshnessPlugin.php:240-242`), the
option (`src/Freshness/OptionFreshnessPolicy.php:32`), the cron hook
(`src/Digest/StaleContentDigest.php:47`), the column key and the CSS classes the views
print. The paths a theme uses to override the plugin's views are not prefixed
yet, as in the scaffold
([mwpd/basic-scaffold#29](https://github.com/mwpd/basic-scaffold/issues/29)).
The prefixes prevent collisions; they are not a coordination protocol.

## 14. The code teaches

Content Freshness is the worked example of the scaffold, and its code is
quoted to show how the architecture works. Its code and comments are teaching
material:

- **Comments explain why**, not just what. The bootstrap is split into three
  numbered steps that say what each one is for (`content-freshness.php:34-45`), and the
  composition root is laid out the same way (`src/ContentFreshnessPlugin.php`,
  sections 1 to 3). Each service's docblock says why it implements the
  capabilities it does, and why not the others.
- **The code must be correct.** Every service and view demonstrates the full
  pattern, including escaping and error handling, and is covered by tests:
  unit tests with test doubles, integration tests through the real
  composition root, and end-to-end tests in WordPress. A shortcut in a
  service is a defect, even when it is "only an example".
