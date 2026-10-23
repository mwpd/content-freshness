<?php
/**
 * Field for the maximum age under Settings › Reading.
 *
 * Rendered by FreshnessSetting. Every value is read as a property, which the
 * view escapes for HTML, attributes included.
 *
 * @package MWPD\ContentFreshness
 */

use MWPD\ContentFreshness\Infrastructure\View;

/** @var View $this */
?>
<input
	name="<?= $this->name ?>"
	id="<?= $this->name ?>"
	type="number"
	min="<?= $this->minimum ?>"
	max="<?= $this->maximum ?>"
	step="1"
	value="<?= $this->days ?>"
	class="small-text"
	aria-describedby="<?= $this->name ?>-description"
>
<?= esc_html__( 'days', 'content-freshness' ) ?>
<p class="description" id="<?= $this->name ?>-description">
	<?= esc_html__( 'Readers see a notice on posts that have not been updated for longer.', 'content-freshness' ) ?>
	<?= esc_html__( 'Editors find them marked in the Posts list and in a weekly email digest.', 'content-freshness' ) ?>
</p>
