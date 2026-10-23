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

namespace MWPD\ContentFreshness\Notice;

use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Freshness\FreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\{
	Conditional,
	DefaultRegistrationPriority,
	Delayed,
	Registerable,
	Service,
	Service\DebugMode,
	ViewFactory
};

/**
 * Warns readers when the post they are reading is out of date.
 *
 * The service says, once, where it acts: on a single post, and only once the
 * query has run so WordPress can tell. In any other request it is never built.
 */
final class OutdatedPostNotice implements Service, Registerable, Conditional, Delayed {

	// Nothing else on "wp" needs to run before or after the notice, so it
	// registers at the default priority.
	use DefaultRegistrationPriority;

	/**
	 * View that renders the notice.
	 *
	 * @var non-empty-string
	 */
	private const VIEW = 'views/outdated-post-notice';

	/**
	 * Policy that decides whether the post is outdated.
	 */
	private FreshnessPolicy $policy;

	/**
	 * Main loop that tells which post is being rendered.
	 */
	private MainLoop $main_loop;

	/**
	 * View factory to render the notice with.
	 */
	private ViewFactory $view_factory;

	/**
	 * Debug mode, which decides how loudly a failure shows.
	 */
	private DebugMode $debug_mode;

	/**
	 * Check whether the service is needed in this request.
	 *
	 * WordPress can only answer is_singular() once the query has run, which
	 * is why the service is also Delayed: the plugin asks this after the "wp"
	 * action.
	 *
	 * @return bool Whether a single post is being viewed.
	 */
	public static function is_needed(): bool {
		return \is_singular( 'post' );
	}

	/**
	 * Get the action to register the service at.
	 *
	 * @return non-empty-string Registration action.
	 */
	public static function get_registration_action(): string {
		return 'wp';
	}

	/**
	 * Instantiate an OutdatedPostNotice object.
	 *
	 * @param FreshnessPolicy $policy       Policy that decides whether the post is outdated.
	 * @param MainLoop        $main_loop    Main loop that tells which post is being rendered.
	 * @param ViewFactory     $view_factory View factory to render the notice with.
	 * @param DebugMode       $debug_mode   Debug mode, which decides how loudly a failure shows.
	 */
	public function __construct(
		FreshnessPolicy $policy,
		MainLoop $main_loop,
		ViewFactory $view_factory,
		DebugMode $debug_mode
	) {
		$this->policy       = $policy;
		$this->main_loop    = $main_loop;
		$this->view_factory = $view_factory;
		$this->debug_mode   = $debug_mode;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		\add_filter( 'the_content', [ $this, 'prepend_notice' ] );
	}

	/**
	 * Prepend the notice to the content of an outdated post.
	 *
	 * WordPress runs the_content for every post on the page, so the post is
	 * resolved here, while the filter runs, and only the queried post in the
	 * main loop gets the notice.
	 *
	 * This is where control comes back from WordPress, so failures stop here:
	 * with debug mode on they surface, with it off the post shows without the
	 * notice rather than not at all.
	 *
	 * @param mixed $content Content of the post. Other plugins may filter it
	 *                       into anything, so it is only changed if it is a string.
	 *
	 * @throws ContentFreshnessException If the notice fails in debug mode.
	 *
	 * @return mixed Content, with the notice prepended if the post is outdated.
	 */
	public function prepend_notice( $content ) {
		if ( ! \is_string( $content ) || ! $this->main_loop->is_rendering_queried_post() ) {
			return $content;
		}

		try {
			$post = $this->main_loop->queried_post();

			if ( ! $this->policy->is_outdated( $post ) ) {
				return $content;
			}

			$notice = $this->view_factory->create( self::VIEW )
				->render( [ 'age' => $this->policy->age_of( $post ) ] );

			// Content filtered twice, by a theme or another plugin, gets
			// the notice once.
			if ( false !== \strpos( $content, $notice ) ) {
				return $content;
			}

			return $notice . $content;
		} catch ( ContentFreshnessException $exception ) {
			if ( $this->debug_mode->is_debug_mode() ) {
				throw $exception;
			}

			return $content;
		}
	}
}
