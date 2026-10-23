<?php

declare( strict_types=1 );

namespace MWPD\ContentFreshness\Tests\Unit\Settings;

use Brain\Monkey\Functions;
use MWPD\ContentFreshness\Freshness\OptionFreshnessPolicy;
use MWPD\ContentFreshness\Exception\InvalidPath;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\LifecycleScope;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\NetworkId;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteContext;
use MWPD\ContentFreshness\Infrastructure\Lifecycle\SiteId;
use MWPD\ContentFreshness\Infrastructure\View\TemplatedViewFactory;
use MWPD\ContentFreshness\Infrastructure\ViewFactory;
use MWPD\ContentFreshness\Settings\FreshnessSetting;
use MWPD\ContentFreshness\Tests\Fixture\FixedDebugMode;
use MWPD\ContentFreshness\Tests\Unit\TestCase;

/**
 * The setting on its own, rendering the views/freshness-setting-field
 * template.
 *
 * @covers \MWPD\ContentFreshness\Settings\FreshnessSetting
 */
final class FreshnessSettingTest extends TestCase {

	protected function set_up() {
		parent::set_up();

		Functions\stubEscapeFunctions();
		Functions\stubTranslationFunctions();
	}

	public function test_it_registers_at_admin_init(): void {
		$this->assertSame( 'admin_init', FreshnessSetting::get_registration_action() );
	}

	public function test_it_adds_the_field_to_the_reading_settings(): void {
		$setting = $this->make_setting();
		Functions\expect( 'register_setting' )
			->once()
			->with(
				'reading',
				OptionFreshnessPolicy::OPTION_NAME,
				\Mockery::on(
					static fn( array $args ): bool => 'integer' === $args['type']
						&& [ $setting, 'sanitize' ] === $args['sanitize_callback']
						&& 365 === $args['default']
						&& false === $args['show_in_rest']
				)
			);
		Functions\expect( 'add_settings_field' )
			->once()
			->with(
				OptionFreshnessPolicy::OPTION_NAME,
				'Posts become outdated after',
				[ $setting, 'render_field' ],
				'reading',
				'default',
				[ 'label_for' => OptionFreshnessPolicy::OPTION_NAME ]
			);

		$setting->register();
	}

	/**
	 * @return array<string,array{mixed,int}>
	 */
	public static function data_valid_input(): array {
		return [
			'number from the form'  => [ '90', 90 ],
			'with stray whitespace' => [ ' 90 ', 90 ],
			'integer from code'     => [ 30, 30 ],
			'smallest allowed'      => [ '1', 1 ],
		];
	}

	/**
	 * @dataProvider data_valid_input
	 *
	 * @param mixed $input Submitted value.
	 */
	public function test_it_normalizes_valid_input( $input, int $expected ): void {
		Functions\expect( 'add_settings_error' )->never();

		$this->assertSame( $expected, $this->make_setting()->sanitize( $input ) );
	}

	/**
	 * @return array<string,array{mixed}>
	 */
	public static function data_invalid_input(): array {
		return [
			'empty'        => [ '' ],
			'zero'         => [ '0' ],
			'negative'     => [ '-5' ],
			'decimal'      => [ '1.5' ],
			'words'        => [ 'a year' ],
			'out of range' => [ '100000' ],
			'array'        => [ [ '90' ] ],
		];
	}

	/**
	 * @dataProvider data_invalid_input
	 *
	 * @param mixed $input Submitted value.
	 */
	public function test_it_keeps_the_stored_value_and_reports_invalid_input( $input ): void {
		Functions\when( 'get_option' )->justReturn( 120 );
		Functions\expect( 'add_settings_error' )
			->once()
			->with(
				OptionFreshnessPolicy::OPTION_NAME,
				'invalid-max-age',
				'Enter a whole number of days from 1 to 36500. Nothing was changed.'
			);

		$this->assertSame( 120, $this->make_setting()->sanitize( $input ) );
	}

	public function test_invalid_input_falls_back_to_the_default_when_the_stored_value_is_broken_too(): void {
		Functions\when( 'get_option' )->justReturn( 'broken' );
		Functions\when( 'add_settings_error' )->justReturn( null );

		$this->assertSame( 365, $this->make_setting()->sanitize( 'abc' ) );
	}

	public function test_it_renders_the_field_with_the_stored_value(): void {
		Functions\when( 'get_option' )->justReturn( '90' );

		$field = $this->render_field();

		$this->assertStringContainsString( 'name="mwpd_content_freshness_max_age"', $field );
		$this->assertStringContainsString( 'id="mwpd_content_freshness_max_age"', $field );
		$this->assertStringContainsString( 'type="number"', $field );
		$this->assertStringContainsString( 'min="1"', $field );
		$this->assertStringContainsString( 'max="36500"', $field );
		$this->assertStringContainsString( 'value="90"', $field );
		$this->assertStringContainsString( 'aria-describedby="mwpd_content_freshness_max_age-description"', $field );
		$this->assertStringContainsString( 'Readers see a notice on posts', $field );
	}

	public function test_it_renders_the_default_for_a_broken_stored_value(): void {
		Functions\when( 'get_option' )->justReturn( [ 'broken' ] );

		$this->assertStringContainsString( 'value="365"', $this->render_field() );
	}

	public function test_uninstalling_removes_the_option_from_the_site(): void {
		Functions\expect( 'delete_option' )->once()->with( OptionFreshnessPolicy::OPTION_NAME )->andReturn( true );

		$this->make_setting()->uninstall_site(
			SiteContext::for_site( SiteId::from_int( 1 ), NetworkId::from_int( 1 ), LifecycleScope::current_site() )
		);
	}

	public function test_a_failing_field_is_left_out_without_debug_mode(): void {
		Functions\when( 'get_option' )->justReturn( '90' );

		$this->assertSame( '', $this->render_field( $this->make_setting( $this->missing_views( false ), false ) ) );
	}

	public function test_a_failing_field_surfaces_in_debug_mode(): void {
		Functions\when( 'get_option' )->justReturn( '90' );

		$this->expectException( InvalidPath::class );

		$this->render_field( $this->make_setting( $this->missing_views( true ), true ) );
	}

	private function make_setting( ?ViewFactory $view_factory = null, bool $debug_mode = false ): FreshnessSetting {
		return new FreshnessSetting(
			$view_factory ?? new TemplatedViewFactory( [ dirname( __DIR__, 4 ) ] ),
			new FixedDebugMode( $debug_mode )
		);
	}

	/**
	 * Views looked up where there are none, as with a broken theme override.
	 */
	private function missing_views( bool $debug_mode ): ViewFactory {
		return new TemplatedViewFactory( [ '/nonexistent' ], new FixedDebugMode( $debug_mode ) );
	}

	private function render_field( ?FreshnessSetting $setting = null ): string {
		ob_start();
		try {
			( $setting ?? $this->make_setting() )->render_field();
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}
}
