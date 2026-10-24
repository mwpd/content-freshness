<?php

declare(strict_types=1);

namespace MWPD\ContentFreshness\Tests\Fixture\Asset;

use MWPD\ContentFreshness\Infrastructure\Asset\AssetContext;
use MWPD\ContentFreshness\Infrastructure\Asset\Style;
use MWPD\ContentFreshness\Infrastructure\HasAssets;
use MWPD\ContentFreshness\Infrastructure\Registerable;
use MWPD\ContentFreshness\Infrastructure\Service;

/**
 * A registered service with a stylesheet on the front end.
 */
final class FrontEndStyleService implements Service, Registerable, HasAssets {

	public const HANDLE = 'acme-front-end';

	private AssetRequests $requests;

	public function __construct( AssetRequests $requests ) {
		$this->requests = $requests;
		$requests->built( 'front end style' );
	}

	public function register(): void {
	}

	public function get_assets( AssetContext $context ): iterable {
		$this->requests->asked( 'front end style', $context );

		return $context->is_front_end() ? [ Style::file( self::HANDLE, 'assets/front-end.css' ) ] : [];
	}
}
