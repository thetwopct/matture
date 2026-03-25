<?php
/**
 * PluginName
 *
 * @package 		plugin-name
 * @author          YMMV LLC
 * @copyright       2025 YMMV LLC
 * @license         GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:             PluginName
 * Description:             TODO
 * Version:                 0.0.1
 * Author:                  YMMV LLC
 * Author URI:              https://www.ymmv.co
 * Text Domain: 			plugin-name
 * Domain Path:             /languages
 * Requires PHP:            7.4
 * Requires at least:       6.0
 * Tested up to:            6.8
 * WC requires at least:    7.0.0
 * WC tested up to:         10.2
 * Requires Plugins:        woocommerce
 * License:                 GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( '{{key_prefix}}_PLUGIN_FILE', __FILE__ );
define( '{{key_prefix}}_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( '{{key_prefix}}_PLUGIN_SLUG', '{{textdomain}}' );
define( '{{key_prefix}}_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( '{{key_prefix}}_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( '{{key_prefix}}_INCLUDES_PATH', plugin_dir_path( __FILE__ ) . 'includes/' );
define( '{{key_prefix}}_VERSION_NUM', '1.0.0' );

// Include core classes.
require_once YMMVPL_INCLUDES_PATH . 'constants.php';
require_once YMMVPL_INCLUDES_PATH . 'class-ymmvpl-init.php';

\YMMVPL\YMMVPL_Init::init();