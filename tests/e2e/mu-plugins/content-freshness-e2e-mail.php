<?php
/**
 * Plugin Name: Content Freshness end-to-end mail capture
 * Description: Keeps email in an option instead of sending it, for the end-to-end tests to read.
 *
 * Only the end-to-end environment loads this, from tests/e2e/mu-plugins.
 *
 * @package MWPD\ContentFreshness
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\E2E;

/**
 * Store an email instead of sending it.
 *
 * @param mixed $short_circuit Whatever an earlier callback decided.
 * @param mixed $attributes    Arguments wp_mail() was called with.
 *
 * @return bool True, so wp_mail() reports the email as sent.
 */
function capture_mail( $short_circuit, $attributes ): bool {
	$mails   = get_option( 'content_freshness_e2e_mails', [] );
	$mails   = is_array( $mails ) ? $mails : [];
	$mails[] = $attributes;

	update_option( 'content_freshness_e2e_mails', $mails, false );

	return true;
}

add_filter( 'pre_wp_mail', __NAMESPACE__ . '\\capture_mail', 10, 2 );
