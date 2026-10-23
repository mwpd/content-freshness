<?php
use MWPD\ContentFreshness\Infrastructure\View;

/** @var View $this */
?>
<p>Rendering works with partials: <?= $this->render_partial( 'tests/php/Fixture/views/partial' ) ?>.</p>
