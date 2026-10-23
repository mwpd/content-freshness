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
use MWPD\ContentFreshness\Exception\FailedToDetermineAge;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use WP_Post;

/**
 * A freshness policy with one rule: a post is outdated once its last update is
 * older than the maximum age set under Settings › Reading.
 */
final class OptionFreshnessPolicy implements FreshnessPolicy {

	/**
	 * Name of the option that stores the maximum age in days.
	 *
	 * @var non-empty-string
	 */
	public const OPTION_NAME = 'mwpd_content_freshness_max_age';

	/**
	 * Clock to measure ages against.
	 */
	private Clock $clock;

	/**
	 * Maximum age, once read from the option.
	 */
	private ?MaxAge $max_age = null;

	/**
	 * Instantiate an OptionFreshnessPolicy object.
	 *
	 * @param Clock $clock Clock to measure ages against.
	 */
	public function __construct( Clock $clock ) {
		$this->clock = $clock;
	}

	/**
	 * Check whether a post is out of date.
	 *
	 * @param WP_Post $post Post to check.
	 *
	 * @throws FailedToDetermineAge If WordPress cannot tell when the post was last updated.
	 * @throws InvalidMaxAge If the stored maximum age is not valid.
	 *
	 * @return bool Whether the post is out of date.
	 */
	public function is_outdated( WP_Post $post ): bool {
		return $this->last_updated( $post ) < $this->outdated_before();
	}

	/**
	 * Get how long ago a post was last updated.
	 *
	 * @param WP_Post $post Post to measure.
	 *
	 * @throws FailedToDetermineAge If WordPress cannot tell when the post was last updated.
	 *
	 * @return PostAge Age of the post.
	 */
	public function age_of( WP_Post $post ): PostAge {
		return PostAge::between( $this->last_updated( $post ), $this->clock->now() );
	}

	/**
	 * Get the moment before which a post's last update makes it outdated.
	 *
	 * @throws InvalidMaxAge If the stored maximum age is not valid.
	 *
	 * @return DateTimeImmutable Latest last-updated moment of an outdated post.
	 */
	public function outdated_before(): DateTimeImmutable {
		return $this->get_max_age()->before( $this->clock->now() );
	}

	/**
	 * Get the maximum age.
	 *
	 * The option is read once, here at the boundary, and turned into a MaxAge.
	 * From then on the policy works with a number of days it can trust, and a
	 * broken value fails loudly with InvalidMaxAge instead of turning every
	 * post fresh, or every post outdated, without a trace.
	 *
	 * @throws InvalidMaxAge If the stored maximum age is not valid.
	 *
	 * @return MaxAge Maximum age.
	 */
	private function get_max_age(): MaxAge {
		if ( null === $this->max_age ) {
			$this->max_age = MaxAge::from_value( \get_option( self::OPTION_NAME, MaxAge::DEFAULT_DAYS ) );
		}

		return $this->max_age;
	}

	/**
	 * Get the moment a post was last updated.
	 *
	 * @param WP_Post $post Post to read.
	 *
	 * @throws FailedToDetermineAge If WordPress cannot tell when the post was last updated.
	 *
	 * @return DateTimeImmutable Moment of the last update.
	 */
	private function last_updated( WP_Post $post ): DateTimeImmutable {
		$last_updated = \get_post_datetime( $post, 'modified', 'gmt' );

		// WordPress signals "no date" with false, for example for a draft that
		// was never saved with one. Turn that into an exception right here.
		if ( ! $last_updated instanceof DateTimeImmutable ) {
			throw FailedToDetermineAge::for_post( $post->ID );
		}

		return $last_updated;
	}
}
