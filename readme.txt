=== Content Freshness ===
Contributors: schlessera
Tags: outdated content, content audit, editorial, posts, email digest
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Warns readers about outdated posts, shows editors what is going stale, and emails a weekly digest.

== Description ==

Old posts keep getting read long after they stop being right. Content Freshness tells your readers when a post may be out of date, and tells your editors which posts need a refresh.

* **A notice for readers.** A post that has not been updated for longer than you allow starts with a short note: "This post was last updated 3 years ago; some information may be out of date."
* **A column for editors.** The Posts list gets a "Last updated" column. Outdated posts are marked, and the column sorts by last update.
* **A weekly digest.** Editors and administrators get one email a week listing the published posts that have gone longest without an update, each linked to its editor. When nothing is stale, nothing is sent.
* **One setting.** Choose after how many days a post counts as outdated, under Settings › Reading. The default is a year.

The plugin needs nothing but WordPress and PHP: no external service, no account, no build step. It stores one option, schedules one weekly event, and removes both when you delete it.

Content Freshness is also the worked example of the [Modern WordPress Plugin Development scaffold](https://github.com/mwpd/basic-scaffold). Its source, with an architecture walkthrough for developers, is on [GitHub](https://github.com/mwpd/content-freshness).

== Installation ==

1. Upload the `content-freshness` directory to `/wp-content/plugins/`, or install the zip under Plugins › Add New › Upload Plugin.
2. Activate the plugin under Plugins.
3. Optionally, change "Posts become outdated after" under Settings › Reading.

The first digest is sent a week after activation.

== Frequently Asked Questions ==

= When does a post count as outdated? =

When its last update is older than the number of days set under Settings › Reading, 365 by default. Publishing a post counts as its first update; any later save counts as a new one.

= Who receives the weekly digest? =

Every user who can edit other users' posts: Editors and Administrators, unless your roles were changed. Each receives a separate email, so nobody sees the others' addresses.

= The digest does not arrive. What can I check? =

The digest is sent by WP-Cron through `wp_mail()`. On a site with little traffic, WP-Cron runs late; a real cron job that calls `wp-cron.php` fixes that. If other email from your site does not arrive either, set up an SMTP plugin.

= Which posts does the notice appear on? =

Only on single posts of the "post" type, and only in the post's main content. Pages, archives and lists of posts are left alone.

= Can I change how the notice looks? =

Yes. The plugin ships a default style, a soft amber box that follows your theme's font size, and loads it only on posts that show the notice. To adjust it, override the class `mwpd-content-freshness-notice` in your theme's stylesheet. To start from scratch, dequeue the style's handle, `mwpd-content-freshness-notice`, on `wp_enqueue_scripts`. To change its markup, copy `views/outdated-post-notice.php` from the plugin into your theme as `content-freshness/views/outdated-post-notice.php`, and edit it there.

= Can I change the rule for what counts as outdated? =

Yes, with a little code. The notice, the column and the digest all ask one `FreshnessPolicy` interface. Bind your own implementation through the `mwpd.content_freshness.bindings` filter, and all three use it. The plugin's README on GitHub shows how.

= Does it work on multisite? =

Yes. Activate it on single sites, or for a whole network: network activation sets the weekly digest up in every site of the network, and in sites created on it later. Each site has its own setting and its own digest.

= What happens when I deactivate or delete the plugin? =

Deactivating stops the digest and hides the notice and the column, and keeps your setting. Deleting the plugin also removes the setting, from every site of a network.

== Changelog ==

= 1.0.2 =
* The outdated post notice now has a soft amber box that follows your theme's font size. Its stylesheet loads only on posts that show the notice.
* Themes can adjust the notice's style through the `mwpd-content-freshness-notice` class, or dequeue the `mwpd-content-freshness-notice` style handle to replace it.

= 1.0.1 =
* Multisite: network activation sets the weekly digest up in every site of the network, and in sites created on it later. Deactivating clears it in every site it reaches.
* Deleting the plugin removes the setting, and any weekly digest left behind, from every site of every network, not only the current site.
* The plugin is built on the current Modern WordPress Plugin Development scaffold. Themes now override the plugin's templates in a `content-freshness/` directory, for example `content-freshness/views/outdated-post-notice.php`.

= 1.0.0 =
* First release: the outdated post notice, the "Last updated" column in the Posts list, the "Posts become outdated after" setting under Settings › Reading, and the weekly digest of stale posts.

== Upgrade Notice ==

= 1.0.2 =
The outdated post notice now comes with a default style. Nothing is needed after updating; the style loads automatically, without reactivating the plugin.

= 1.0.1 =
Deleting the plugin now cleans up every site of a network. If the plugin is network-active, deactivate it for the network and activate it again after updating, so every site gets its digest. Theme overrides of the plugin's templates move into a `content-freshness/` directory of the theme.

= 1.0.0 =
First release.
