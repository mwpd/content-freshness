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

namespace MWPD\ContentFreshness\Digest;

use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Exception\FailedToScheduleDigest;
use MWPD\ContentFreshness\Exception\FailedToSendDigest;
use MWPD\ContentFreshness\Freshness\Clock;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\{
	Activateable,
	Deactivateable,
	Registerable,
	Service,
	Service\DebugMode,
	ViewFactory
};
use WP_Post;

/**
 * Emails editors a weekly list of the posts that need a refresh.
 *
 * Activation schedules the weekly event and deactivation clears it. The
 * service is deliberately not Conditional: WP-Cron runs the event in whatever
 * request comes along, so the callback has to be hooked in every request, and
 * activation skips a Conditional service that is not needed in its request.
 */
final class StaleContentDigest implements Service, Registerable, Activateable, Deactivateable {

	/**
	 * Cron hook the weekly event fires.
	 *
	 * @var non-empty-string
	 */
	public const CRON_HOOK = 'mwpd_content_freshness_send_digest';

	/**
	 * Largest number of posts one digest lists. A digest of a thousand posts
	 * gets read by nobody, and the Posts list has the rest, sorted.
	 *
	 * @var int
	 */
	public const SIZE = 20;

	/**
	 * How often the digest is sent.
	 *
	 * @var non-empty-string
	 */
	private const RECURRENCE = 'weekly';

	/**
	 * Seconds until the first digest: a week after activation, so turning the
	 * plugin on does not send email at once.
	 *
	 * @var int
	 */
	private const FIRST_DELAY = 604800;

	/**
	 * Capability of the users who get the digest: those who can edit
	 * other people's posts, Editors and Administrators by default.
	 *
	 * @var non-empty-string
	 */
	private const RECIPIENT_CAPABILITY = 'edit_others_posts';

	/**
	 * View that renders the body of the email.
	 *
	 * @var non-empty-string
	 */
	private const VIEW = 'views/stale-content-digest';

	/**
	 * Policy that decides which posts are outdated.
	 */
	private FreshnessPolicy $policy;

	/**
	 * Stale posts to list.
	 */
	private StalePosts $stale_posts;

	/**
	 * Clock that dates the first digest.
	 */
	private Clock $clock;

	/**
	 * View factory to render the email with.
	 */
	private ViewFactory $view_factory;

	/**
	 * Debug mode, which decides how loudly a failure shows.
	 */
	private DebugMode $debug_mode;

	/**
	 * Instantiate a StaleContentDigest object.
	 *
	 * @param FreshnessPolicy $policy       Policy that decides which posts are outdated.
	 * @param StalePosts      $stale_posts  Stale posts to list.
	 * @param Clock           $clock        Clock that dates the first digest.
	 * @param ViewFactory     $view_factory View factory to render the email with.
	 * @param DebugMode       $debug_mode   Debug mode, which decides how loudly a failure shows.
	 */
	public function __construct(
		FreshnessPolicy $policy,
		StalePosts $stale_posts,
		Clock $clock,
		ViewFactory $view_factory,
		DebugMode $debug_mode
	) {
		$this->policy       = $policy;
		$this->stale_posts  = $stale_posts;
		$this->clock        = $clock;
		$this->view_factory = $view_factory;
		$this->debug_mode   = $debug_mode;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		\add_action( self::CRON_HOOK, [ $this, 'send' ], 10, 0 );
	}

	/**
	 * Schedule the weekly digest.
	 *
	 * Activating twice must not send two digests a week, so an event that is
	 * scheduled already stays as it is.
	 *
	 * @throws FailedToScheduleDigest If WordPress cannot schedule the event.
	 */
	public function activate(): void {
		if ( false !== \wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		$result = \wp_schedule_event(
			$this->clock->now()->getTimestamp() + self::FIRST_DELAY,
			self::RECURRENCE,
			self::CRON_HOOK,
			[],
			true
		);

		if ( \is_wp_error( $result ) ) {
			throw FailedToScheduleDigest::when_scheduling( $result->get_error_message() );
		}
	}

	/**
	 * Stop the weekly digest.
	 *
	 * @throws FailedToScheduleDigest If WordPress cannot clear the event.
	 */
	public function deactivate(): void {
		$result = \wp_clear_scheduled_hook( self::CRON_HOOK, [], true );

		if ( \is_wp_error( $result ) ) {
			throw FailedToScheduleDigest::when_clearing( $result->get_error_message() );
		}
	}

	/**
	 * Send the digest.
	 *
	 * WP-Cron calls this, and nobody watches a cron request, so failures stop
	 * here: with debug mode on they surface for a developer running the event
	 * by hand, with it off the run ends and next week's runs as planned.
	 *
	 * @throws ContentFreshnessException If sending fails in debug mode.
	 */
	public function send(): void {
		try {
			$this->send_to_editors();
		} catch ( ContentFreshnessException $exception ) {
			if ( $this->debug_mode->is_debug_mode() ) {
				throw $exception;
			}
		}
	}

	/**
	 * Email the stale posts to every editor, one email each, so no editor
	 * sees the addresses of the others. Nothing is sent when nothing is
	 * stale. A policy that cannot list the posts throws too, and send()
	 * handles both.
	 *
	 * @throws FailedToSendDigest If WordPress could not send to some editors.
	 */
	private function send_to_editors(): void {
		$posts = $this->stale_posts->last_updated_before( $this->policy->outdated_before(), self::SIZE );

		if ( [] === $posts ) {
			return;
		}

		$entries = \array_map(
			fn( WP_Post $post ): array => [
				'post' => $post,
				'age'  => $this->policy->age_of( $post ),
			],
			$posts
		);

		$subject = \sprintf(
			/* translators: %s: Site title. */
			\__( '[%s] Posts that need a refresh', 'content-freshness' ),
			$this->get_site_title()
		);
		$body    = $this->view_factory->create( self::VIEW )->render( [ 'entries' => $entries ] );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$failed  = [];

		foreach ( $this->get_recipients() as $recipient ) {
			if ( ! \wp_mail( $recipient, $subject, $body, $headers ) ) {
				$failed[] = $recipient;
			}
		}

		if ( [] !== $failed ) {
			throw FailedToSendDigest::to_recipients( $failed );
		}
	}

	/**
	 * Get the email addresses of the users who get the digest.
	 *
	 * @return list<string> Valid email addresses.
	 */
	private function get_recipients(): array {
		$users      = \get_users(
			[
				'capability' => self::RECIPIENT_CAPABILITY,
				'fields'     => [ 'user_email' ],
			]
		);
		$recipients = [];

		foreach ( $users as $user ) {
			$email = \is_object( $user ) && isset( $user->user_email ) ? $user->user_email : null;

			if ( \is_string( $email ) && false !== \is_email( $email ) ) {
				$recipients[] = $email;
			}
		}

		return \array_values( \array_unique( $recipients ) );
	}

	/**
	 * Get the site title as plain text, for the subject line.
	 *
	 * @return string Site title.
	 */
	private function get_site_title(): string {
		$title = \get_option( 'blogname' );

		return \is_string( $title ) ? \wp_specialchars_decode( $title, \ENT_QUOTES ) : '';
	}
}
