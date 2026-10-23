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

namespace MWPD\ContentFreshness\Tests\Architecture;

use Brain\Monkey;
use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\BrokenPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\BrokenLifecyclePlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\IdempotentLifecyclePlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\CallbackPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\GoodService;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\IncompatibleBindingPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\BoundToSomethingElse;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\NotAService;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\RegistrationPanelPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\CyclicDependencyPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\MisorderedDependencyPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\ValidDependencyGraphPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\UnknownActionDependencyPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\SelfDependentPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\InvalidDependencyGraphPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\DependsOnB;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\OverlappingCyclesPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\TypoActionService;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\TypoActionPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\PrefixedActionPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\AdminMenuPlugin;
use MWPD\ContentFreshness\Tests\Fixture\CompositionRoot\EmptyHookPrefixPlugin;
use stdClass;
use WP_Post;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * The composition root keeps the promises it makes about its services.
 */
final class CompositionRootContractTest extends TestCase {

	protected function set_up() {
		parent::set_up();
		Monkey\setUp();

		// WordPress functions the wiring calls, standing in for a request.
		Functions\when( 'get_post' )->justReturn( new WP_Post( new stdClass() ) );
		Functions\when( 'get_stylesheet_directory' )->justReturn( '/nonexistent/child-theme' );
		Functions\when( 'get_template_directory' )->justReturn( '/nonexistent/parent-theme' );
		Functions\stubTranslationFunctions();
		// Registering a setting or field again replaces it, as in WordPress.
		Functions\when( 'register_setting' )->justReturn( null );
		Functions\when( 'add_settings_field' )->justReturn( null );
		Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );
		Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( '2026-10-23 12:00:00 UTC' ) );
	}

	protected function tear_down() {
		Monkey\tearDown();
		parent::tear_down();
	}

	public function test_the_plugin_keeps_the_promises_of_its_composition_root(): void {
		/**
		 * The composition root of this plugin.
		 *
		 * @var class-string<\MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin> $plugin
		 */
		$plugin   = RepositoryLayout::COMPOSITION_ROOT;
		$problems = ( new CompositionRootContract( $plugin ) )->check();

		$this->assertSame( [], $problems, implode( "\n\n", $problems ) );
	}

	/**
	 * Declaring the optional Query Monitor panel, as its documentation says,
	 * keeps every promise too: the root can build it, and registering it
	 * twice changes nothing, without Query Monitor installed.
	 */
	public function test_the_plugin_keeps_its_promises_with_the_query_monitor_panel_declared(): void {
		$problems = ( new CompositionRootContract( RegistrationPanelPlugin::class ) )->check();

		$this->assertSame( [], $problems, implode( "\n\n", $problems ) );
	}

	public function test_it_reports_the_promises_a_composition_root_breaks(): void {
		$problems = ( new CompositionRootContract( BrokenPlugin::class ) )->check();
		$prefix   = '[architecture.compositionRootContracts] The service "%s" (%s) of ' . BrokenPlugin::class . ' ';
		$fixtures = 'MWPD\\ContentFreshness\\Tests\\Fixture\\CompositionRoot\\';
		$expected = [
			[ 'hooking', 'HookingService', 'changes hooks while it is being built, directly or through' ],
			[ 'unbound', 'NeedsContract', 'cannot be built from the composition root: ' ],
			[ 'dependent', 'DependentService', 'depends on "missing", which the composition root does not declare.' ],
			[ 'delayed', 'EmptyDelayedService', 'is Delayed, but get_registration_action() returns no action.' ],
			[ 'not-a-service', 'NotAService', 'is not a class implementing Service.' ],
			[ 'removing', 'RemovingService', 'changes hooks while it is being built, directly or through' ],
			[
				'rebound',
				'BoundToSomethingElse',
				'cannot be built from the composition root: The delegate for "' . BoundToSomethingElse::class
					. '" returned ' . NotAService::class . ', not an instance of that class.',
			],
		];

		$this->assertCount( count( $expected ), $problems, implode( "\n\n", $problems ) );

		foreach ( $expected as $index => [ $id, $class_name, $problem ] ) {
			$this->assertStringStartsWith(
				sprintf( $prefix, $id, $fixtures . $class_name ) . $problem,
				$problems[ $index ]
			);
			$this->assertStringContainsString(
				'Pattern: docs/patterns.md#i-need-to-add-a-feature. '
					. 'Rule: docs/architecture-rules.md#architecturecompositionrootcontracts',
				$problems[ $index ]
			);
		}

		$this->assertStringContainsString( 'UnboundContract', $problems[1] );
	}

	public function test_it_reports_an_injector_configuration_the_plugin_rejects(): void {
		$problems = ( new CompositionRootContract( IncompatibleBindingPlugin::class ) )->check();

		$this->assertCount( 1, $problems, implode( "\n\n", $problems ) );
		$this->assertStringStartsWith(
			'[architecture.compositionRootContracts] The injector configuration of ' . IncompatibleBindingPlugin::class
				. ' is invalid: "' . BoundToSomethingElse::class . '" cannot be bound to "' . NotAService::class . '"',
			$problems[0]
		);
		$this->assertStringContainsString(
			'Rule: docs/architecture-rules.md#architecturecompositionrootcontracts',
			$problems[0]
		);
	}

	public function test_it_watches_what_callable_declarations_do(): void {
		$problems = ( new CompositionRootContract( CallbackPlugin::class ) )->check();
		$prefix   = '[architecture.compositionRootContracts] The service "%s" (%s) of ' . CallbackPlugin::class . ' ';

		$this->assertCount( 2, $problems, implode( "\n\n", $problems ) );
		$this->assertStringStartsWith(
			sprintf( $prefix, 'hooking-callback', GoodService::class ) . 'changes hooks while it is being built',
			$problems[0]
		);
		$this->assertStringStartsWith(
			sprintf( $prefix, 'failing-callback', 'callable' ) . 'cannot be resolved: ',
			$problems[1]
		);
	}

	public function test_it_reports_non_idempotent_lifecycle_steps(): void {
		$problems = ( new CompositionRootContract( BrokenLifecyclePlugin::class ) )->check();
		$expected = [
			[ 'RepeatingRegistration', 'register', 'changes the callbacks on the action "init"' ],
			[ 'RepeatingRegistration', 'register', 'changes the callbacks on the filter "the_content"' ],
			[ 'ChangingPriorityRegistration', 'register', 'changes the callbacks on the action "init"' ],
			[ 'AppendingActivation', 'activate', 'changes the post meta "fixture_runs" for object 42 again' ],
			[ 'AppendingActivation', 'activate', 'changes the user meta "fixture_runs" for object 42 again' ],
			[ 'AppendingActivation', 'activate', 'changes the term meta "fixture_runs" for object 42 again' ],
			[ 'AppendingActivation', 'activate', 'changes the comment meta "fixture_runs" for object 42 again' ],
			[ 'ChangingDeactivation', 'deactivate', 'changes the option "fixture_runs" again' ],
			[ 'ChangingTransient', 'activate', 'changes the transient "fixture_runs" again' ],
			[ 'ChangingTransient', 'activate', 'changes the user meta "fixture_runs" for object 42 again' ],
			[ 'ReschedulingActivation', 'activate', 'changes the cron events of "fixture_event" again' ],
			[
				'ChangingArgumentsScheduling',
				'activate',
				'changes the cron events of "fixture_arguments_event" again',
			],
			[
				'AppendingScopedLifecycle',
				'activate_network',
				'changes the user meta "fixture_runs" for object 42 again',
			],
			[
				'AppendingScopedLifecycle',
				'uninstall_site',
				'changes the post meta "fixture_runs" for object 42 again',
			],
		];
		$messages = [];

		foreach ( $expected as [ $service, $method, $effect ] ) {
			$messages[] = $this->lifecycle_problem(
				$service,
				'changes the plugin on the second call of ' . $method . '(): it ' . $effect
				. '. Guard the step so a second run does nothing, or make it set rather than add.'
			);
		}

		$messages[] = $this->lifecycle_problem(
			'ThrowingLifecycle',
			'throws on the first call of register(): registration failed. Make the lifecycle step safe to run twice.'
		);
		$messages[] = $this->lifecycle_problem(
			'ThrowingLifecycle',
			'throws on the second call of activate(): activation failed. Make the lifecycle step safe to run twice.'
		);
		$messages[] = $this->lifecycle_problem(
			'ThrowingLifecycle',
			'throws on the first call of deactivate(): deactivation failed. Make the lifecycle step safe to run twice.'
		);

		$this->assertSame( $messages, $problems );
	}

	public function test_it_accepts_guarded_and_setting_lifecycle_steps(): void {
		$problems = ( new CompositionRootContract( IdempotentLifecyclePlugin::class ) )->check();

		$this->assertSame( [], $problems, implode( "\n\n", $problems ) );
	}

	public function test_it_reports_a_dependency_cycle_once_with_the_fix(): void {
		$problems = ( new CompositionRootContract( CyclicDependencyPlugin::class ) )->check();

		$this->assertSame(
			[
				$this->graph_problem(
					CyclicDependencyPlugin::class,
					'a',
					'has a dependency cycle: a -> b -> a. Remove a dependency from the cycle so services can register.'
				),
			],
			$problems
		);
	}

	public function test_it_reports_each_overlapping_cycle_once(): void {
		$problems = ( new CompositionRootContract( OverlappingCyclesPlugin::class ) )->check();

		$this->assertCount( 2, $problems );
		$this->assertStringContainsString( 'a -> b -> c -> a.', $problems[0] );
		$this->assertStringContainsString( 'a -> c -> a.', $problems[1] );
	}

	public function test_it_reports_a_self_dependency_once(): void {
		$problems = ( new CompositionRootContract( SelfDependentPlugin::class ) )->check();

		$this->assertSame(
			[
				$this->graph_problem(
					SelfDependentPlugin::class,
					'b',
					'has a dependency cycle: b -> b. Remove a dependency from the cycle so services can register.'
				),
			],
			$problems
		);
	}

	public function test_it_reports_a_dependency_that_registers_later(): void {
		$problems = ( new CompositionRootContract( MisorderedDependencyPlugin::class ) )->check();

		$this->assertSame(
			[
				$this->graph_problem(
					MisorderedDependencyPlugin::class,
					'a',
					'registers at "plugins_loaded" before its dependency "b" at "wp".'
					. ' Delay this service until the dependency\'s action or later,'
					. ' or register the dependency earlier.'
				),
			],
			$problems
		);
	}

	public function test_it_accepts_dependencies_at_the_same_or_an_earlier_action(): void {
		$this->assertSame( [], ( new CompositionRootContract( ValidDependencyGraphPlugin::class ) )->check() );
	}

	public function test_it_skips_unknown_actions_on_either_side_of_a_dependency(): void {
		$this->assertSame( [], ( new CompositionRootContract( UnknownActionDependencyPlugin::class ) )->check() );
	}

	public function test_it_skips_invalid_declarations_in_graph_checks(): void {
		$problems = ( new CompositionRootContract( InvalidDependencyGraphPlugin::class ) )->check();

		$this->assertCount( 1, $problems );
		$this->assertStringContainsString( 'is not a class implementing Service.', $problems[0] );
	}

	public function test_it_uses_the_layouts_registration_action_order(): void {
		$layout = new RepositoryLayout( __DIR__, '', [], [], '', [], [], [], [ 'wp', 'plugins_loaded' ] );

		$this->assertSame(
			[],
			( new CompositionRootContract( MisorderedDependencyPlugin::class, $layout ) )->check()
		);
	}

	public function test_it_reports_an_unknown_registration_action_with_the_fix(): void {
		$this->assertSame(
			[
				'[architecture.registrationActionKnown] The service "delayed" (' . TypoActionService::class
				. ') of ' . TypoActionPlugin::class . ' is Delayed, but returns the unknown action "wp_laoded".'
				. ' Use a core action or one starting with the plugin\'s hook prefix "mwpd.content_freshness.".'
				. ' Principle 7: Show the alternative to WordPress anti-patterns, see '
				. 'docs/design-principles.md#7-show-the-alternative-to-wordpress-anti-patterns.'
				. ' Pattern: docs/patterns.md#i-need-to-hook-in-later.'
				. ' Rule: docs/architecture-rules.md#architectureregistrationactionknown.',
			],
			( new CompositionRootContract( TypoActionPlugin::class ) )->check()
		);
	}

	public function test_it_accepts_a_registration_action_with_the_roots_hook_prefix(): void {
		$this->assertSame( [], ( new CompositionRootContract( PrefixedActionPlugin::class ) )->check() );
	}

	public function test_it_accepts_a_core_action_without_a_fixed_order(): void {
		$this->assertSame( [], ( new CompositionRootContract( AdminMenuPlugin::class ) )->check() );
	}

	public function test_it_uses_the_layouts_additional_core_actions(): void {
		$layout = new RepositoryLayout(
			__DIR__,
			'',
			[],
			[],
			'',
			[],
			[],
			[],
			RepositoryLayout::REGISTRATION_ACTIONS,
			[ 'wp_laoded' ]
		);

		$this->assertSame( [], ( new CompositionRootContract( TypoActionPlugin::class, $layout ) )->check() );
	}

	public function test_it_can_remove_additional_core_actions_through_the_layout(): void {
		$layout   = new RepositoryLayout(
			__DIR__,
			'',
			[],
			[],
			'',
			[],
			[],
			[],
			RepositoryLayout::REGISTRATION_ACTIONS,
			[]
		);
		$problems = ( new CompositionRootContract( AdminMenuPlugin::class, $layout ) )->check();

		$this->assertCount( 1, $problems );
		$this->assertStringContainsString( 'returns the unknown action "admin_menu"', $problems[0] );
	}

	public function test_it_accepts_the_layouts_ordered_actions_as_known(): void {
		$layout = new RepositoryLayout( __DIR__, '', [], [], '', [], [], [], [ 'wp_laoded' ] );

		$this->assertSame( [], ( new CompositionRootContract( TypoActionPlugin::class, $layout ) )->check() );
	}

	public function test_it_rejects_an_unknown_action_when_the_hook_prefix_is_empty(): void {
		$problems = ( new CompositionRootContract( EmptyHookPrefixPlugin::class ) )->check();

		$this->assertCount( 1, $problems );
		$this->assertStringContainsString( '[architecture.registrationActionKnown]', $problems[0] );
		$this->assertStringContainsString( 'returns the unknown action "wp_laoded"', $problems[0] );
		$this->assertStringContainsString( 'hook prefix ""', $problems[0] );
	}

	/**
	 * @param class-string<\MWPD\ContentFreshness\Infrastructure\ServiceBasedPlugin> $plugin Fixture root.
	 */
	private function graph_problem( string $plugin, string $id, string $problem ): string {
		return '[architecture.compositionRootContracts] The service "' . $id . '" ('
			. DependsOnB::class . ') of ' . $plugin . ' ' . $problem . ' '
			. RuleManifest::get_explanation( RuleManifest::COMPOSITION_ROOT_CONTRACTS ) . '.';
	}

	private function lifecycle_problem( string $service, string $problem ): string {
		return '[architecture.compositionRootContracts] The service "' . $service . '" ('
			. 'MWPD\\ContentFreshness\\Tests\\Fixture\\CompositionRoot\\' . $service . ') of '
			. BrokenLifecyclePlugin::class . ' ' . $problem . ' '
			. RuleManifest::get_explanation( RuleManifest::COMPOSITION_ROOT_CONTRACTS ) . '.';
	}
}
