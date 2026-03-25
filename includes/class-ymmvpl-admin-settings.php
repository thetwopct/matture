<?php
/**
 * {{plugin_name}} Admin Settings
 *
 * @package WordPress
 * @subpackage {{textdomain}}
 * @since   1.4.0
 */

namespace YMMVPL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * {{plugin_name}} Admin Settings
 */
class YMMVPL_Admin_Settings {
	/**
	 * Construct
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		if ( defined( 'YMMVPL_PLUGIN_BASENAME' ) ) {
			add_filter( 'plugin_action_links_' . YMMVPL_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
		}
	}

	/**
	 * Register Settings Page
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_options_page(
			esc_html__( '{{plugin_name}} Settings', '{{textdomain}}' ),
			esc_html__( '{{plugin_name}}', '{{textdomain}}' ),
			'manage_options',
			'{{plugin_slug}}-settings',
			array( $this, 'settings_page_content' )
		);
	}

	/**
	 * Register Settings
	 *
	 * @return void
	 */
	public function register_settings() {
		$settings = array();

		foreach ( $settings as $setting ) {
			register_setting( '{{key_prefix}}_group', $setting, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		}
	}

	/**
	 * Settings Page Content
	 *
	 * @return void
	 */
	public function settings_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['{{key_prefix}}_activate_license_submit_action'] ) && check_admin_referer( '{{key_prefix}}_activate_license_action', '{{key_prefix}}_activate_license_nonce' ) ) {
			$this->handle_license_submission();
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<!-- Tab Navigation -->
			<h2 class="nav-tab-wrapper">
				<a href="?page={{plugin_slug}}-settings&tab=settings" class="nav-tab <?php echo 'settings' === $this->get_active_tab() ? 'nav-tab-active' : ''; ?>">Settings</a>
				<a href="?page={{plugin_slug}}-settings&tab=license" class="nav-tab <?php echo 'license' === $this->get_active_tab() ? 'nav-tab-active' : ''; ?>">License</a>
			</h2>
			<form method="post" action="<?php echo 'license' === $this->get_active_tab() ? '' : 'options.php'; ?>">
				<?php
				$current_tab = $this->get_active_tab();
				settings_fields( '{{key_prefix}}_group' );

				if ( 'settings' === $current_tab ) {
					$this->display_settings();
					submit_button();
				} elseif ( 'license' === $current_tab ) {
					$this->display_license_settings();
				}
				?>
				</form>
		</div>
		<?php
	}

	/**
	 * Display Settings
	 *
	 * @return void
	 */
	private function display_settings() {
		?>
        <table class="form-table" role="presentation">
            <tr valign="top">
                <th scope="row"></th>
                <td></td>
            </tr>
        </table>
        <?php
	}

	/**
	 * Display License Settings
	 *
	 * @return void
	 */
	private function display_license_settings() {

		$license_key  = YMMVPL_Helpers::get_license_key();
		$license_data = YMMVPL_Helpers::get_license_data();
		$license_info = YMMVPL_Helpers::get_license_data( false, true );
		$is_active    = ! empty( $license_data ) && is_array( $license_data ) && isset( $license_data['license_status'] ) && 'valid' === $license_data['license_status'];
		$action       = $is_active ? 'Deactivate' : 'Activate';
		$readonly     = '';

		if ( ! is_string( $license_key ) ) {
			$readonly    = 'readonly="readonly"';
			$license_key = '';
		} elseif ( $is_active ) {
			$readonly = 'readonly="readonly"';
		}
				$license_status_message = YMMVPL_Helpers::get_license_status_message( $license_info, true );
		if ( ! is_string( $license_status_message ) ) {
			$license_status_message = '';
		}
		?>
		<h2>License Settings</h2>
		<p>Please enter your license key to activate the plugin and get automatic security and feature updates.</p>
		<table class="form-table">
		<tr>
			<th scope="row"><label for="{{key_prefix}}_license_key"><?php esc_html_e( 'License Key', '{{textdomain}}' ); ?></label></th>
			<td>
				<input <?php echo esc_attr( $readonly ); ?> required type="text" id="{{key_prefix}}_license_key" size="40" name="{{key_prefix}}_license_key" value="<?php echo esc_attr( $license_key ); ?>" />
				<?php wp_nonce_field( '{{key_prefix}}_activate_license_action', '{{key_prefix}}_activate_license_nonce' ); ?>
				<button type="submit" id="activate-license" class="button button-secondary" name="{{key_prefix}}_activate_license_submit_action" value="<?php echo esc_attr( $action ); ?>">
					<?php echo esc_html( $action ); ?> License
				</button>
				<p><strong><?php echo esc_html__( 'Status:', '{{textdomain}}' ); ?></strong> <?php echo esc_html( $is_active ? __( 'Active', '{{textdomain}}' ) : __( 'Inactive', '{{textdomain}}' ) ); ?></p>
				<p class="description">
						<?php
						if ( $is_active ) {
								echo esc_html__( 'Click Deactivate License to unregister this website from your account.', '{{textdomain}}' );
						} else {
								echo esc_html__( 'Enter your license key and click Activate License to register this website to your account.', '{{textdomain}}' );
						}
						?>
				</p>
				<p class="description"><?php echo wp_kses_post( $license_status_message ); ?></p>
				<?php
				if ( $license_data && YMMVPL_Helpers::is_dev_domain() ) {
					?>
					<p class="description">This domain is classed as a development domain so does not count towards your domains allowance.</p>
					<?php
				}
				?>
			</td>
		</tr>
	</table>

		<hr style="margin-top: 40px; margin-bottom: 40px">
		<h2>Purchased on CodeCanyon/Envato?</h2>
		<p>If you've previously purchased {{plugin_name}} on CodeCanyon/Envato you convert your CodeCanyon/Envato Purchase Code to a {{plugin_name}} license. This will let you register the plugin and get automatic updates.</p>
		<p><a href="https://ymmv.co/onboard/?invite={{plugin_slug}}" target="_blank">Convert your Purchase Code to a License</a>.</p>
		<?php
	}

	/**
	 * Add Action Links
	 *
	 * @param array<string, string> $links Links.
	 * @return array<int|string, string> Links.
	 */
	public function add_action_links( array $links ): array {
		$custom_links = array(
			'<a href="' . admin_url( 'options-general.php?page={{plugin_slug}}-settings' ) . '">Settings</a>',
			'<a href="https://ymmv.co/support/" target="_blank">Support</a>',
		);
		return array_merge( $custom_links, $links );
	}

	/**
	 * Get Active Tab
	 *
	 * @return string
	 */
	private function get_active_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
	}

	/**
	 * Handle License Submission
	 *
	 * @return void
	 */
	private function handle_license_submission(): void {
		if ( ! isset( $_POST['{{key_prefix}}_activate_license_submit_action'] ) ||
		! check_admin_referer( '{{key_prefix}}_activate_license_action', '{{key_prefix}}_activate_license_nonce' ) ) {
			$this->display_notice( 'error', esc_html__( 'License comms failed. Please refresh the page and try again.', '{{textdomain}}' ) );
			return;
		}

		$license_key = isset( $_POST['{{key_prefix}}_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['{{key_prefix}}_license_key'] ) ) : null;
		$action      = isset( $_POST['{{key_prefix}}_activate_license_submit_action'] ) ? sanitize_text_field( wp_unslash( $_POST['{{key_prefix}}_activate_license_submit_action'] ) ) : null;

		if ( ! $license_key || ! $action ) {
			$this->display_notice( 'error', esc_html__( 'Licence key must be provided. Please try again.', '{{textdomain}}' ) );
			return;
		}

		// SECURITY ENHANCEMENT: Validate license key format
		if ( ! YMMVPL_Helpers::validate_license_key_format( $license_key ) ) {
			$this->display_notice( 'error', esc_html__( 'Invalid license key format. Please check your license key and try again.', '{{textdomain}}' ) );
			return;
		}

		$params   = YMMVPL_Helpers::build_request_params( $license_key, strtolower( $action ) );
		$response = YMMVPL_Helpers::send_api_request( $params );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->display_notice(
				'error',
				sprintf(
					/* translators: %s: Error message from the API response */
					esc_html__( 'License API request failed: %s', '{{textdomain}}' ),
					esc_html( $error_message )
				)
			);
			return;
		}

		if ( 'Deactivate' === $action ) {
			if ( is_array( $response ) && isset( $response['errors']['unregistered_license_domain'] ) ) {
						$this->display_notice( 'success', esc_html__( 'Domain already deactivated.', '{{textdomain}}' ) );
			} elseif ( is_array( $response ) && isset( $response['errors'] ) && ! empty( $response['errors'] ) ) {
							$message = YMMVPL_Helpers::get_license_status_message( $response );
							$this->display_notice( 'error', $message );
							return;
			} else {
				$this->display_notice( 'success', esc_html__( 'License deactivated successfully.', '{{textdomain}}' ) );
			}

			delete_option( YMMVPL_LICENSE_KEY );
			delete_transient( YMMVPL_LICENSE_DATA_TRANSIENT );
			delete_transient( YMMVPL_LICENSE_INFO_TRANSIENT );
			return;
		}

		if ( isset( $response['errors'] ) && ! empty( $response['errors'] ) ) {
				$message = YMMVPL_Helpers::get_license_status_message( $response );
				$this->display_notice( 'error', $message );
				return;
		}

		if ( isset( $response['license_status'] ) && 'valid' === $response['license_status'] ) {
				update_option( YMMVPL_LICENSE_KEY, $license_key );
				set_transient( YMMVPL_LICENSE_DATA_TRANSIENT, $response, DAY_IN_SECONDS );
				delete_transient( YMMVPL_LICENSE_INFO_TRANSIENT );
				$this->display_notice( 'success', esc_html__( 'License activated successfully.', '{{textdomain}}' ) );
		} else {
			$this->display_notice( 'error', esc_html__( 'Invalid license response. Please try again.', '{{textdomain}}' ) );
		}
	}

	/**
	 * Display Notice
	 *
	 * @param string $type Type of notice.
	 * @param string $message Message to display.
	 * @return void
	 */
	private function display_notice( $type, $message ): void {
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}
}