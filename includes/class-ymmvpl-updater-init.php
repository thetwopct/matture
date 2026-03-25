<?php
/**
 * Tax Toggle for WooCommerce Updater Init
 *
 * @package WordPress
 * @subpackage {{textdomain}}
 * @since   1.4.0
 */

namespace YMMVPL;

// Allow loading as an add-on package without requiring WordPress bootstrap at include time.

/**
 * Updater Initialize
 */
class YMMVPL_Updater_Init {

	/**
	 * Initialize the updater.
	 *
	 * @return void
	 */
	public static function init() {
		// Ensure this is only done for privileged users or during cron jobs.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

        $license_key = YMMVPL_Helpers::get_license_key();

		if ( empty( $license_key ) && ! get_transient( '{{textdomain}}_license_key_notice_dismissed' ) ) {
			set_transient( '{{textdomain}}_show_license_key_notice', true, 3600 );
		}

        $endpoint = trailingslashit( apply_filters( 'paddlepress_updater_api_url', YMMVPL_UPDATE_ENDPOINT ) );

        $updater = new YMMVPL_Updater(
			$endpoint,
            YMMVPL_PLUGIN_FILE,
			array(
                'version'      => YMMVPL_VERSION_NUM,
				'license_key'  => $license_key,
				'license_url'  => home_url(),
                'download_tag' => YMMVPL_PLUGIN_SLUG,
			)
		);
	}
}
