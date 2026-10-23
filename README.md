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
changes. The plugin's tests do the same with a fixed policy
(`tests/php/Integration/Notice/OutdatedPostNoticeTest.php:76-98`).

### Changing the markup

The notice, the column cell, the setting field and the email body are views
in `views/`. A theme overrides one by putting a file with the same relative
path in the theme, for example `views/outdated-post-notice.php`. The notice
has the class `mwpd-content-freshness-notice` for styling.

## How it is built

The plugin is four services, one per feature. Each implements only the small
capability interfaces that describe where and when it acts, and the scaffold's
infrastructure works out the rest. The composition root lists them and
nothing else knows they exist
(`src/ContentFreshnessPlugin.php:88-100`):

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
| [`FreshnessSetting`](src/Settings/FreshnessSetting.php) | `Delayed` (`admin_init`), `Registerable`, `Uninstallable` | Registers the setting in admin requests, and removes its option when the plugin is deleted. |
| [`StaleContentDigest`](src/Digest/StaleContentDigest.php) | `Registerable`, `Activateable`, `Deactivateable` | Hooks the cron callback in every request, schedules the weekly event on activation and clears it on deactivation. |

On the front end of a single post, only two of the four services are ever
built. On the Posts list, three. The other ones are not skipped by an `if`
somewhere: their own static `is_needed()` or registration action tells the
plugin, before anything is constructed.

Look at what each service does *not* do:

- The notice does not check `is_admin()`, and the column does not check
  `is_singular()`. Each states its own condition once
  (`src/Notice/OutdatedPostNotice.php:71-73`,
  `src/PostsList/PostAgeColumn.php:82-86`).
- The digest is deliberately **not** `Conditional`. WP-Cron runs in whatever
  request comes along, so its callback has to be hooked in every request, and
  the infrastructure skips a `Conditional` service in an activation request
  where it is not needed.
- The setting is not `Conditional` either: waiting for `admin_init` already
  keeps it out of front-end requests, and a condition would keep it from
  cleaning up when the plugin is uninstalled through WP-CLI.
- No service reads the option. They all depend on `FreshnessPolicy`, and the
  one implementation, `OptionFreshnessPolicy`, reads the option once and turns
  it into a `MaxAge` value object, or fails with `InvalidMaxAge`
  (`src/Freshness/OptionFreshnessPolicy.php:103-108`). The policy is shared,
  so a request reads the option at most once.
- No service fetches the current post when it is built. The notice asks
  `MainLoop` inside its filter, so it only acts on the queried post in the
  main loop, not on a related-posts widget further down
  (`src/Notice/OutdatedPostNotice.php:129-135`).

### Why adding a feature changed nothing else

The notice came first. The column, the setting and the digest were each added
in their own commit, and each of those commits touched only the new service,
its views and tests, and the composition root:

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
(`src/Notice/OutdatedPostNotice.php:144-149`).

### One thing the scaffold did not have

Uninstalling was the one lifecycle point the scaffold had no interface for.
Following its own rule, that a lifecycle point gets a capability interface
rather than a conditional in a service, this plugin adds `Uninstallable` to
the infrastructure (`src/Infrastructure/Uninstallable.php:27-33`). The main plugin file
registers one uninstall callback on activation, a bridge that asks the plugin
to uninstall, and the plugin calls each `Uninstallable` service
(`src/Infrastructure/ServiceBasedPlugin.php:231-239`).

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

Psalm 5 supports PHP up to 8.3, so run the lint step on PHP 8.3. CI
(`.github/workflows/ci.yml`) is the reference environment. The rules for
contributing, by hand or with a coding agent, are in [AGENTS.md](AGENTS.md).

## License

[MIT](LICENSE)
