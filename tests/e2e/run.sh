#!/usr/bin/env bash
#
# End-to-end tests of Content Freshness in a real WordPress, run with
# @wordpress/env (Docker).
#
#   npm ci
#   npm run e2e
#
# The script copies the plugin as a site would install it (without the files
# .distignore lists, so without vendor/ and with the bundled autoloader) into
# build/content-freshness, mounts that copy into WordPress, and drives WordPress
# through WP-CLI and HTTP requests. Email is captured by the must-use plugin in
# tests/e2e/mu-plugins instead of being sent.
#
# Set E2E_KEEP_RUNNING=1 to leave the environment running afterwards, and
# WP_ENV_PHP_VERSION or WP_ENV_CORE to test another PHP or WordPress version.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
BUILD="$ROOT/build/content-freshness"
PORT="${WP_ENV_PORT:-8891}"
SITE="http://localhost:$PORT"
PLUGIN="content-freshness/content-freshness.php"
OPTION="mwpd_content_freshness_max_age"
CRON_HOOK="mwpd_content_freshness_send_digest"
COOKIES="$(mktemp)"
PAGE="$(mktemp)"

cd "$ROOT"

passed=0
failed=0

cleanup() {
	rm -f "$COOKIES" "$PAGE"
	if [ "${E2E_KEEP_RUNNING:-0}" != "1" ]; then
		npx wp-env stop >/dev/null 2>&1 || true
	fi
}
trap cleanup EXIT

pass() {
	passed=$((passed + 1))
	printf '  \033[32m✔\033[0m %s\n' "$1"
}

fail() {
	failed=$((failed + 1))
	printf '  \033[31m✘\033[0m %s\n' "$1"
	if [ -n "${2:-}" ]; then
		printf '      %s\n' "$2"
	fi
}

# Run WP-CLI in the environment and print only its output, without the status
# lines wp-env adds around it.
# WP-CLI's own exit status is kept, so a failing command stops the script.
wp() {
	local output status=0
	output="$(npx wp-env run cli wp "$@" 2>/dev/null)" || status=$?
	printf '%s\n' "$output" | sed -e '/^ℹ /d' -e '/^✔ /d' -e '/^[[:space:]]*$/d'
	return "$status"
}

# Check that a value equals what is expected.
expect_equals() {
	local description="$1" expected="$2" actual="$3"
	if [ "$expected" = "$actual" ]; then
		pass "$description"
	else
		fail "$description" "expected \"$expected\", got \"$actual\""
	fi
}

# Check that a file contains, or does not contain, a fixed string.
expect_contains() {
	local description="$1" needle="$2" file="$3"
	if grep -qF -- "$needle" "$file"; then
		pass "$description"
	else
		fail "$description" "\"$needle\" not found"
	fi
}

expect_not_contains() {
	local description="$1" needle="$2" file="$3"
	if grep -qF -- "$needle" "$file"; then
		fail "$description" "\"$needle\" found"
	else
		pass "$description"
	fi
}

# Fetch a page, logged in if the cookie jar holds a session.
fetch() {
	curl -sS -L -b "$COOKIES" -c "$COOKIES" -o "$PAGE" -w '%{http_code}' "$SITE$1"
}

echo "Building the plugin as a site installs it"
# Sync rather than recreate the directory: WordPress mounts it, and a mount
# keeps pointing at a directory that was deleted.
mkdir -p "$BUILD"
rsync -a --delete --exclude-from="$ROOT/.distignore" "$ROOT/" "$BUILD/"
test ! -d "$BUILD/vendor"

echo "Starting WordPress"
npx wp-env start >/dev/null
echo "  WordPress $(wp core version), PHP $(wp eval 'echo PHP_VERSION;')"

echo "Resetting the site"
# A previous run may have used an older WordPress on the same database. A
# newer one cannot be undone: run `npx wp-env reset development` before testing
# an older WordPress, such as WP_ENV_CORE=WordPress/WordPress#6.0.
wp core update-db --quiet
wp plugin deactivate content-freshness --quiet >/dev/null 2>&1 || true
wp option delete "$OPTION" >/dev/null 2>&1 || true
wp option delete content_freshness_e2e_mails >/dev/null 2>&1 || true
wp cron event delete "$CRON_HOOK" >/dev/null 2>&1 || true
wp config set WP_DEBUG true --raw --quiet
sleep 3
for id in $(wp post list --post_type=post --format=ids); do
	wp post delete "$id" --force --quiet
done
for id in $(wp user list --role=editor --field=ID); do
	wp user delete "$id" --yes --quiet
done
for id in $(wp user list --role=subscriber --field=ID); do
	wp user delete "$id" --yes --quiet
done
wp user create e2e-editor editor@example.com --role=editor --porcelain >/dev/null
wp user create e2e-subscriber subscriber@example.com --role=subscriber --porcelain >/dev/null
# An editor whose capability to edit others' posts was taken away.
denied_id="$(wp user create e2e-denied denied@example.com --role=editor --porcelain)"
wp eval "get_userdata( $denied_id )->add_cap( 'edit_others_posts', false );"

echo "Activation"
wp plugin activate content-freshness --quiet
expect_equals "the plugin is active" "active" "$(wp plugin get content-freshness --field=status)"
expect_equals "the weekly digest is scheduled" "weekly" "$(wp eval "echo wp_get_schedule( '$CRON_HOOK' );")"
expect_equals "the uninstall callback is the static bridge" "MWPD\\ContentFreshness\\ContentFreshnessUninstallHook::uninstall" \
	"$(wp eval "\$u = get_option( 'uninstall_plugins' ); echo implode( '::', (array) ( \$u['$PLUGIN'] ?? [] ) );")"
wp plugin deactivate content-freshness --quiet
wp plugin activate content-freshness --quiet
expect_equals "deactivating and activating keeps one digest event" "1" \
	"$(wp cron event list --hook="$CRON_HOOK" --format=count)"
wp eval "do_action( 'activate_$PLUGIN', false );"
expect_equals "running activation again while active keeps one digest event" "1" \
	"$(wp cron event list --hook="$CRON_HOOK" --format=count)"

echo "Posts"
old_id="$(wp post create --post_title='An old post' --post_content='Old content.' --post_status=publish --porcelain)"
fresh_id="$(wp post create --post_title='A fresh post' --post_content='Fresh content.' --post_status=publish --porcelain)"
# WordPress sets the modified date on every save, so age the old post directly.
wp db query "UPDATE wp_posts SET post_modified = '2022-01-01 00:00:00', post_modified_gmt = '2022-01-01 00:00:00' WHERE ID = $old_id" >/dev/null
wp cache flush --quiet
pass "created an old post ($old_id) and a fresh post ($fresh_id)"

echo "Outdated post notice"
expect_equals "the old post loads" "200" "$(fetch "/?p=$old_id")"
expect_contains "the old post warns readers" 'class="mwpd-content-freshness-notice"' "$PAGE"
expect_contains "the notice says how old the post is" 'This post was last updated' "$PAGE"
expect_contains "the content still follows the notice" 'Old content.' "$PAGE"
expect_equals "the fresh post loads" "200" "$(fetch "/?p=$fresh_id")"
expect_not_contains "the fresh post has no notice" 'mwpd-content-freshness-notice' "$PAGE"
expect_equals "the home page loads" "200" "$(fetch "/")"
expect_not_contains "lists of posts have no notice" 'mwpd-content-freshness-notice' "$PAGE"
expect_equals "the old post's feed loads" "200" "$(fetch "/?p=$old_id&feed=rss2&withoutcomments=1")"
expect_not_contains "feeds have no notice" 'mwpd-content-freshness-notice' "$PAGE"

echo "Logging in"
curl -sS -o /dev/null -c "$COOKIES" -b 'wordpress_test_cookie=WP%20Cookie%20check' \
	--data-urlencode 'log=admin' --data-urlencode 'pwd=password' \
	--data-urlencode 'testcookie=1' --data-urlencode "redirect_to=$SITE/wp-admin/" \
	"$SITE/wp-login.php"
expect_equals "the dashboard loads" "200" "$(fetch "/wp-admin/")"
expect_not_contains "the column is not built on the dashboard" 'column-mwpd-content-freshness-age' "$PAGE"

echo "Posts list column"
expect_equals "the Posts list loads" "200" "$(fetch "/wp-admin/edit.php")"
expect_contains "the Posts list has the column" 'column-mwpd-content-freshness-age' "$PAGE"
expect_contains "the column is titled" 'Last updated' "$PAGE"
expect_contains "the old post is marked outdated" 'mwpd-content-freshness-outdated' "$PAGE"
expect_equals "only the old post is marked" "1" "$(grep -cF 'class="mwpd-content-freshness-outdated"' "$PAGE")"
expect_contains "the column sorts by last update" 'orderby=modified' "$PAGE"
expect_equals "the Pages list loads" "200" "$(fetch "/wp-admin/edit.php?post_type=page")"
expect_not_contains "the Pages list has no column" 'column-mwpd-content-freshness-age' "$PAGE"

echo "Setting"
expect_equals "Settings > Reading loads" "200" "$(fetch "/wp-admin/options-reading.php")"
expect_contains "the field is on the page" "name=\"$OPTION\"" "$PAGE"
expect_contains "the field shows the default" 'value="365"' "$PAGE"
nonce="$(sed -n 's/.*name="_wpnonce" value="\([^"]*\)".*/\1/p' "$PAGE" | head -n 1)"

# Submit the form as a browser would: with every field of the page.
save_reading() {
	curl -sS -L -b "$COOKIES" -c "$COOKIES" -o "$PAGE" -w '%{http_code}' \
		--data-urlencode "option_page=reading" --data-urlencode "action=update" \
		--data-urlencode "_wpnonce=$nonce" --data-urlencode "_wp_http_referer=/wp-admin/options-reading.php" \
		--data-urlencode "show_on_front=$(wp option get show_on_front)" \
		--data-urlencode "page_on_front=$(wp option get page_on_front)" \
		--data-urlencode "page_for_posts=$(wp option get page_for_posts)" \
		--data-urlencode "posts_per_page=$(wp option get posts_per_page)" \
		--data-urlencode "posts_per_rss=$(wp option get posts_per_rss)" \
		--data-urlencode "rss_use_excerpt=$(wp option get rss_use_excerpt)" \
		--data-urlencode "blog_public=$(wp option get blog_public)" \
		--data-urlencode "$OPTION=$1" \
		"$SITE/wp-admin/options.php"
}

expect_equals "saving the setting succeeds" "200" "$(save_reading ' 90 ')"
expect_equals "the saved value is normalized" "90" "$(wp option get "$OPTION")"
expect_contains "the field shows the saved value" 'value="90"' "$PAGE"
expect_equals "saving an invalid value returns to the page" "200" "$(save_reading 'a year')"
expect_contains "an invalid value is reported" 'Enter a whole number of days from 1 to 36500.' "$PAGE"
expect_equals "an invalid value changes nothing" "90" "$(wp option get "$OPTION")"
save_reading '0' >/dev/null
expect_equals "zero days changes nothing" "90" "$(wp option get "$OPTION")"

echo "Stale content digest"
wp option delete content_freshness_e2e_mails --quiet >/dev/null 2>&1 || true
wp cron event run "$CRON_HOOK" --quiet
recipients="$(wp eval "\$t = array_map( static fn( \$x ) => \$x['to'], get_option( 'content_freshness_e2e_mails', [] ) ); sort( \$t ); echo implode( ',', \$t );")"
admin_email="$(wp option get admin_email)"
expected_recipients="$(printf '%s\n' "$admin_email" editor@example.com | sort | paste -sd, -)"
expect_equals "the digest goes to the administrator and the editor, not the subscriber or the denied editor" \
	"$expected_recipients" "$recipients"
wp eval "echo get_option( 'content_freshness_e2e_mails' )[0]['message'];" > "$PAGE"
expect_contains "the digest lists the old post" 'An old post' "$PAGE"
expect_not_contains "the digest leaves out the fresh post" 'A fresh post' "$PAGE"
expect_contains "the digest links to the editor" "post.php?post=$old_id&#038;action=edit" "$PAGE"
expect_equals "the digest is HTML" "Content-Type: text/html; charset=UTF-8" \
	"$(wp eval "echo implode( '', (array) get_option( 'content_freshness_e2e_mails' )[0]['headers'] );")"
expect_contains "the subject names the site" 'Posts that need a refresh' \
	<(wp eval "echo get_option( 'content_freshness_e2e_mails' )[0]['subject'];")

wp option update "$OPTION" 36500 --quiet
wp option delete content_freshness_e2e_mails --quiet >/dev/null 2>&1 || true
wp cron event run "$CRON_HOOK" --quiet
expect_equals "nothing is sent when nothing is stale" "0" \
	"$(wp eval "echo count( (array) get_option( 'content_freshness_e2e_mails', [] ) );")"
fetch "/?p=$old_id" >/dev/null
expect_not_contains "a longer maximum age makes the old post fresh" 'mwpd-content-freshness-notice' "$PAGE"

# Change WP_DEBUG and give PHP's opcache time to see the new wp-config.php.
set_debug() {
	wp config set WP_DEBUG "$1" --raw --quiet
	sleep 3
}

echo "Errors"
wp option update "$OPTION" 'forever' --quiet
fetch "/?p=$old_id" >/dev/null
expect_contains "a broken setting fails loudly with WP_DEBUG on" \
	'Uncaught MWPD\ContentFreshness\Exception\InvalidMaxAge' "$PAGE"
set_debug false
expect_equals "a broken setting does not take the page down with WP_DEBUG off" "200" "$(fetch "/?p=$old_id")"
expect_not_contains "nothing is thrown at the reader" 'Uncaught' "$PAGE"
expect_contains "the post still shows" 'Old content.' "$PAGE"
expect_contains "the whole page renders" '</html>' "$PAGE"
expect_not_contains "the post shows without the notice" 'mwpd-content-freshness-notice' "$PAGE"
expect_equals "the Posts list still loads" "200" "$(fetch "/wp-admin/edit.php")"
expect_contains "the Posts list still has all its rows" 'An old post' "$PAGE"
expect_not_contains "the outdated mark is left out" 'class="mwpd-content-freshness-outdated"' "$PAGE"
set_debug true
wp option update "$OPTION" 365 --quiet

echo "Deactivation"
wp plugin deactivate content-freshness --quiet
expect_equals "the digest is unscheduled" "0" "$(wp cron event list --hook="$CRON_HOOK" --format=count)"
expect_equals "the setting is kept" "365" "$(wp option get "$OPTION")"
fetch "/?p=$old_id" >/dev/null
expect_not_contains "the notice is gone" 'mwpd-content-freshness-notice' "$PAGE"

echo "Uninstall"
# A digest event left behind, as on a site deactivation did not reach.
wp cron event schedule "$CRON_HOOK" '+1 week' weekly --quiet
# uninstall_plugin() runs what deleting the plugin runs, without deleting the
# files, so the mounted copy stays in place.
wp eval "uninstall_plugin( '$PLUGIN' );"
expect_equals "the setting is removed" "missing" \
	"$(wp option get "$OPTION" >/dev/null 2>&1 && echo present || echo missing)"
expect_equals "the uninstall callback is forgotten" "" \
	"$(wp eval "\$u = get_option( 'uninstall_plugins' ); echo implode( '::', (array) ( \$u['$PLUGIN'] ?? [] ) );")"
expect_equals "a digest left behind is cleared" "0" "$(wp cron event list --hook="$CRON_HOOK" --format=count)"

echo
echo "$passed passed, $failed failed"
test "$failed" -eq 0
