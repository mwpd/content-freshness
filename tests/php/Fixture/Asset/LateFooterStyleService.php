<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Asset;

use MWPD\ContentFreshness\Infrastructure\Asset\AssetContext;
use MWPD\ContentFreshness\Infrastructure\Asset\Style;
use MWPD\ContentFreshness\Infrastructure\DefaultRegistrationPriority;
use MWPD\ContentFreshness\Infrastructure\Delayed;
use MWPD\ContentFreshness\Infrastructure\HasAssets;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A service that is only registered at wp_footer, after the enqueue points of
 * the page, and wants a style at every one of them.
 */
final class LateFooterStyleService implements Service, Delayed, HasAssets {

	use DefaultRegistrationPriority;

	public const HANDLE = 'acme-late';

	private AssetRequests $requests;

	public function __construct( AssetRequests $requests ) {
		$this->requests = $requests;
	}

	public static function get_registration_action(): string {
		return 'wp_footer';
	}

	public function get_assets( AssetContext $context ): iterable {
		$this->requests->asked( 'late style', $context );

		$where = $context->is_admin() ? $context->get_hook_suffix() : 'page';

		return [ Style::inline( self::HANDLE . '-' . $where, 'p { margin: 0; }' ) ];
	}
}
