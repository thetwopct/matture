<?php
/**
 * Helpers
 *
 * @package WordPress
 * @subpackage {{textdomain}}
 * @since 1.4.0
 */

namespace YMMVPL;

// Allow being loaded as a Composer/VCS add-on without requiring WordPress bootstrap at include time.

/**
 * Helpers
 */
class YMMVPL_Helpers {
	/**
	 * Is Dev Domain
	 *
	 * @return boolean
	 */
	public static function is_dev_domain() {
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( 'localhost' === $domain ) {
			return true;
		}

		if ( is_string( $domain ) && function_exists( 'str_ends_with' ) && str_ends_with( $domain, 'lndo.site' ) ) {
			return true;
		}

		// Ensure $domain is a string before using pathinfo.
		if ( is_string( $domain ) && ! empty( $domain ) ) {
			$tld = pathinfo( $domain, PATHINFO_EXTENSION );
		} else {
			// Handle invalid domain case.
			$tld = '';
		}
		$allowed_dev_tlds = apply_filters( 'custom_dev_tlds', array( 'test', 'local' ) );
		return in_array( $tld, $allowed_dev_tlds, true );
	}

	/**
	 * Get license key
	 *
	 * @return string
	 * @since 1.0
	 */
	public static function get_license_key() {
        $license_key = get_option( YMMVPL_LICENSE_KEY );
		if ( ! is_string( $license_key ) ) {
			$license_key = '';
		}
		return trim( $license_key );
	}

	/**
	 * Validate license key format
	 *
	 * @param string $license_key License key to validate.
	 * @return bool True if valid format, false otherwise.
	 * @since 1.4.0
	 */
	public static function validate_license_key_format( $license_key ) {
		if ( ! is_string( $license_key ) ) {
			return false;
		}

		$license_key = trim( $license_key );

		// Check length - reasonable license key should be between 10-100 characters
		if ( strlen( $license_key ) < 10 || strlen( $license_key ) > 100 ) {
			return false;
		}

		// Check for valid characters only (alphanumeric, hyphens, underscores)
		if ( ! preg_match( '/^[a-zA-Z0-9\-_]+$/', $license_key ) ) {
			return false;
		}

		// Reject obvious invalid patterns
		$invalid_patterns = array(
			'test',
			'demo',
			'invalid',
			'null',
			'undefined',
			'fake',
			'example',
		);

		$lower_key = strtolower( $license_key );
		foreach ( $invalid_patterns as $pattern ) {
			if ( strpos( $lower_key, $pattern ) !== false ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Retrieve license data from the PaddlePress API.
	 *
	 * @param bool $force_update   Optional. Bypass the cached value. Default false.
	 * @param bool $use_dev_domain Optional. Use the development domain when querying. Default false.
	 * @return array<string, mixed>
	 */
    public static function get_license_data( $force_update = false, $use_dev_domain = false ) {
            $transient    = $use_dev_domain ? YMMVPL_LICENSE_INFO_TRANSIENT : YMMVPL_LICENSE_DATA_TRANSIENT;
			$license_data = get_transient( $transient );
			$license_key  = self::get_license_key();

            $api_url = trailingslashit( apply_filters( 'paddlepress_license_api_url', YMMVPL_LICENSE_ENDPOINT ) );

		if ( $force_update || ( false === $license_data && $license_key ) ) {
				$api_params = array(
					'action'      => 'info',
					'license_key' => $license_key,
                    'license_url' => $use_dev_domain ? YMMVPL_DEV_INFO_DOMAIN : home_url(),
				);

				$response = wp_remote_post(
					$api_url,
					array(
						'timeout'   => 15,
						'sslverify' => self::verify_ssl(),
						'body'      => $api_params,
					)
				);

				$license_data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_array( $license_data ) && ! empty( $license_data ) ) {
                            set_transient( $transient, $license_data, HOUR_IN_SECONDS * 12 );
				return $license_data;
			} else {
				$license_data = array(
					'success'        => false,
					'license_status' => 'unknown',
				);
                set_transient( $transient, $license_data, MINUTE_IN_SECONDS * 30 );
			}
		}
		return is_array( $license_data ) ? $license_data : array();
	}

	/**
	 * Return user-readable feedback message based on the API response of license check.
	 *
	 * @param array<string, mixed>|null $license_data License data. Default null triggers an API request.
	 * @param bool                      $use_dev_domain Whether to fetch info using the development domain.
	 * @return string
	 */
	public static function get_license_status_message( $license_data = null, $use_dev_domain = false ) {
			$message = '';

		if ( null === $license_data ) {
				$license_data = self::get_license_data( false, $use_dev_domain );
		}

		// Ensure $license_data is an array before accessing its keys.
		if ( ! empty( $license_data ) && is_array( $license_data ) ) {
			// Ensure the date format is a string.
			$date_format = get_option( 'date_format' );
			if ( ! is_string( $date_format ) ) {
				$date_format = 'Y-m-d';
			}

			if ( isset( $license_data['license_status'] ) && 'valid' === $license_data['license_status'] ) {
				$message = esc_html__( 'Your license is valid and activated. ', '{{textdomain}}' );

				if ( isset( $license_data['expires'] ) && 'lifetime' !== $license_data['expires'] ) {
					$expiration_date = strtotime( $license_data['expires'] );
					if ( $expiration_date < time() ) {
						$message .= ' ' . esc_html__( 'Your license has expired. ', '{{textdomain}}' );
					} elseif ( $expiration_date < strtotime( '+30 days' ) ) {
						$message .= ' ' . esc_html__( 'Your license will expire soon. ', '{{textdomain}}' );
					} else {
						$message .= sprintf(
							/* translators: %s: Date. */
							esc_html__( 'Your license expires on %s. ', '{{textdomain}}' ),
							date_i18n( $date_format, $expiration_date )
						);
					}
				}

				if ( isset( $license_data['expires'] ) && 'lifetime' === $license_data['expires'] ) {
					$message .= esc_html__( 'Lifetime License. ', '{{textdomain}}' );
				}

				if ( isset( $license_data['site_count'] ) && isset( $license_data['license_limit'] ) ) {
					$message .= sprintf(
						/* translators: %s: Number of sites. */
						esc_html__( 'You have %1$s/%2$s sites activated. ', '{{textdomain}}' ),
						absint( $license_data['site_count'] ),
						absint( $license_data['license_limit'] )
					);
				}
			}

			if ( isset( $license_data['errors'] ) && ! empty( $license_data['errors'] ) && is_array( $license_data['errors'] ) ) {
				$error_keys     = array_keys( $license_data['errors'] );
				$error_messages = array();

				foreach ( $error_keys as $err_code ) {
					switch ( $err_code ) {
						case 'missing_license_key':
							// Don't print anything as missing key is the default state.
							break;

						case 'expired_license_key':
							$error_messages[] = sprintf(
								/* translators: %s: A date. */
								esc_html__( 'Your license key expired on %s.', '{{textdomain}}' ),
								date_i18n( $date_format, strtotime( $license_data['expires'], current_time( 'timestamp' ) ) ) // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
							);
							break;
						case 'unregistered_license_domain':
							$error_messages[] = esc_html__( 'Unregistered domain address.', '{{textdomain}}' );
							break;
						case 'invalid_product':
							$error_messages[] = esc_html__( 'Invalid product for this license key.', '{{textdomain}}' );
							break;
						case 'invalid_license_or_domain':
							$error_messages[] = esc_html__( 'Invalid license or url.', '{{textdomain}}' );
							break;
						case 'can_not_add_new_domain':
							$error_messages[] = esc_html__( 'Can not add a new domain.', '{{textdomain}}' );
							break;
						default:
							// Get the actual error message from the API response if available.
							if ( isset( $license_data['errors'][ $err_code ] ) && is_array( $license_data['errors'][ $err_code ] ) && isset( $license_data['errors'][ $err_code ][0] ) && is_string( $license_data['errors'][ $err_code ][0] ) ) {
								$api_message = esc_html( $license_data['errors'][ $err_code ][0] );
								// Ensure the message ends with a period.
								if ( ! empty( $api_message ) && ! in_array( substr( $api_message, -1 ), array( '.', '!', '?' ), true ) ) {
									$api_message .= '.';
								}
								$error_messages[] = $api_message;
							} else {
								$error_messages[] = esc_html__( 'An error occurred, please try again.', '{{textdomain}}' );
							}
							break;
					}
				}

				// Combine all error messages.
				if ( ! empty( $error_messages ) ) {
					$message = implode( ' ', $error_messages );
				}
			}

			if ( ( isset( $license_data['license_status'] ) && 'unknown' === $license_data['license_status'] ) ) {
				$message = esc_html__( 'Please enter a valid license key and activate it.', '{{textdomain}}' );
			}
		}
		return $message;
	}

	/**
	 * Returns if the SSL should be verified.
	 *
	 * @return bool
	 * @since  1.4
	 */
	public static function verify_ssl() {
		return (bool) apply_filters( 'paddlepress_api_request_verify_ssl', true );
	}

	/**
	 * Build Request Params
	 *
	 * @param string|null $license_key License Key.
	 * @param string      $action Action.
	 * @return array<string, mixed>
	 */
	public static function build_request_params( $license_key, $action ) {
		return array(
			'action'       => $action,
			'license_key'  => $license_key,
			'license_url'  => home_url(),
			'download_tag' => YMMVPL_PLUGIN_SLUG,
		);
	}

	/**
	 * Send the API request
	 *
	 * @param array<string, mixed> $params Params.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function send_api_request( $params ) {
		$endpoint = trailingslashit( apply_filters( 'paddlepress_license_api_url', YMMVPL_LICENSE_ENDPOINT ) );
		$response = wp_remote_post(
			$endpoint,
			array(
				'body'      => $params,
				'timeout'   => 15,
				'sslverify' => self::verify_ssl(),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return new \WP_Error( 'api_error', wp_remote_retrieve_response_message( $response ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return new \WP_Error( 'invalid_response', 'Invalid response from license server' );
		}

		return $body;
	}

	/**
	 * Get Active Tab
	 *
	 * @return string
	 */
	public static function get_active_tab() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'settings';
	}

    /**
     * Render the license settings UI for host plugins to embed.
     *
     * This outputs a minimal, vendor-neutral license UI so host products can place it in their own settings screens.
     *
     * @return void
     */
    public static function display_license_settings(): void {

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
        <p>Please enter your license key to activate the product and enable automatic updates.</p>
        <table class="form-table">
        <tr>
			<th scope="row"><label for="{{key_prefix}}_license_key"><?php esc_html_e( 'License Key', '{{textdomain}}' ); ?></label></th>
            <td>
				<input <?php echo esc_attr( $readonly ); ?> required type="text" id="{{key_prefix}}_license_key" size="40" name="{{key_prefix}}_license_key" value="<?php echo esc_attr( $license_key ); ?>" />
				<?php wp_nonce_field( 'ymmvpl_activate_license_action', 'ymmvpl_activate_license_nonce' ); ?>
				<button type="submit" id="{{key_prefix}}-activate-license" class="button button-secondary" name="{{key_prefix}}_activate_license_submit_action" value="<?php echo esc_attr( $action ); ?>">
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
		<p>If you've previously purchased Tax Toggle on CodeCanyon/Envato you convert your CodeCanyon/Envato Purchase Code to a Tax Toggle license. This will let you register the plugin and get automatic updates.</p>
		<p><a href="https://ymmv.co/onboard/?invite=taxtoggle" target="_blank">Convert your Purchase Code to a License</a>.</p>
        <?php
    }

	/**
	 * Handle License Submission
	 *
	 * @return void
	 */
	public static function handle_license_submission(): void {
		if ( ! isset( $_POST['{{key_prefix}}_activate_license_submit_action'] ) ||
		! check_admin_referer( '{{key_prefix}}_activate_license_action', '{{key_prefix}}_activate_license_nonce' ) ) {
			self::display_notice( 'error', esc_html__( 'License comms failed. Please refresh the page and try again.', '{{textdomain}}' ) );
			return;
		}

		$license_key = isset( $_POST['{{key_prefix}}_license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['{{key_prefix}}_license_key'] ) ) : null;
		$action      = isset( $_POST['{{key_prefix}}_activate_license_submit_action'] ) ? sanitize_text_field( wp_unslash( $_POST['{{key_prefix}}_activate_license_submit_action'] ) ) : null;

		if ( ! $license_key || ! $action ) {
			self::display_notice( 'error', esc_html__( 'Licence key must be provided. Please try again.', '{{textdomain}}' ) );
			return;
		}

		// SECURITY ENHANCEMENT: Validate license key format
		if ( ! self::validate_license_key_format( $license_key ) ) {
			self::display_notice( 'error', esc_html__( 'Invalid license key format. Please check your license key and try again.', '{{textdomain}}' ) );
			return;
		}

		$params   = self::build_request_params( $license_key, strtolower( $action ) );
		$response = self::send_api_request( $params );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			self::display_notice(
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
						self::display_notice( 'success', esc_html__( 'Domain already deactivated.', '{{textdomain}}' ) );
			} elseif ( is_array( $response ) && isset( $response['errors'] ) && ! empty( $response['errors'] ) ) {
						$message = YMMVPL_Helpers::get_license_status_message( $response );
							self::display_notice( 'error', $message );
							return;
			} else {
				self::display_notice( 'success', esc_html__( 'License deactivated successfully.', '{{textdomain}}' ) );
			}

			delete_option( YMMVPL_LICENSE_KEY );
			delete_transient( YMMVPL_LICENSE_DATA_TRANSIENT );
			delete_transient( YMMVPL_LICENSE_INFO_TRANSIENT );
			return;
		}

		if ( isset( $response['errors'] ) && ! empty( $response['errors'] ) ) {
				$message = YMMVPL_Helpers::get_license_status_message( $response );
				self::display_notice( 'error', $message );
				return;
		}

		if ( isset( $response['license_status'] ) && 'valid' === $response['license_status'] ) {
				update_option( YMMVPL_LICENSE_KEY, $license_key );
				set_transient( YMMVPL_LICENSE_DATA_TRANSIENT, $response, DAY_IN_SECONDS );
				delete_transient( YMMVPL_LICENSE_INFO_TRANSIENT );
				self::display_notice( 'success', esc_html__( 'License activated successfully.', '{{textdomain}}' ) );
		} else {
			self::display_notice( 'error', esc_html__( 'Invalid license response. Please try again.', '{{textdomain}}' ) );
		}
	}

	/**
	 * Display Notice
	 *
	 * @param string $type Type of notice.
	 * @param string $message Message to display.
	 * @return void
	 */
	public static function display_notice( $type, $message ): void {
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
			<p><?php echo wp_kses_post( $message ); ?></p>
		</div>
		<?php
	}
}
