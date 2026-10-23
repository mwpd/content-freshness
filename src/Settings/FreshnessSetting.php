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

namespace MWPD\ContentFreshness\Settings;

use MWPD\ContentFreshness\Exception\ContentFreshnessException;
use MWPD\ContentFreshness\Exception\InvalidMaxAge;
use MWPD\ContentFreshness\Freshness\MaxAge;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Infrastructure\{
	Delayed,
	Registerable,
	Service,
	Service\DebugMode,
	Uninstallable,
	ViewFactory
};

/**
 * Lets site owners choose, under Settings › Reading, after how many days a
 * post counts as outdated.
 *
 * The service owns the option OptionFreshnessPolicy reads: it registers it,
 * sanitizes what is saved into it, and removes it when the plugin is deleted.
 *
 * It is not Conditional. Waiting for admin_init already keeps it out of every
 * request that is not for the admin, so an is_admin() condition would add
 * nothing. It would also cost something: lifecycle events skip a Conditional
 * service that is not needed, and uninstalling through WP-CLI is not an admin
 * request, so the option would be left behind.
 */
final class FreshnessSetting implements Service, Registerable, Delayed, Uninstallable {

	/**
	 * Settings page the field is shown on, and the option group it is saved
	 * with. It is WordPress's own Reading page.
	 *
	 * @var non-empty-string
	 */
	private const PAGE = 'reading';

	/**
	 * Section of the page the field is shown in.
	 *
	 * @var non-empty-string
	 */
	private const SECTION = 'default';

	/**
	 * View that renders the field.
	 *
	 * @var non-empty-string
	 */
	private const VIEW = 'views/freshness-setting-field';

	/**
	 * View factory to render the field with.
	 */
	private ViewFactory $view_factory;

	/**
	 * Debug mode, which decides how loudly a failure shows.
	 */
	private DebugMode $debug_mode;

	/**
	 * Get the action to register the service at.
	 *
	 * @return non-empty-string Registration action.
	 */
	public static function get_registration_action(): string {
		return 'admin_init';
	}

	/**
	 * Instantiate a FreshnessSetting object.
	 *
	 * @param ViewFactory $view_factory View factory to render the field with.
	 * @param DebugMode   $debug_mode   Debug mode, which decides how loudly a failure shows.
	 */
	public function __construct( ViewFactory $view_factory, DebugMode $debug_mode ) {
		$this->view_factory = $view_factory;
		$this->debug_mode   = $debug_mode;
	}

	/**
	 * Register the service.
	 */
	public function register(): void {
		\register_setting(
			self::PAGE,
			OptionFreshnessPolicy::OPTION_NAME,
			[
				'type'              => 'integer',
				'description'       => \__( 'Days after which a post counts as outdated.', 'content-freshness' ),
				'sanitize_callback' => [ $this, 'sanitize' ],
				'default'           => MaxAge::DEFAULT_DAYS,
				'show_in_rest'      => false,
			]
		);

		\add_settings_field(
			OptionFreshnessPolicy::OPTION_NAME,
			\__( 'Posts become outdated after', 'content-freshness' ),
			[ $this, 'render_field' ],
			self::PAGE,
			self::SECTION,
			[ 'label_for' => OptionFreshnessPolicy::OPTION_NAME ]
		);
	}

	/**
	 * Uninstall the service, by removing the option it owns.
	 */
	public function uninstall(): void {
		\delete_option( OptionFreshnessPolicy::OPTION_NAME );
	}

	/**
	 * Sanitize a submitted maximum age into a number of days.
	 *
	 * WordPress calls this for every value saved into the option. A value that
	 * is not a valid maximum age is reported to the user on the settings page,
	 * and the option keeps its current value.
	 *
	 * @param mixed $value Submitted value.
	 *
	 * @return int Number of days to store.
	 */
	public function sanitize( $value ): int {
		try {
			return MaxAge::from_value( $value )->in_days();
		} catch ( InvalidMaxAge $exception ) {
			// Wrong input is the user's to fix, not a bug, so it becomes a
			// message on the settings page in debug mode too.
			\add_settings_error(
				OptionFreshnessPolicy::OPTION_NAME,
				'invalid-max-age',
				\sprintf(
					/* translators: 1: Smallest number of days allowed, 2: largest number of days allowed. */
					\__( 'Enter a whole number of days from %1$d to %2$d. Nothing was changed.', 'content-freshness' ),
					MaxAge::MINIMUM_DAYS,
					MaxAge::MAXIMUM_DAYS
				)
			);

			return $this->get_stored_max_age()->in_days();
		}
	}

	/**
	 * Render the field.
	 *
	 * This is where control comes back from WordPress, so failures stop here:
	 * with debug mode on they surface, with it off the field is left out and
	 * the rest of the settings page works.
	 *
	 * @throws ContentFreshnessException If the field fails in debug mode.
	 */
	public function render_field(): void {
		$context = [
			'name'    => OptionFreshnessPolicy::OPTION_NAME,
			'days'    => $this->get_stored_max_age()->in_days(),
			'minimum' => MaxAge::MINIMUM_DAYS,
			'maximum' => MaxAge::MAXIMUM_DAYS,
		];

		try {
			$view = $this->view_factory->create( self::VIEW );

			// The view escapes what it prints. PHPCS cannot tell, see phpcs.xml.dist.
			echo $view->render( $context ); // phpcs:ignore WordPress.Security.EscapeOutput
		} catch ( ContentFreshnessException $exception ) {
			if ( $this->debug_mode->is_debug_mode() ) {
				throw $exception;
			}
		}
	}

	/**
	 * Get the maximum age the option holds.
	 *
	 * The settings page is where a broken value gets fixed, so a value that is
	 * not a valid maximum age shows as the default here instead of failing:
	 * saving the form then stores a valid one.
	 *
	 * @return MaxAge Stored maximum age, or the default.
	 */
	private function get_stored_max_age(): MaxAge {
		try {
			return MaxAge::from_value( \get_option( OptionFreshnessPolicy::OPTION_NAME, MaxAge::DEFAULT_DAYS ) );
		} catch ( InvalidMaxAge $exception ) {
			return MaxAge::from_days( MaxAge::DEFAULT_DAYS );
		}
	}
}
