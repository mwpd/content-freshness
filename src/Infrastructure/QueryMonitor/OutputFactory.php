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

namespace MWPD\ContentFreshness\Infrastructure\QueryMonitor;

use MWPD\ContentFreshness\Infrastructure\Registration\RegistrationReport;
use QM_Collector;
use QM_Output_Html;

/**
 * Create the Query Monitor output of the registration panel.
 *
 * Query Monitor prints a panel through an outputter, an object of a class
 * that extends its QM_Output_Html. Like the collector, it can only be created
 * while Query Monitor is loaded, so a factory creates it once the panel is
 * registered.
 */
interface OutputFactory {

	/**
	 * Create the output of the panel.
	 *
	 * Only call this while Query Monitor is running.
	 *
	 * @param QM_Collector       $collector Collector of the panel.
	 * @param RegistrationReport $report    Registration history to show.
	 *
	 * @return QM_Output_Html Output of the panel.
	 */
	public function create( QM_Collector $collector, RegistrationReport $report ): QM_Output_Html;
}
