<?php
/**
 * Configuration for shipmonk/composer-dependency-analyser.
 *
 * The analyser loads this file before Composer's autoloader, so it loads the
 * autoloader itself. The paths it scans come from RepositoryLayout, shared
 * with the other checks of the repository's structure.
 *
 * Run it through the architecture test suite, see docs/architecture-rules.md.
 *
 * @package MWPD\ContentFreshness
 */

declare( strict_types=1 );

use MWPD\ContentFreshness\Tests\Architecture\RepositoryLayout;
use MWPD\ContentFreshness\Tests\Architecture\RuntimeDependencyConfiguration;

require_once __DIR__ . '/vendor/autoload.php';

return RuntimeDependencyConfiguration::for_layout( RepositoryLayout::of_plugin() );
