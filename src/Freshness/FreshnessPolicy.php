<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2026 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Freshness;

use DateTimeImmutable;
use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use WP_Post;

/**
 * Decides when a post is out of date.
 *
 * The notice, the column and the digest all ask this one question, so they
 * depend on this interface rather than on how the answer is found. The
 * composition root binds it to OptionFreshnessPolicy; a test, or a site with
 * other rules, binds something else and no service changes.
 */
interface FreshnessPolicy {

	/**
	 * Check whether a post is out of date.
	 *
	 * @param WP_Post $post Post to check.
	 *
	 * @throws ContentFreshnessException If the policy cannot tell.
	 *
	 * @return bool Whether the post is out of date.
	 */
	public function is_outdated( WP_Post $post ): bool;

	/**
	 * Get how long ago a post was last updated.
	 *
	 * @param WP_Post $post Post to measure.
	 *
	 * @throws ContentFreshnessException If the age cannot be measured.
	 *
	 * @return PostAge Age of the post.
	 */
	public function age_of( WP_Post $post ): PostAge;

	/**
	 * Get the moment before which a post's last update makes it outdated.
	 *
	 * This lets a query find outdated posts in the database, instead of
	 * loading every post to ask is_outdated().
	 *
	 * @throws ContentFreshnessException If the policy cannot tell.
	 *
	 * @return DateTimeImmutable Latest last-updated moment of an outdated post.
	 */
	public function outdated_before(): DateTimeImmutable;
}
