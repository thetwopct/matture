<?php
/**
 * Test the Matture_Hooks class.
 *
 * @package matture
 */

namespace Matture\Tests;

use Matture\Matture_Hooks;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test the render_block() method and all extensibility hooks.
 */
class Test_Hooks extends TestCase {

	/**
	 * Test that render_block() includes the passed inner content in output.
	 *
	 * @dataProvider mode_provider
	 */
	public function test_render_includes_inner_content( $mode ) {
		$attributes = array( 'mode' => $mode );
		$content    = '<p>Inner content for ' . $mode . '</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( $content, $output );
		$this->assertStringContainsString( 'matture-gate__content', $output );
	}

	/**
	 * Test that render_block() renders correct CSS class per mode.
	 *
	 * @dataProvider mode_provider
	 */
	public function test_renders_correct_css_class_per_mode( $mode ) {
		$attributes = array( 'mode' => $mode );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'matture-gate--' . $mode, $output );
	}

	/**
	 * Test that render_block() renders correct data-mode attribute per mode.
	 *
	 * @dataProvider mode_provider
	 */
	public function test_renders_correct_data_mode_attribute_per_mode( $mode ) {
		$attributes = array( 'mode' => $mode );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'data-mode="' . $mode . '"', $output );
	}

	/**
	 * Test that render_block() renders data-matture-state="hidden" on initial render.
	 */
	public function test_renders_data_matture_state_hidden_on_initial_render() {
		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'data-matture-state="hidden"', $output );
	}

	/**
	 * Test that render_block() renders aria-expanded="false" on initial render.
	 */
	public function test_renders_aria_expanded_false_on_initial_render() {
		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'aria-expanded="false"', $output );
	}

	/**
	 * Test that matture_block_attributes filter modifies attributes before render.
	 */
	public function test_matture_block_attributes_filter_modifies_attributes() {
		add_filter(
			'matture_block_attributes',
			function ( $attributes ) {
				$attributes['mode'] = 'spoiler';
				return $attributes;
			}
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'data-mode="spoiler"', $output );
		$this->assertStringContainsString( 'matture-gate--spoiler', $output );

		remove_all_filters( 'matture_block_attributes' );
	}

	/**
	 * Test that matture_default_warning_text filter changes warning label in output.
	 */
	public function test_matture_default_warning_text_filter_changes_label() {
		add_filter(
			'matture_default_warning_text',
			function ( $defaults, $mode ) {
				$defaults['mature']['label'] = 'Custom Warning Text';
				return $defaults;
			},
			10,
			2
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'Custom Warning Text', $output );

		remove_all_filters( 'matture_default_warning_text' );
	}

	/**
	 * Test that matture_overlay_html filter replaces overlay HTML in output.
	 */
	public function test_matture_overlay_html_filter_replaces_overlay() {
		add_filter(
			'matture_overlay_html',
			function ( $overlay_html, $attributes ) {
				return '<div class="custom-overlay">Custom overlay</div>';
			},
			10,
			2
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'custom-overlay', $output );
		$this->assertStringContainsString( 'Custom overlay', $output );

		remove_all_filters( 'matture_overlay_html' );
	}

	/**
	 * Test that matture_modes filter — invalid mode falls back to 'mature'.
	 */
	public function test_matture_modes_filter_invalid_mode_fallback() {
		// Filter out 'nsfw' so it becomes invalid.
		add_filter(
			'matture_modes',
			function ( $modes ) {
				return array_diff( $modes, array( 'nsfw' ) );
			}
		);

		$attributes = array( 'mode' => 'nsfw' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		// Should fallback to 'mature' in overlay class.
		$this->assertStringContainsString( 'matture-gate__overlay--mature', $output );

		remove_all_filters( 'matture_modes' );
	}

	/**
	 * Test that matture_before_overlay action fires during render.
	 */
	public function test_matture_before_overlay_action_fires() {
		$counter = 0;
		add_action(
			'matture_before_overlay',
			function () use ( &$counter ) {
				++$counter;
			}
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		Matture_Hooks::render_block( $attributes, $content );

		$this->assertEquals( 1, $counter );

		remove_all_actions( 'matture_before_overlay' );
	}

	/**
	 * Test that matture_after_overlay action fires during render.
	 */
	public function test_matture_after_overlay_action_fires() {
		$counter = 0;
		add_action(
			'matture_after_overlay',
			function () use ( &$counter ) {
				++$counter;
			}
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		Matture_Hooks::render_block( $attributes, $content );

		$this->assertEquals( 1, $counter );

		remove_all_actions( 'matture_after_overlay' );
	}

	/**
	 * Test that matture_before_content action fires during render.
	 */
	public function test_matture_before_content_action_fires() {
		$counter = 0;
		add_action(
			'matture_before_content',
			function () use ( &$counter ) {
				++$counter;
			}
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		Matture_Hooks::render_block( $attributes, $content );

		$this->assertEquals( 1, $counter );

		remove_all_actions( 'matture_before_content' );
	}

	/**
	 * Test that matture_after_content action fires during render.
	 */
	public function test_matture_after_content_action_fires() {
		$counter = 0;
		add_action(
			'matture_after_content',
			function () use ( &$counter ) {
				++$counter;
			}
		);

		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		Matture_Hooks::render_block( $attributes, $content );

		$this->assertEquals( 1, $counter );

		remove_all_actions( 'matture_after_content' );
	}

	/**
	 * Test that allowRehide attribute does NOT affect PHP render (it's a JS behaviour).
	 */
	public function test_allow_rehide_attribute_does_not_affect_php_render() {
		$attributes_true  = array(
			'mode'        => 'mature',
			'allowRehide' => true,
		);
		$attributes_false = array(
			'mode'        => 'mature',
			'allowRehide' => false,
		);
		$content          = '<p>Test content</p>';
		$output_true      = Matture_Hooks::render_block( $attributes_true, $content );
		$output_false     = Matture_Hooks::render_block( $attributes_false, $content );

		// Both should have the data attribute, but it doesn't change the HTML structure.
		$this->assertStringContainsString( 'data-allow-rehide="1"', $output_true );
		$this->assertStringContainsString( 'data-allow-rehide="0"', $output_false );
		// No re-hide button in PHP render (JS adds it).
		$this->assertStringNotContainsString( 'rehide-btn', $output_true );
		$this->assertStringNotContainsString( 'rehide-btn', $output_false );
	}

	/**
	 * Test that warningLabel custom text appears in rendered HTML.
	 */
	public function test_warning_label_custom_text_appears() {
		$attributes = array(
			'mode'         => 'mature',
			'warningLabel' => 'My Custom Warning',
		);
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'My Custom Warning', $output );
	}

	/**
	 * Test that buttonLabel custom text appears in rendered HTML.
	 */
	public function test_button_label_custom_text_appears() {
		$attributes = array(
			'mode'        => 'mature',
			'buttonLabel' => 'My Custom Button',
		);
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'My Custom Button', $output );
	}

	/**
	 * Test that subLabel custom text appears in rendered HTML when set.
	 */
	public function test_sub_label_custom_text_appears_when_set() {
		$attributes = array(
			'mode'     => 'mature',
			'subLabel' => 'My Custom Sublabel',
		);
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'My Custom Sublabel', $output );
		$this->assertStringContainsString( 'matture-gate__sub-label', $output );
	}

	/**
	 * Test that subLabel does not appear when not set.
	 */
	public function test_sub_label_does_not_appear_when_not_set() {
		$attributes = array( 'mode' => 'mature' );
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringNotContainsString( 'matture-gate__sub-label', $output );
	}

	/**
	 * Test that showIcon renders icon span with SVG when true.
	 */
	public function test_show_icon_renders_icon_span_when_true() {
		$attributes = array(
			'mode'     => 'mature',
			'showIcon' => true,
		);
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'matture-gate__icon', $output );
		$this->assertStringContainsString( '<svg ', $output );
	}

	/**
	 * Test that showIcon renders the correct SVG icon per mode.
	 *
	 * @dataProvider mode_provider
	 */
	public function test_show_icon_renders_correct_svg_per_mode( $mode ) {
		$attributes = array(
			'mode'     => $mode,
			'showIcon' => true,
		);
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringContainsString( 'matture-gate__icon', $output );
		$this->assertStringContainsString( '<svg ', $output );
		$this->assertStringContainsString( '</svg>', $output );
	}

	/**
	 * Test that showIcon does not render icon span when false.
	 */
	public function test_show_icon_does_not_render_icon_span_when_false() {
		$attributes = array(
			'mode'     => 'mature',
			'showIcon' => false,
		);
		$content    = '<p>Test content</p>';
		$output     = Matture_Hooks::render_block( $attributes, $content );

		$this->assertStringNotContainsString( 'matture-gate__icon', $output );
	}

	/**
	 * Data provider for mode tests.
	 *
	 * @return array
	 */
	public function mode_provider() {
		return array(
			'nsfw'    => array( 'nsfw' ),
			'mature'  => array( 'mature' ),
			'spoiler' => array( 'spoiler' ),
			'trigger' => array( 'trigger' ),
		);
	}
}
