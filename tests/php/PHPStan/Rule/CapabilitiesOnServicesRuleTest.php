<?php
/**
 * Content Freshness.
 *
 * @package   MWPD\ContentFreshness
 * @author    Alain Schlesser <alain.schlesser@gmail.com>
 * @license   MIT
 * @link      https://www.mwpd.io/
 * @copyright 2019 Alain Schlesser
 */

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\PHPStan\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CapabilitiesOnServicesRule>
 */
final class CapabilitiesOnServicesRuleTest extends RuleTestCase {

	private const FIXTURES = __DIR__ . '/../Fixture/Capabilities/';

	private const NAMESPACE_ROOT = 'MWPD\ContentFreshness\Tests\PHPStan\Fixture\Capabilities';

	private const TIP = 'Principle 5: One composition root builds the object graph, see '
		. 'docs/design-principles.md#5-one-composition-root-builds-the-object-graph. '
		. 'Pattern: docs/patterns.md#i-need-to-add-a-feature. '
		. 'Rule: docs/architecture-rules.md#architecturecapabilitiesonservices';

	private string $root = self::NAMESPACE_ROOT;

	private string $service = 'MWPD\ContentFreshness\Infrastructure\Service';

	private string $registerable = 'MWPD\ContentFreshness\Infrastructure\Registerable';

	private string $delayed = 'MWPD\ContentFreshness\Infrastructure\Delayed';

	/** @var array<string> */
	private array $capabilities = [
		'MWPD\ContentFreshness\Infrastructure\Registerable',
		'MWPD\ContentFreshness\Infrastructure\Conditional',
		'MWPD\ContentFreshness\Infrastructure\Delayed',
		'MWPD\ContentFreshness\Infrastructure\HasDependencies',
		'MWPD\ContentFreshness\Infrastructure\Activateable',
		'MWPD\ContentFreshness\Infrastructure\Deactivateable',
	];

	/** @var array<string> */
	private array $excluded = [];

	private string $infrastructure = 'MWPD\ContentFreshness\Infrastructure';

	/** @var array<string> */
	private array $composition_root = [];

	protected function getRule(): Rule {
		return new CapabilitiesOnServicesRule(
			new PluginNamespace( $this->root, $this->excluded ),
			$this->service,
			$this->capabilities,
			$this->delayed,
			$this->registerable,
			$this->infrastructure,
			$this->composition_root
		);
	}

	public function test_every_capability_requires_service(): void {
		$capabilities = [
			'Registerable',
			'Conditional',
			'Delayed',
			'HasDependencies',
			'Activateable',
			'Deactivateable',
		];
		foreach ( $capabilities as $name ) {
			$errors = [ [ $this->service_message( self::NAMESPACE_ROOT . '\\' . $name . 'Only' ), 9, self::TIP ] ];
			if ( $name === 'Delayed' ) {
				$errors[] = [ $this->delayed_message( self::NAMESPACE_ROOT . '\DelayedOnly' ), 9, self::TIP ];
			}
			$this->analyse( [ self::FIXTURES . $name . 'Only.php' ], $errors );
		}
	}

	public function test_delayed_services_require_registerable(): void {
		$this->analyse(
			[ self::FIXTURES . 'DelayedService.php' ],
			[ [ $this->delayed_message( self::NAMESPACE_ROOT . '\DelayedService' ), 10, self::TIP ] ]
		);
	}

	public function test_capabilities_inherited_from_a_base_class_are_checked(): void {
		$this->analyse(
			[ self::FIXTURES . 'BaseRegistration.php', self::FIXTURES . 'InheritedRegistration.php' ],
			[
				[ $this->service_message( self::NAMESPACE_ROOT . '\BaseRegistration' ), 9, self::TIP ],
				[ $this->service_message( self::NAMESPACE_ROOT . '\InheritedRegistration' ), 7, self::TIP ],
			]
		);
	}

	public function test_services_and_non_class_declarations_pass(): void {
		$this->analyse(
			array_map(
				static fn( string $name ): string => self::FIXTURES . $name . '.php',
				[ 'Capability', 'Helper', 'OrdinaryClass', 'PlainService', 'BaseService', 'FullService' ]
			),
			[]
		);
	}

	public function test_excluded_and_outside_namespaces_pass(): void {
		$this->excluded = [ self::NAMESPACE_ROOT ];
		$this->analyse(
			[ self::FIXTURES . 'RegisterableOnly.php', self::FIXTURES . 'RenamedPlugin/Feature/MissingTask.php' ],
			[]
		);
	}

	public function test_interfaces_and_namespaces_come_from_configuration(): void {
		$this->root         = self::NAMESPACE_ROOT . '\RenamedPlugin';
		$this->service      = $this->root . '\Lifecycle\Task';
		$this->registerable = $this->root . '\Lifecycle\Hooks';
		$this->delayed      = $this->root . '\Lifecycle\Later';
		$this->capabilities = [ $this->registerable, $this->delayed ];

		$this->analyse(
			[
				self::FIXTURES . 'RenamedPlugin/Feature/MissingTask.php',
				self::FIXTURES . 'RenamedPlugin/Feature/MissingHooks.php',
				self::FIXTURES . 'RenamedPlugin/Feature/FullTask.php',
			],
			[
				[ $this->service_message( $this->root . '\Feature\MissingTask' ), 11, self::TIP ],
				[ $this->delayed_message( $this->root . '\Feature\MissingHooks' ), 11, self::TIP ],
			]
		);
	}

	public function test_the_feature_services_pass(): void {
		$this->root     = 'MWPD\ContentFreshness';
		$this->excluded = [ 'MWPD\ContentFreshness\Tests' ];
		$this->analyse(
			[
				dirname( __DIR__, 4 ) . '/src/SampleSubsystem/SampleBackendService.php',
				dirname( __DIR__, 4 ) . '/src/Notice/OutdatedPostNotice.php',
			],
			[]
		);
	}

	public function test_the_infrastructure_and_composition_root_orchestrate_services(): void {
		$this->root             = 'MWPD\ContentFreshness';
		$this->composition_root = [ $this->root . '\ContentFreshnessPlugin' ];
		$this->analyse(
			[
				dirname( __DIR__, 4 ) . '/src/Infrastructure/ServiceBasedPlugin.php',
				dirname( __DIR__, 4 ) . '/src/ContentFreshnessPlugin.php',
			],
			[]
		);
	}

	public function test_composition_root_allowances_only_match_the_configured_classes(): void {
		$this->composition_root = [ self::NAMESPACE_ROOT . '\RegisterableOnly' ];
		$this->analyse(
			[ self::FIXTURES . 'RegisterableOnly.php', self::FIXTURES . 'ConditionalOnly.php' ],
			[ [ $this->service_message( self::NAMESPACE_ROOT . '\ConditionalOnly' ), 9, self::TIP ] ]
		);
	}

	private function service_message( string $class_name ): string {
		return sprintf(
			'Class %s implements a lifecycle capability without %s. Implement %s so the plugin can orchestrate it.',
			$class_name,
			$this->service,
			$this->service
		);
	}

	private function delayed_message( string $class_name ): string {
		return sprintf(
			'Class %s implements %s without %s. Implement %s and add its hooks in register()'
			. ' so delayed registration has work to run.',
			$class_name,
			$this->delayed,
			$this->registerable,
			$this->registerable
		);
	}
}
