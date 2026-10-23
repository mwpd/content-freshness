<?php
/**
 * Body of the weekly email that lists the posts that need a refresh.
 *
 * Rendered by StaleContentDigest. The email is HTML, so every value is
 * escaped for HTML here too: post titles are user content.
 *
 * @package MWPD\ContentFreshness
 */

use MWPD\ContentFreshness\Freshness\PostAge;
use MWPD\ContentFreshness\Infrastructure\View;

/** @var View $this */
/** @var list<array{post: WP_Post, age: PostAge}> $entries */
$entries = $this->raw( 'entries' );
?>
<p>
	<?= esc_html__( 'These posts have not been updated for a while.', 'content-freshness' ) ?>
	<?= esc_html__( 'Readers now see a notice that they may be out of date.', 'content-freshness' ) ?>
</p>
<ul>
	<?php foreach ( $entries as $entry ) : ?>
		<?php
		$post_title = get_the_title( $entry['post'] );
		$post_title = '' === $post_title ? __( '(no title)', 'content-freshness' ) : $post_title;
		$age        = $entry['age'];
		$edit_url   = add_query_arg(
			[
				'post'   => $entry['post']->ID,
				'action' => 'edit',
			],
			admin_url( 'post.php' )
		);
		$age_text   = sprintf(
			/* translators: %s: Time since the post was last updated, such as "3 years". */
			__( 'last updated %s ago', 'content-freshness' ),
			human_time_diff( $age->last_updated()->getTimestamp(), $age->measured_at()->getTimestamp() )
		);
		?>
	<li>
		<a href="<?= esc_url( $edit_url ) ?>"><?= esc_html( $post_title ) ?></a>
		&ndash; <?= esc_html( $age_text ) ?>
	</li>
	<?php endforeach; ?>
</ul>
<p>
	<a href="<?= esc_url( admin_url( 'edit.php?orderby=modified&order=asc' ) ) ?>">
		<?= esc_html__( 'See all posts by last update', 'content-freshness' ) ?>
	</a>
</p>
