<?php
/**
 * Notice that warns readers a post may be out of date.
 *
 * Rendered by OutdatedPostNotice ahead of the post content. A theme can
 * override it with its own views/outdated-post-notice.php.
 *
 * @package MWPD\ContentFreshness
 */

use MWPD\ContentFreshness\Freshness\PostAge;
use MWPD\ContentFreshness\Infrastructure\View;

/** @var View $this */
/** @var PostAge $age */
$age = $this->raw( 'age' );

/*
 * The age is raw, so the sentence built from it is escaped for HTML text.
 */
?>
<p class="mwpd-content-freshness-notice" role="note">
	<?=
	esc_html(
		sprintf(
			/* translators: %s: Time since the post was last updated, such as "3 years". */
			__( 'This post was last updated %s ago; some information may be out of date.', 'content-freshness' ),
			human_time_diff( $age->last_updated()->getTimestamp(), $age->measured_at()->getTimestamp() )
		)
	)
	?>
</p>
