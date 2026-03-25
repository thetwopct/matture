<?php
/**
 * Matture - Hooks, filters, and actions extensibility layer.
 *
 * @package WordPress
 * @subpackage matture
 */

namespace Matture;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the block render callback and exposes extensibility hooks.
 */
class Matture_Hooks {

	/**
	 * Initialize the hooks layer.
	 *
	 * Hooked to the WordPress 'init' action via Matture_Init.
	 * Exists as the canonical hook point for third-party extensibility.
	 *
	 * @return void
	 */
	public static function init(): void {
		// Stub: render_callback is registered in Matture_Init::register_blocks().
		// This method is the canonical entry point for future hook registration.
	}

	/**
	 * Render callback for the content-gate block.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Inner block content (inner blocks HTML).
	 * @return string Rendered block HTML.
	 */
	public static function render_block( array $attributes, string $content ): string {
		// Apply filter to attributes before render.
		$attributes = apply_filters( 'matture_block_attributes', $attributes );

		$mode          = $attributes['mode'] ?? 'mature';
		$warning_label = $attributes['warningLabel'] ?? '';
		$button_label  = $attributes['buttonLabel'] ?? '';
		$sub_label     = $attributes['subLabel'] ?? '';
		$allow_rehide  = ! empty( $attributes['allowRehide'] );
		$blur          = $attributes['blurIntensity'] ?? 20;
		$remember      = ! empty( $attributes['rememberReveal'] );
		$show_icon     = ! empty( $attributes['showIcon'] );

		// Get filterable mode defaults.
		$defaults = apply_filters( 'matture_default_warning_text', self::get_mode_defaults(), $mode );

		if ( empty( $warning_label ) ) {
			$warning_label = $defaults[ $mode ]['label'] ?? '';
		}
		if ( empty( $button_label ) ) {
			$button_label = $defaults[ $mode ]['button'] ?? 'Reveal';
		}

		$data_attrs = sprintf(
			'data-mode="%s" data-allow-rehide="%s" data-blur="%s" data-remember="%s" data-button-label="%s" data-sub-label="%s"',
			esc_attr( $mode ),
			$allow_rehide ? '1' : '0',
			esc_attr( (string) $blur ),
			$remember ? '1' : '0',
			esc_attr( $button_label ),
			esc_attr( $sub_label )
		);

		$wrapper_attrs = get_block_wrapper_attributes(
			array(
				'class'              => 'matture-gate matture-gate--' . esc_attr( $mode ),
				'aria-expanded'      => 'false',
				'data-matture-state' => 'hidden',
			)
		);

		// Build overlay HTML (filterable).
		$overlay_html = self::build_overlay_html( $mode, $warning_label, $button_label, $sub_label, $show_icon, $defaults );
		$overlay_html = apply_filters( 'matture_overlay_html', $overlay_html, $attributes );

		ob_start();
		do_action( 'matture_before_overlay', $attributes );
		echo $overlay_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $overlay_html is either built by build_overlay_html() (all values esc_html/esc_attr'd) or replaced by the matture_overlay_html developer filter. This is an intentional extensibility point for trusted third-party code, not user input.
		do_action( 'matture_after_overlay', $attributes );
		$overlay_output = (string) ob_get_clean();

		ob_start();
		do_action( 'matture_before_content', $attributes );
		echo '<div class="matture-gate__content" aria-hidden="true">' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $content is the inner blocks HTML produced by WordPress's own block renderer, which is responsible for escaping its own output. Direct re-escaping would corrupt HTML markup.
		do_action( 'matture_after_content', $attributes );
		$content_output = (string) ob_get_clean();

		return sprintf(
			'<div %s %s>%s%s</div>',
			$wrapper_attrs,
			$data_attrs,
			$overlay_output,
			$content_output
		);
	}

	/**
	 * Build the overlay HTML for the gate.
	 *
	 * @param string $mode          Gate mode slug.
	 * @param string $warning_label Warning label text.
	 * @param string $button_label  Reveal button label.
	 * @param string $sub_label     Optional sub-label text shown below the button.
	 * @param bool   $show_icon     Whether to render a decorative icon element.
	 * @param array  $defaults      Mode defaults array, used as fallback for empty labels.
	 * @return string Overlay HTML string.
	 */
	private static function build_overlay_html(
		string $mode,
		string $warning_label,
		string $button_label,
		string $sub_label,
		bool $show_icon,
		array $defaults
	): string {
		$supported_modes = apply_filters( 'matture_modes', self::get_supported_modes() );
		$safe_mode       = in_array( $mode, $supported_modes, true ) ? $mode : 'mature';

		// Fallback to defaults if labels are still empty (defensive).
		if ( empty( $warning_label ) ) {
			$warning_label = $defaults[ $mode ]['label'] ?? '';
		}
		if ( empty( $button_label ) ) {
			$button_label = $defaults[ $mode ]['button'] ?? 'Reveal';
		}

		$icon_html = '';
		if ( $show_icon ) {
			$icon_svg  = self::get_mode_icon( $safe_mode );
			$icon_html = '<span class="matture-gate__icon" aria-hidden="true">' . $icon_svg . '</span>';
		}

		$sub_label_html = '';
		if ( ! empty( $sub_label ) ) {
			$sub_label_html = '<span class="matture-gate__sub-label">' . esc_html( $sub_label ) . '</span>';
		}

		$warning_html = sprintf(
			'<div class="matture-gate__warning">%s<span class="matture-gate__label">%s</span><button class="matture-gate__reveal-btn" type="button">%s</button>%s</div>',
			$icon_html,
			esc_html( $warning_label ),
			esc_html( $button_label ),
			$sub_label_html
		);

		return sprintf(
			'<div class="matture-gate__overlay matture-gate__overlay--%s" aria-label="%s">%s</div>',
			esc_attr( $safe_mode ),
			esc_attr( $warning_label ),
			$warning_html
		);
	}

	/**
	 * Get the default warning label and button text for each mode.
	 *
	 * @return array Mode defaults keyed by mode slug, each with 'label' and 'button' keys.
	 */
	private static function get_mode_defaults(): array {
		return array(
			'nsfw'    => array(
				'label'  => 'NSFW Content — Tap to reveal',
				'button' => 'Reveal',
			),
			'mature'  => array(
				'label'  => "Mature Content — Click to confirm you're 18+",
				'button' => 'I confirm I am 18+',
			),
			'spoiler' => array(
				'label'  => 'Spoiler — Click to reveal',
				'button' => 'Show Spoiler',
			),
			'trigger' => array(
				'label'  => 'Trigger Warning — Click to continue',
				'button' => 'Continue',
			),
		);
	}

	/**
	 * Get the list of supported gate modes.
	 *
	 * @return array List of supported mode slugs.
	 */
	private static function get_supported_modes(): array {
		return array( 'nsfw', 'mature', 'spoiler', 'trigger' );
	}

	/**
	 * Get the SVG icon markup for a given mode.
	 *
	 * @param string $mode Gate mode slug.
	 * @return string SVG markup or empty string if mode has no icon.
	 */
	private static function get_mode_icon( string $mode ): string {
		$icons = array(
			'mature'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
			'nsfw'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>',
			'spoiler' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
			'trigger' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
		);

		return $icons[ $mode ] ?? '';
	}
}
