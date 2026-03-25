<?php
/**
 * Matture
 *
 * @package         matture
 * @author          James Hunt
 * @copyright       2026 James Hunt
 * @license         GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:             Matture
 * Description:             Gate any content behind a tap-to-reveal overlay. Four modes: NSFW, Mature, Spoiler, and Trigger Warning.
 * Version:                 1.0.0
 * Author:                  James Hunt
 * Author URI:              https://thetwopercent.co.uk
 * Text Domain:             matture
 * Domain Path:             /languages
 * Requires PHP:            8.0
 * Requires at least:       6.6
 * Tested up to:            6.8
 * License:                 GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MATTURE_PLUGIN_FILE', __FILE__ );
define( 'MATTURE_PLUGIN_BASENAME', plugin_basename( MATTURE_PLUGIN_FILE ) );
define( 'MATTURE_PLUGIN_SLUG', 'matture' );
define( 'MATTURE_PLUGIN_PATH', plugin_dir_path( MATTURE_PLUGIN_FILE ) );
define( 'MATTURE_PLUGIN_URL', plugin_dir_url( MATTURE_PLUGIN_FILE ) );
define( 'MATTURE_INCLUDES_PATH', plugin_dir_path( MATTURE_PLUGIN_FILE ) . 'includes/' );
define( 'MATTURE_VERSION_NUM', '1.0.0' );

// Include core classes.
require_once MATTURE_INCLUDES_PATH . 'class-matture-hooks.php';
require_once MATTURE_INCLUDES_PATH . 'class-matture-rest.php';
require_once MATTURE_INCLUDES_PATH . 'class-matture-init.php';

\Matture\Matture_Init::init();
