# Content Freshness

A WordPress plugin that keeps old posts honest:

- **Readers** see a notice on a post that has not been updated for a while:
  "This post was last updated 3 years ago; some information may be out of
  date."
- **Editors** see a "Last updated" column in the Posts list, sortable, with
  outdated posts marked.
- **Editors** get a weekly email listing the posts that need a refresh the
  most, and no email when nothing is stale.
- **Site owners** choose after how many days a post counts as outdated, under
  Settings › Reading.

It is also the worked example of the
[`mwpd/basic-scaffold`](https://github.com/mwpd/basic-scaffold) plugin
template from the *Modern WordPress Plugin Development* book. The
[architecture walkthrough](#how-it-is-built) below shows how the scaffold
keeps four features in four different parts of WordPress from knowing about
each other.

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later

Nothing else: no Composer packages and no build step at runtime.

## Installation

1. Download the plugin and put the `content-freshness` directory into
   `wp-content/plugins/`, or upload the zip under Plugins › Add New › Upload
   Plugin.
2. Activate it under Plugins.

Activating schedules the weekly digest; the first one goes out a week later.
Deactivating stops the digest and keeps your setting. Deleting the plugin
removes the setting too.

On multisite, network activation sets the digest up in every site of the
network, and in each site created on the network later. Deleting the plugin
removes the setting and the digest from every site of every network.

With WP-CLI:

```sh
wp plugin install content-freshness.zip --activate
```

## Configuration

Go to **Settings › Reading** and set **Posts become outdated after** a number
of days, from 1 to 36500. The default is 365. A post counts as outdated when
its last update is older than that.

The digest goes to every user who can edit other users' posts, which means
Editors and Administrators unless roles were changed. It lists up to 20 of the
published posts that have gone longest without an update, each linked to its
editor, and is sent through `wp_mail()`, so whatever handles mail on your site
sends it.

### Changing the rule

Every feature asks one question, "is this post outdated?", through the
`FreshnessPolicy` interface. To use your own rule, for example one that
ignores posts in an evergreen category, bind the interface to your class
through the plugin's injector filter, from a plugin or `mu-plugin`:

```php
add_filter(
	'mwpd.content_freshness.bindings',
	static function ( array $bindings ): array {
		$bindings[ \MWPD\ContentFreshness\Freshness\FreshnessPolicy::class ] = My_Freshness_Policy::class;

		return $bindings;
	}
);
```

The notice, the column and the digest all use it from then on; none of them
changes. The digest asks the database for the posts last updated before the
policy's `outdated_before()`, then lets `is_outdated()` decide about each
one, so a rule that leaves some old posts out applies to the email too. The plugin's tests do the same with a fixed policy
(`tests/php/Integration/Notice/OutdatedPostNoticeTest.php:78-100`).

### Changing the markup

The notice, the column cell, the setting field and the email body are views
in `views/`. A theme overrides one in a `content-freshness/` directory of its
own, at the same relative path: the notice, for example, with
`content-freshness/views/outdated-post-notice.php` in the child or parent
theme. The notice
has the class `mwpd-content-freshness-notice` for styling.

## How it is built

The plugin is four services, one per feature. Each implements only the small
capability interfaces that describe where and when it acts, and the scaffold's
infrastructure works out the rest. The composition root lists them and
nothing else knows they exist
(`src/ContentFreshnessPlugin.php:94-118`):

```php
protected function get_service_classes(): array {
	return [
		self::OUTDATED_POST_NOTICE_ID => OutdatedPostNotice::class,
		self::POST_AGE_COLUMN_ID      => PostAgeColumn::class,
		self::FRESHNESS_SETTING_ID    => FreshnessSetting::class,
		self::STALE_CONTENT_DIGEST_ID => StaleContentDigest::class,
	];
}
```

| Service | Declares | Which means |
| --- | --- | --- |
| [`OutdatedPostNotice`](src/Notice/OutdatedPostNotice.php) | `Conditional` (`is_singular( 'post' )`), `Delayed` (`wp`), `Registerable` | Only exists on a single post, once the query has run, and filters `the_content` there. |
| [`PostAgeColumn`](src/PostsList/PostAgeColumn.php) | `Conditional` (screen `edit-post`), `Delayed` (`current_screen`), `Registerable` | Only exists on the Posts list, and adds its column there. |
| [`FreshnessSetting`](src/Settings/FreshnessSetting.php) | `Delayed` (`admin_init`), `Registerable`, `SiteUninstallable` | Registers the setting in admin requests, and removes its option from every site when the plugin is deleted. |
| [`StaleContentDigest`](src/Digest/StaleContentDigest.php) | `Registerable`, `SiteActivateable`, `SiteDeactivateable`, `SiteInitializable`, `SiteUninstallable` | Hooks the cron callback in every request, schedules the weekly event in each site an activation reaches and in new sites of a network, and clears it in each site a deactivation reaches and on uninstall. |

On the front end of a single post, only two of the four services are ever
built. On the Posts list, three. The other ones are not skipped by an `if`
somewhere: their own static `is_needed()` or registration action tells the
plugin, before anything is constructed.

Look at what each service does *not* do:

- The notice does not check `is_admin()`, and the column does not check
  `is_singular()`. Each states its own condition once
  (`src/Notice/OutdatedPostNotice.php:76-78`,
  `src/PostsList/PostAgeColumn.php:87-91`).
- The digest is deliberately **not** `Conditional`. WP-Cron runs in whatever
  request comes along, so its callback has to be hooked in every request, and
  the infrastructure skips a `Conditional` service in a lifecycle event where
  it is not needed.
- The digest does not check whether an activation is network-wide. It
  implements `SiteActivateable`, because its work happens in each site, and
  the infrastructure visits the sites
  (`src/Digest/StaleContentDigest.php:173-175`).
- The setting is not `Conditional` either: waiting for `admin_init` already
  keeps it out of front-end requests, and a condition would keep it from
  cleaning up when the plugin is uninstalled through WP-CLI.
- No feature decides from the option itself. They all ask `FreshnessPolicy`,
  and its one implementation, `OptionFreshnessPolicy`, reads the option once
  and turns it into a `MaxAge` value object, or fails with `InvalidMaxAge`
  (`src/Freshness/OptionFreshnessPolicy.php:103-108`). The policy is shared,
  so a request reads the option at most once.
- No service fetches the current post when it is built. The notice asks
  `MainLoop` inside its filter, so it only acts on the queried post in the
  main loop, not on a related-posts widget further down
  (`src/Notice/OutdatedPostNotice.php:134-140`).

### Why adding a feature changed nothing else

The notice came first. The column, the setting and the digest were each added
in their own commit. In `src/`, each of those commits added the new service
and its collaborators, and changed nothing but a few lines in the composition
root:

```text
Add the post age column       src/ContentFreshnessPlugin.php, src/PostsList/PostAgeColumn.php
Add the freshness setting     src/ContentFreshnessPlugin.php, src/Settings/FreshnessSetting.php
Add the stale content digest  src/ContentFreshnessPlugin.php, src/Digest/…, two exceptions
```

The column did not need the notice to know it exists, and the digest did not
need a branch for "running from cron" in either of them. Each new feature was
one more service that manages its own lifecycle. Complexity grew by one
service at a time, not by one more condition in every existing one.

### Errors

Inside the plugin, every failure is an exception with a name, such as
`InvalidMaxAge` or `FailedToSendDigest`. Each hook callback catches them
before control goes back to WordPress: with `WP_DEBUG` on, the exception
surfaces; with it off, the notice is left out, the column cell stays empty,
or the digest waits for next week, and the rest of the page works
(`src/Notice/OutdatedPostNotice.php:156-161`).

The site lifecycle events are the deliberate exception, as in the scaffold:
when the digest cannot be scheduled while the plugin is activated in a site,
or the uninstall fails, the exception propagates with `WP_DEBUG` off too, so
the failure is visible and running the step again finishes it.

### Uninstalling

The plugin uses the scaffold's uninstall design. WordPress stores the
uninstall callback in the database, so activation registers a static bridge,
`ContentFreshnessUninstallHook::uninstall()`
(`src/ContentFreshnessUninstallHook.php:31-39`), and the composition root names it
(`src/ContentFreshnessPlugin.php:309-311`). When the plugin is deleted, the bridge hands over to
the plugin, which visits every site, builds only the services that implement
`SiteUninstallable` there, and calls them: the setting deletes its option
(`src/Settings/FreshnessSetting.php:137-139`), and the digest clears any weekly event left behind.

Version 1.0.0 had an uninstall capability of its own, from before the
scaffold settled the design. Its callback, a function in the main file,
stays as a thin hand-over to the bridge
(`content-freshness.php:163-165`), because a site that activated 1.0.0 keeps
that callback until the plugin is activated again. Updating does not run
activation: where 1.0.0 is network-active, deactivate it for the network and
activate it again after updating, so that every site gets its digest and
every network's main site the new callback.

### Further reading

- [docs/design-principles.md](docs/design-principles.md): the principles every
  change is measured against, with references into this plugin's code.
- [docs/patterns.md](docs/patterns.md): "I need X. How do I do that here?"
- [docs/architecture-rules.md](docs/architecture-rules.md): the checks that
  enforce the principles, and what each failure asks you to do instead.

## Development

```sh
composer install
composer test   # unit, integration and architecture tests
composer lint   # PHPCS, PHPStan (level max) and Psalm (level 2), plus the PHPStan rule tests

npm ci
npm run e2e     # the real thing: WordPress in Docker through @wordpress/env
```

The unit and integration tests run without WordPress, with
[Brain Monkey](https://github.com/Brain-WP/BrainMonkey) standing in for its
functions. The end-to-end tests install the plugin as a site would, without
`vendor/`, into a WordPress started by `@wordpress/env`, and check the notice,
the column, the setting, the digest (captured by a must-use plugin instead of
being sent), activation, deactivation and uninstalling. Set
`WP_ENV_PHP_VERSION` or `WP_ENV_CORE` to run them against another PHP or
WordPress version.

To see which services a request built, skipped or still waits to build, and
why, declare the scaffold's optional Query Monitor panel: it is one commented
line in the composition root, as
[I need to see why a service did not run](docs/patterns.md#i-need-to-see-why-a-service-did-not-run)
shows.

Psalm 5 supports PHP up to 8.3, so run the lint step on PHP 8.3. CI
(`.github/workflows/ci.yml`) is the reference environment. The rules for
contributing, by hand or with a coding agent, are in [AGENTS.md](AGENTS.md).

## License

[MIT](LICENSE)
