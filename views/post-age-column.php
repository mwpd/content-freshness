<?php
/**
 * Cell of the "Last updated" column in the Posts list.
 *
 * Rendered by PostAgeColumn for each row. A theme cannot override admin
 * screens, but the path follows the same convention as the other views.
 *
 * @package MWPD\ContentFreshness
 */

use MWPD\ContentFreshness\Freshness\PostAge;
use MWPD\ContentFreshness\Infrastructure\View;

/** @var View $this */
/** @var PostAge $age */
$age = $this->raw( 'age' );

/*
 * Both values are raw: the date is escaped for the attribute, and the
 * sentence built from the age for HTML text.
 */
?>
<time datetime="<?= esc_attr( $age->last_updated()->format( DATE_W3C ) ) ?>">
	<?=
	esc_html(
		sprintf(
			/* translators: %s: Time since the post was last updated, such as "3 years". */
			__( '%s ago', 'content-freshness' ),
			human_time_diff( $age->last_updated()->getTimestamp(), $age->measured_at()->getTimestamp() )
		)
	)
	?>
</time>
<?php if ( true === $this->raw( 'outdated' ) ) : ?>
	<br>
	<strong class="mwpd-content-freshness-outdated" style="color: #b32d2e;">
		<span class="dashicons dashicons-warning" aria-hidden="true"></span>
		<?= esc_html__( 'Outdated', 'content-freshness' ) ?>
	</strong>
<?php endif; ?>
