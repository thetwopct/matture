<?php
/**
 * Matture - Main initializer
 *
 * @package WordPress
 * @subpackage matture
 */

namespace Matture;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin init class.
 */
class Matture_Init {

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		$instance = new self();
		add_action( 'init', array( $instance, 'register_blocks' ) );
		add_action( 'init', array( 'Matture\Matture_Hooks', 'init' ) );
		add_action( 'rest_api_init', array( 'Matture\Matture_Rest', 'register_routes' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

		// Stub hook for OpenClaw Abilities API and future AI integrations.
		do_action( 'matture_register_abilities' );
	}

	/**
	 * Register all blocks.
	 *
	 * Registers the block type and wires up script translations for the editor
	 * script so that JS strings translated via wp i18n make-json are loaded.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		register_block_type(
			MATTURE_PLUGIN_PATH . 'blocks/content-gate',
			array(
				'render_callback' => array( 'Matture\Matture_Hooks', 'render_block' ),
			)
		);

		// Load JS translations for the editor block script.
		// Requires JSON files generated via: composer run-script make-json.
		wp_set_script_translations( 'matture-content-gate-editor-script', 'matture', MATTURE_PLUGIN_PATH . 'languages' );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'matture', false, plugin_basename( dirname( MATTURE_PLUGIN_FILE ) ) . '/languages' );
	}
}
