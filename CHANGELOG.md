# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the plugin uses
[Semantic Versioning](https://semver.org/).

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

[1.0.0]: https://github.com/mwpd/content-freshness/releases/tag/1.0.0
