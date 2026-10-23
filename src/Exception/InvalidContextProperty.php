<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://github.com/mwpd/content-freshness
 * @copyright 2019 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Exception;

use InvalidArgumentException;

/**
 * Exception for invalid context properties.
 */
final class InvalidContextProperty extends InvalidArgumentException implements ContentFreshnessException {

	/**
	 * Create a new instance of the exception for a context property that is
	 * not recognized.
	 *
	 * @param string $property Name of the context property that was not
	 *                         recognized.
	 */
	public static function from_property( string $property ): self {
		$message = \sprintf(
			'The property "%s" could not be found within the context of the currently rendered view.',
			$property
		);

		return new self( $message );
	}
}
