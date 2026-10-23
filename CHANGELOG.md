# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin uses
[Semantic Versioning](https://semver.org/).

## [1.0.1] - 2026-10-23

### Changed

- Uninstalling goes through the scaffold's lifecycle capabilities instead of
  the plugin's own `Uninstallable`: the setting is a `SiteUninstallable`, and
  WordPress calls the static bridge `ContentFreshnessUninstallHook::uninstall()`.
  Deleting the plugin removes the setting from every site of every network,
  not only the current site. The uninstall callback of 1.0.0 still works on
  sites that activated that version.
- The weekly digest is set up per site: network activation schedules it in
  every site of the network, a site created on a network where the plugin is
  network-active gets its own, deactivation clears it in every site it
  reaches, and uninstalling clears any event left behind.
- Themes override the plugin's templates in a `content-freshness/` directory,
  for example `content-freshness/views/outdated-post-notice.php`, instead of
  the theme's own `views/` directory.
- The infrastructure, architecture checks and PHPStan rules are updated to
  `mwpd/basic-scaffold@61a9b00`, with PHPStan 2. Views now degrade on their
  own when they fail, delayed services choose their registration priority,
  and an optional Query Monitor panel shows how the services were registered.

### Removed

- The plugin's own `Uninstallable` interface and `Plugin::uninstall()`.

## [1.0.0] - 2026-10-23

### Added

- A notice ahead of the content of a single post whose last update is older
  than the maximum age: "This post was last updated 3 years ago; some
  information may be out of date."
- A sortable "Last updated" column in the Posts list that marks outdated
  posts.
- The "Posts become outdated after" setting under Settings › Reading, from 1
  to 36500 days, 365 by default. Invalid input is reported and leaves the
  setting unchanged.
- A weekly email to the users who can edit others' posts, listing up to 20 of
  the published posts that have gone longest without an update. Nothing is
  sent when nothing is stale.
- Activation schedules the digest, deactivation clears it, and deleting the
  plugin removes its setting.
- The `Uninstallable` capability interface in the infrastructure, for services
  that remove their data when the plugin is deleted.

[1.0.1]: https://github.com/mwpd/content-freshness/releases/tag/1.0.1
[1.0.0]: https://github.com/mwpd/content-freshness/releases/tag/1.0.0
