<?php
/**
 * Tax Toggle for WooCommerce - Main initializer
 *
 * @package WordPress
 * @subpackage {{textdomain}}
 * @since 1.4.0
 */

namespace YMMVPL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Lets Go
 */
class YMMVPL_Init {

	/**
	 * Initialize the plugin by setting up all necessary classes and hooks.
	 *
	 * @return void
	 */
    public static function init() {
		self::register_initial_hooks();
        self::includes();
		self::initialize_classes();
		self::register_hooks();
    }

	/**
	 * Include all required files.
	 *
	 * @return void
	 */
	private static function includes() {
		require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-helpers.php';
		require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-notices.php';
		require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-updater-init.php';
		require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-updater.php';
		require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-admin-settings.php';
	}

	/**
	 * Initialize required classes.
	 *
	 * @return void
	 */
	private static function initialize_classes() {
		new YMMVPL_Admin_Settings();
	}

	/**
	 * Register initial hooks and actions.
	 *
	 * @return void
	 */
	private static function register_initial_hooks() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
	}

	/**
	 * Register hooks and actions.
	 *
	 * @return void
	 */
	private static function register_hooks() {
		add_action( 'init', array( '\{{namespace}}\{{class_prefix}}_Updater_Init', 'init' ) );
		add_action( 'wp_ajax_{{textdomain}}_dismiss_license_notice', array( '\{{namespace}}\{{class_prefix}}_Notices', 'dismiss_license_key_notice' ) );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( '{{textdomain}}', false, plugin_basename( dirname( YMMVPL_PLUGIN_FILE ) ) . '/languages' );
	}
}
