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

namespace MWPD\ContentFreshness\PostsList;

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
use WP_Post;

/**
 * Shows editors, in the Posts list, how long ago each post was last updated,
 * and marks the outdated ones.
 *
 * The service is only built on the Posts list screen. Adding it changed
 * nothing in the notice, the setting or the digest: it reuses the same
 * FreshnessPolicy, and decides for itself where it is needed.
 */
final class PostAgeColumn implements Service, Registerable, Conditional, Delayed {

	// Nothing else on "current_screen" needs to run before or after the
	// column, so it registers at the default priority.
	use DefaultRegistrationPriority;

	/**
	 * Key of the column. Other plugins add columns too, so it is prefixed.
	 *
	 * @var non-empty-string
	 */
	public const COLUMN = 'mwpd-content-freshness-age';

	/**
	 * Screen of the Posts list.
	 *
	 * @var non-empty-string
	 */
	private const SCREEN = 'edit-post';

	/**
	 * View that renders a cell of the column.
	 *
	 * @var non-empty-string
	 */
	private const VIEW = 'views/post-age-column';

	/**
	 * Policy that decides whether a post is outdated.
	 */
	private FreshnessPolicy $policy;

	/**
	 * View factory to render the cells with.
	 */
	private ViewFactory $view_factory;

	/**
	 * Debug mode, which decides how loudly a failure shows.
	 */
	private DebugMode $debug_mode;

	/**
	 * Check whether the service is needed in this request.
	 *
	 * WordPress only knows the screen once current_screen has fired, which is
	 * why the service is also Delayed until then.
	 *
	 * @return bool Whether the Posts list is shown.
	 */
	public static function is_needed(): bool {
		$screen = \get_current_screen();

		return null !== $screen && self::SCREEN === $screen->id;
	}

	/**
	 * Get the action to register the service at.
	 *
	 * @return non-empty-string Registration action.
	 */
	public static function get_registration_action(): string {
		return 'current_screen';
	}

	/**
	 * Instantiate a PostAgeColumn object.
	 *
	 * @param FreshnessPolicy $policy       Policy that decides whether a post is outdated.
	 * @param ViewFactory     $view_factory View factory to render the cells with.
	 * @param DebugMode       $debug_mode   Debug mode, which decides how loudly a failure shows.
	 */
	public function __construct( FreshnessPolicy $policy, ViewFactory $view_factory, DebugMode $debug_mode ) {
		$this->policy       = $policy;
		$this->view_factory = $view_factory;
		$this->debug_mode   = $debug_mode;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		\add_filter( 'manage_post_posts_columns', [ $this, 'add_column' ] );
		\add_filter( 'manage_edit-post_sortable_columns', [ $this, 'make_sortable' ] );
		\add_action( 'manage_post_posts_custom_column', [ $this, 'render_cell' ], 10, 2 );
	}

	/**
	 * Add the column to the Posts list.
	 *
	 * @param mixed $columns Columns of the list, keyed by column key. Other
	 *                       plugins filter them too, so anything that is not
	 *                       an array is passed on unchanged.
	 *
	 * @return mixed Columns, with this one added.
	 */
	public function add_column( $columns ) {
		if ( ! \is_array( $columns ) ) {
			return $columns;
		}

		$columns[ self::COLUMN ] = \__( 'Last updated', 'content-freshness' );

		return $columns;
	}

	/**
	 * Let editors sort the list by the column.
	 *
	 * WordPress can order posts by their last update already; the column only
	 * has to ask for it.
	 *
	 * @param mixed $columns Sortable columns, mapped to their orderby value.
	 *
	 * @return mixed Sortable columns, with this one added.
	 */
	public function make_sortable( $columns ) {
		if ( ! \is_array( $columns ) ) {
			return $columns;
		}

		$columns[ self::COLUMN ] = 'modified';

		return $columns;
	}

	/**
	 * Render the cell of the column for one post.
	 *
	 * This is where control comes back from WordPress, so failures stop here:
	 * with debug mode on they surface, with it off the cell stays empty and
	 * the rest of the list renders.
	 *
	 * @param mixed $column  Key of the column being rendered.
	 * @param mixed $post_id ID of the post of the row.
	 *
	 * @throws ContentFreshnessException If the cell fails in debug mode.
	 */
	public function render_cell( $column, $post_id ): void {
		if ( self::COLUMN !== $column || ! \is_numeric( $post_id ) ) {
			return;
		}

		// Looking a row's post up by its ID is an implementation detail, not
		// state the service depends on, so it calls WordPress directly.
		$post = \get_post( (int) $post_id );

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		try {
			$context = [
				'age'      => $this->policy->age_of( $post ),
				'outdated' => $this->policy->is_outdated( $post ),
			];

			$view = $this->view_factory->create( self::VIEW );

			// The view escapes what it prints. PHPCS cannot tell, see phpcs.xml.dist.
			echo $view->render( $context ); // phpcs:ignore WordPress.Security.EscapeOutput
		} catch ( ContentFreshnessException $exception ) {
			if ( $this->debug_mode->is_debug_mode() ) {
				throw $exception;
			}
		}
	}
}
