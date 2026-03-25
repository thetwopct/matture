<?php
/**
 * Tax Toggle for WooCommerce - Notices
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
 * Tax Toggle Notices
 */
class YMMVPL_Notices {
	/**
	 * Set Required Admin Notices
	 *
	 * @return void
	 */
	public static function admin_notices() {
		// Check if license key notice should be shown.
		if ( get_transient( '{{textdomain}}_show_license_key_notice' ) ) {
			// Only show if not dismissed or if the dismiss period has expired.
			if ( ! get_transient( '{{textdomain}}_license_key_notice_dismissed' ) ) {
                include YMMVPL_PLUGIN_PATH . 'views/html-notice-requirement-license-key.php';
			}
			delete_transient( '{{textdomain}}_show_license_key_notice' );
		}
	}

	/**
	 * Handle AJAX request to dismiss license key notice
	 *
	 * @return void
	 */
	public static function dismiss_license_key_notice() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), '{{textdomain}}_dismiss_license_notice' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', '{{textdomain}}' ) ) );
			return;
		}

		// Verify user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', '{{textdomain}}' ) ) );
			return;
		}

		// Set transient for 7 days (604800 seconds).
		$expiration = 7 * DAY_IN_SECONDS;
		set_transient( '{{textdomain}}_license_key_notice_dismissed', true, $expiration );

		wp_send_json_success( array( 'message' => __( 'Notice dismissed for 7 days.', '{{textdomain}}' ) ) );
	}
}
