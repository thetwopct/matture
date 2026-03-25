<?php
/**
 * Tax Toggle for WooCommerce Updater
 *
 * @package WordPress
 * @subpackage {{textdomain}}
 * @since   1.4.0
 */

namespace YMMVPL;

// phpcs:disable WordPress.Security.NonceVerification.Recommended

/**
 * Updater for Tax Toggle
 */
class YMMVPL_Updater {
	/**
	 * Update endpoint of the API
	 *
	 * @var string $api_url
	 */
	private $api_url = '';

	/**
	 * HTTP parameters on API requests
	 *
	 * @var array $api_data
	 */
	private $api_data = array(); // @phpstan-ignore missingType.iterableValue

	/**
	 * Plugin Name
	 *
	 * @var string $name
	 */
	private $name = '';

	/**
	 * Whether update from the beta version or not
	 *
	 * @var bool $beta
	 */
	private $beta;

	/**
	 * Slug key
	 *
	 * @var string $slug
	 */
	private $slug;

	/**
	 * Current version of plugin
	 *
	 * @var string
	 */
	private $version = '';

	/**
	 * WP Override flag
	 *
	 * @var bool $wp_override
	 */
	private $wp_override = false;

	/**
	 * Cache key
	 *
	 * @var string
	 */
	private $cache_key = '';

	/**
	 * Product slug
	 *
	 * @var string $download_tag
	 */
	private $download_tag = '';

	/**
	 * Health check timeout
	 *
	 * @var int $health_check_timeout
	 */
	private $health_check_timeout = 5;

	/**
	 * Class constructor.
	 *
	 * @param string $_api_url     The URL pointing to the custom API endpoint.
	 * @param string $_plugin_file Path to the plugin file.
	 * @param array  $_api_data    Optional data to send with API calls.
	 *
	 * @uses hook()
	 *
	 * @uses plugin_basename()
	 *
	 * @phpstan-ignore-next-line missingType.iterableValue
	 */
	public function __construct( $_api_url, $_plugin_file, array $_api_data = array() ) {
		global $paddlepress_plugin_data;

		$this->api_url      = trailingslashit( apply_filters( 'paddlepress_updater_api_url', $_api_url ) );
		$this->api_data     = $_api_data;
		$this->name         = plugin_basename( $_plugin_file );
		$this->version      = $_api_data['version'] ?? '';
		$this->download_tag = $_api_data['download_tag'] ?? '';
		$this->slug         = $this->download_tag; // the slug of download term.
		$this->wp_override  = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;
		$this->beta         = ! empty( $this->api_data['beta'] ) ? true : false;
		$this->cache_key    = 'paddlepress_' . md5( serialize( $this->slug . ( $this->api_data['license_key'] ?? '' ) . $this->beta ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		$paddlepress_plugin_data[ $this->slug ] = $this->api_data;

		/**
		 * Fires after the $paddlepress_plugin_data is setup.
		 *
		 * @param array $paddlepress_plugin_data Array of plugin data.
		 *
		 * @since 1.0
		 */
		do_action( 'post_paddlepress_plugin_updater_setup', $paddlepress_plugin_data );

		// Set up hooks.
		$this->init();
	}

	/**
	 * Set up WordPress filters to hook into WP's update process.
	 *
	 * @return void
	 * @uses add_filter()
	 */
	public function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
		remove_action( 'after_plugin_row_' . $this->name, 'wp_plugin_update_row', 10 );
		add_action( 'after_plugin_row_' . $this->name, array( $this, 'show_update_notification' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'show_changelog' ) );
	}

	/**
	 * Check for Updates at the defined API endpoint and modify the update array.
	 *
	 * This function dives into the update API just when WordPress creates its update array,
	 * then adds a custom API call and injects the custom plugin data retrieved from the API.
	 * It is reassembled from parts of the native WordPress plugin update code.
	 * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
	 *
	 * @param array<string, mixed> $_transient_data Update array build by WordPress.
	 *
	 * @return stdClass|array<string, mixed> Modified update array with custom plugin data.
	 * @uses api_request()
	 */
	public function check_update( $_transient_data ) {

		global $pagenow;

		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new stdClass();
		}

		if ( 'plugins.php' === $pagenow && is_multisite() ) {
			return $_transient_data;
		}

		if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
			return $_transient_data;
		}

		$version_info = $this->get_cached_version_info();

		if ( false === $version_info ) {
			$version_info = $this->api_request(
				'get_version',
				array(
					'download_tag' => $this->download_tag,
					'beta'         => $this->beta,
					'license_url'  => $this->api_data['license_url'] ?? '',
				)
			);

			// Since we disabled our filter for the transient, we aren't running our object conversion on banners, sections, or icons. Do this now:.
			if ( isset( $version_info->banners ) && ! is_array( $version_info->banners ) ) {
				$version_info->banners = $this->convert_object_to_array( $version_info->banners );
			}

			if ( isset( $version_info->sections ) && ! is_array( $version_info->sections ) ) {
				$version_info->sections = $this->convert_object_to_array( $version_info->sections );
			}

			if ( isset( $version_info->icons ) && ! is_array( $version_info->icons ) ) {
				$version_info->icons = $this->convert_object_to_array( $version_info->icons );
			}

			if ( isset( $version_info->contributors ) && ! is_array( $version_info->contributors ) ) {
				$version_info->contributors = $this->convert_object_to_array( $version_info->contributors );
			}

			$this->set_version_info_cache( $version_info );
		}

		if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {
			$no_update = false;
			if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {
				$_transient_data->response[ $this->name ] = $version_info;

				// Make sure the plugin property is set to the plugin's name/location.
				$_transient_data->response[ $this->name ]->plugin = $this->name;
			} else {
				$no_update              = new stdClass();
				$no_update->id          = '';
				$no_update->slug        = $this->slug;
				$no_update->plugin      = $this->name;
				$no_update->new_version = $version_info->new_version ?? '';
				$no_update->icons       = $version_info->icons ?? '';
				$no_update->banners     = $version_info->banners ?? '';
				$no_update->banners_rtl = array();

				if ( isset( $version_info->homepage ) ) {
					$no_update->url = $version_info->homepage;
				}

				if ( isset( $version_info->package ) ) {
					$no_update->package = $version_info->package;
				}
			}

			$_transient_data->last_checked           = time();
			$_transient_data->checked[ $this->name ] = $this->version;

			if ( $no_update ) {
				$_transient_data->no_update[ $this->name ] = $no_update;
			}
		}

		return $_transient_data;
	}

	/**
	 * Show update nofication row -- needed for multisite subsites, because WP won't tell you otherwise.
	 *
	 * @param string               $file   Path to the plugin file relative to the plugins directory.
	 * @param array<string, mixed> $plugin An array of plugin data.
	 * @return void
	 */
	public function show_update_notification( $file, $plugin ) {

		if ( is_network_admin() ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( ! is_multisite() ) {
			return;
		}

		if ( $this->name !== $file ) {
			return;
		}

		// Remove our filter on the site transient.
		remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 10 );

		$update_cache = get_site_transient( 'update_plugins' );

		$update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

		if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->name ] ) ) {
			$version_info = $this->get_cached_version_info();

			if ( false === $version_info ) {
				$version_info = $this->api_request(
					'get_version',
					array(
						'download_tag' => $this->download_tag,
						'beta'         => $this->beta,
						'license_url'  => $this->api_data['license_url'] ?? '',
					)
				);

				// Since we disabled our filter for the transient, we aren't running our object conversion on banners, sections, or icons. Do this now:.
				if ( isset( $version_info->banners ) && ! is_array( $version_info->banners ) ) {
					$version_info->banners = $this->convert_object_to_array( $version_info->banners );
				}

				if ( isset( $version_info->sections ) && ! is_array( $version_info->sections ) ) {
					$version_info->sections = $this->convert_object_to_array( $version_info->sections );
				}

				if ( isset( $version_info->icons ) && ! is_array( $version_info->icons ) ) {
					$version_info->icons = $this->convert_object_to_array( $version_info->icons );
				}

				if ( isset( $version_info->contributors ) && ! is_array( $version_info->contributors ) ) {
					$version_info->contributors = $this->convert_object_to_array( $version_info->contributors );
				}

				$this->set_version_info_cache( $version_info );
			}

			if ( ! is_object( $version_info ) ) {
				return;
			}

			$no_update = false;
			if ( version_compare( $this->version, $version_info->new_version, '<' ) ) { // @phpstan-ignore-line
				$update_cache->response[ $this->name ] = $version_info; // @phpstan-ignore-line
			} else {
				$no_update              = new stdClass();
				$no_update->id          = '';
				$no_update->slug        = $this->slug;
				$no_update->plugin      = $this->name;
				$no_update->new_version = $version_info->new_version ?? '';
				$no_update->url         = $version_info->homepage ?? '';
				$no_update->package     = $version_info->package ?? '';
				$no_update->icons       = $version_info->icons ?? '';
				$no_update->banners     = $version_info->banners ?? '';
				$no_update->banners_rtl = array();
			}

			$update_cache->last_checked           = time(); // @phpstan-ignore-line
			$update_cache->checked[ $this->name ] = $this->version; // @phpstan-ignore-line
			if ( $no_update ) {
				$update_cache->no_update[ $this->name ] = $no_update; // @phpstan-ignore-line
			}

			set_site_transient( 'update_plugins', $update_cache );
		} else {
			$version_info = $update_cache->response[ $this->name ];
		}

		// Restore our filter.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

		if ( ! empty( $update_cache->response[ $this->name ] ) && version_compare( $this->version, $version_info->new_version, '<' ) ) {

			// build a plugin list row, with update notification.
			$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
			echo '<tr class="plugin-update-tr" id="' . esc_attr( $this->slug ) . '-update" data-slug="' . esc_attr( $this->slug ) . '" data-plugin="' . esc_attr( $this->slug ) . '/' . esc_attr( $file ) . '">';
			echo '<td colspan="3" class="plugin-update colspanchange">';
			echo '<div class="update-message notice inline notice-warning notice-alt">';

			$changelog_link = self_admin_url( 'index.php?paddlepress_action=view_plugin_changelog&plugin=' . $this->name . '&slug=' . $this->slug . '&TB_iframe=true&width=772&height=911' );

			if ( isset( $version_info->name, $version_info->new_version ) ) {
				if ( empty( $version_info->download_link ) ) {
					printf(
						/* translators: %1$s: Plugin name. */
						esc_html__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s. Please register your license to update.', '{{textdomain}}' ),
						esc_html( $version_info->name ),
						'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
						esc_html( $version_info->new_version ),
						'</a>'
					);
				} else {
					printf(
						/* translators: %1$s: Plugin name. */
						esc_html__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s or %5$supdate now%6$s.', '{{textdomain}}' ),
						esc_html( $version_info->name ),
						'<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
						esc_html( $version_info->new_version ),
						'</a>',
						'<a href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->name, 'upgrade-plugin_' . $this->name ) ) . '">',
						'</a>'
					);
				}
			}

			do_action( "in_plugin_update_message-{$file}", $plugin, $version_info ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			echo '</div></td></tr>';
		}
	}

	/**
	 * Updates information on the "View version x.x details" page with custom data.
	 *
	 * @param mixed  $_data   The result object or array. Default false.
	 * @param string $_action The type of information being requested from the Plugin Installation API.
	 * @param object $_args   Plugin API arguments.
	 *
	 * @return mixed $_data
	 * @uses api_request()
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

		if ( 'plugin_information' !== $_action ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->slug ) ) {
			return $_data;
		}

		$to_send = array(
			'download_tag' => $this->download_tag,
			'slug'         => $this->slug,
			'is_ssl'       => is_ssl(),
			'fields'       => array(
				'banners' => array(),
				'reviews' => false,
				'icons'   => array(),
			),
		);

		$cache_key = 'paddlepress_api_request_' . md5( serialize( $this->slug . $this->api_data['license_key'] . $this->beta ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		// Get the transient where we store the api request for this plugin for 24 hours.
		$paddlepress_api_request_transient = $this->get_cached_version_info( $cache_key );

		// If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
		if ( empty( $paddlepress_api_request_transient ) ) {
			$api_response = $this->api_request( 'plugin_information', $to_send );

			// Expires in 3 hours.
			$this->set_version_info_cache( $api_response, $cache_key );

			if ( false !== $api_response ) {
				$_data = $api_response;
			}
		} else {
			$_data = $paddlepress_api_request_transient;
		}

		if ( is_object( $_data ) ) {
			// Convert sections into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
				$_data->sections = $this->convert_object_to_array( $_data->sections );
			}

			// Convert banners into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
				$_data->banners = $this->convert_object_to_array( $_data->banners );
			}

			// Convert icons into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
				$_data->icons = $this->convert_object_to_array( $_data->icons );
			}

			// Convert contributors into an associative array, since we're getting an object, but Core expects an array.
			if ( isset( $_data->contributors ) && ! is_array( $_data->contributors ) ) {
				$_data->contributors = $this->convert_object_to_array( $_data->contributors );
			}

			if ( ! isset( $_data->plugin ) ) {
				$_data->plugin = $this->name; // @phpstan-ignore property.notFound
			}
		}

		return $_data;
	}

	/**
	 * Convert some objects to arrays when injecting data into the update API.
	 *
	 * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
	 * decoding, they are objects. This method allows us to pass in the object and return an associative array.
	 *
	 * @param stdClass|object $data plugin api sections.
	 *
	 * @return array<int|string, mixed> Associative array converted from object.
	 * @since 1.0
	 */
	private function convert_object_to_array( $data ): array {
		$new_data = array();

		if ( is_object( $data ) && is_iterable( $data ) ) {
			foreach ( $data as $key => $value ) {
				$new_data[ $key ] = is_object( $value ) ? $this->convert_object_to_array( $value ) : $value;
			}
		}

		return $new_data;
	}

	/**
	 * Disable SSL verification in order to prevent download update failures.
	 *
	 * @param array<string, mixed> $args HTTP Req. args.
	 * @param string               $url  URL.
	 *
	 * @return object|array<int|string, mixed> $array
	 */
	public function http_request_args( $args, $url ) {

        $verify_ssl = YMMVPL_Helpers::verify_ssl();
		if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'action=download' ) ) {
			$args['sslverify'] = $verify_ssl;
		}

		return $args;
	}

	/**
	 * Calls the API and, if successful, returns the object delivered by the API.
	 *
	 * @param string               $_action The requested action.
	 * @param array<string, mixed> $_data   Parameters for the API action.
	 *
	 * @return false|object
	 * @uses get_bloginfo()
	 * @uses wp_remote_post()
	 * @uses is_wp_error()
	 */
	private function api_request( $_action, $_data ) {
		// Ensure that both $_action and download_tag are set.
		if ( empty( $_action ) || empty( $this->download_tag ) ) {
			return false;
		}

		global $paddlepress_plugin_url_available;

        $verify_ssl = YMMVPL_Helpers::verify_ssl();

		// Do a quick status check on this domain if we haven't already checked it.
		$store_hash = md5( $this->api_url );
		if ( ! is_array( $paddlepress_plugin_url_available ) || ! isset( $paddlepress_plugin_url_available[ $store_hash ] ) ) {
			$test_url_parts = wp_parse_url( $this->api_url );

			$scheme = ! empty( $test_url_parts['scheme'] ) ? $test_url_parts['scheme'] : 'http';
			$host   = ! empty( $test_url_parts['host'] ) ? $test_url_parts['host'] : '';
			$port   = ! empty( $test_url_parts['port'] ) ? ':' . $test_url_parts['port'] : '';

			if ( empty( $host ) ) {
				$paddlepress_plugin_url_available[ $store_hash ] = false;
			} else {
				$test_url                                        = $scheme . '://' . $host . $port;
				$response                                        = wp_remote_get(
					$test_url,
					array(
						'timeout'   => $this->health_check_timeout,
						'sslverify' => $verify_ssl,
					)
				);
				$paddlepress_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
			}
		}

		if ( false === $paddlepress_plugin_url_available[ $store_hash ] ) {
			return false;
		}

		$data = array_merge( $this->api_data, $_data );

		if ( $data['download_tag'] !== $this->slug ) {
			return false;
		}

		$api_params = array(
			'action'       => $_action,
			'license_key'  => ! empty( $data['license_key'] ) ? $data['license_key'] : '',
			'license_url'  => ! empty( $data['license_url'] ) ? $data['license_url'] : '',
			'download_tag' => $this->download_tag,
			'version'      => isset( $data['version'] ) ? $data['version'] : false,
			'beta'         => ! empty( $data['beta'] ),
		);

		// For plugin_information action, always return update info without license verification.
		// For download action, verify license status before allowing download.
		if ( 'plugin_information' === $_action ) {
			unset( $api_params['license_key'] );
			unset( $api_params['license_url'] );
		}

		$request = wp_remote_post(
			$this->api_url,
			array(
				'timeout'   => 15,
				'sslverify' => $verify_ssl,
				'body'      => $api_params,
			)
		);

		if ( ! is_wp_error( $request ) ) {
			$request = json_decode( wp_remote_retrieve_body( $request ) );
		}

		if ( $request && isset( $request->sections ) ) {
			$request->sections = maybe_unserialize( $request->sections );
		} else {
			$request = false;
		}

		if ( $request && isset( $request->banners ) ) {
			$request->banners = maybe_unserialize( $request->banners );
		}

		if ( $request && isset( $request->icons ) ) {
			$request->icons = maybe_unserialize( $request->icons );
		}

		if ( ! empty( $request->sections ) ) {
			foreach ( $request->sections as $key => $section ) {
				$request->$key = (array) $section;
			}
		}

		// For download action, check if license is valid.
		if ( 'download' === $_action ) {
            $license_data = YMMVPL_Helpers::get_license_data();
			if ( ! is_array( $license_data ) || ! isset( $license_data['license_status'] ) || 'valid' !== $license_data['license_status'] ) {
				return false;
			}
		}

		return $request;
	}

	/**
	 * Changelog output.
	 */
	public function show_changelog(): void {
		global $paddlepress_plugin_data;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['paddlepress_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['paddlepress_action'] ) ) : '';
		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		$slug   = isset( $_REQUEST['slug'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['slug'] ) ) : '';
		$nonce  = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		// phpcs:enable

		if ( empty( $action ) || 'view_plugin_changelog' !== $action ) {
			return;
		}

		if ( empty( $plugin ) || empty( $slug ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( esc_html__( 'You do not have permission to install plugin updates', '{{textdomain}}' ), esc_html__( 'Error', '{{textdomain}}' ), array( 'response' => 402 ) );
		}

		// SECURITY FIX: Add CSRF protection for changelog requests
		if ( ! wp_verify_nonce( $nonce, 'view_plugin_changelog_' . $slug ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh the page and try again.', '{{textdomain}}' ), esc_html__( 'Security Error', '{{textdomain}}' ), array( 'response' => 403 ) );
		}

		$data         = $paddlepress_plugin_data[ $slug ];
		$beta         = ! empty( $data['beta'] ) ? true : false;
		$cache_key    = md5( 'paddlepress_plugin_' . sanitize_key( $plugin ) . '_' . $beta . '_version_info' );
		$version_info = $this->get_cached_version_info( $cache_key );

		if ( false === $version_info ) {
			$api_params = array(
				'action'       => 'plugin_information',
				'slug'         => $slug,
				'beta'         => ! empty( $data['beta'] ),
				'download_tag' => $this->download_tag,
			);

			$verify_ssl = YMMVPL_Helpers::verify_ssl();
			$request    = wp_remote_post(
				$this->api_url,
				array(
					'timeout'   => 15,
					'sslverify' => $verify_ssl,
					'body'      => $api_params,
				)
			);

			if ( ! is_wp_error( $request ) ) {
				$version_info = json_decode( wp_remote_retrieve_body( $request ) );
			}

			if ( is_object( $version_info ) && isset( $version_info->sections ) ) {
				// SECURITY ENHANCEMENT: Safely handle potentially malicious serialized data
				if ( is_string( $version_info->sections ) ) {
					// Only unserialize if it looks like serialized data and is reasonably sized
					if ( strlen( $version_info->sections ) < 50000 &&
						( strpos( $version_info->sections, 'a:' ) === 0 || strpos( $version_info->sections, 's:' ) === 0 ) ) {
						$version_info->sections = maybe_unserialize( $version_info->sections );
					}
				}
			} else {
				$version_info = false;
			}

			if ( ! empty( $version_info ) ) {
				$this->set_version_info_cache( wp_json_encode( $version_info ), $cache_key );
			}
		}

		if ( ! empty( $version_info ) && is_object( $version_info ) && isset( $version_info->sections['changelog'] ) && is_string( $version_info->sections['changelog'] ) ) {
			echo '<div style="background:#fff;padding:10px;">' . wp_kses_post( $version_info->sections['changelog'] ) . '</div>';
		}

		exit;
	}

	/**
	 * Get version info from cache.
	 *
	 * @param string $cache_key Cache key.
	 *
	 * @return bool|mixed
	 */
	public function get_cached_version_info( $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->cache_key;
		}

		$cache = get_option( $cache_key );

		if ( ! is_array( $cache ) || empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
			return false; // Cache is expired.
		}

		// We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
		if ( isset( $cache['value'] ) && is_string( $cache['value'] ) ) {
			// We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
			$cache['value'] = json_decode( $cache['value'] );
			if ( is_object( $cache['value'] ) && isset( $cache['value']->icons ) ) {
				$cache['value']->icons = (array) $cache['value']->icons;
			}
		}
		return $cache['value'];
	}

	/**
	 * Set cache data and save in option with TTL.
	 *
	 * @param object|string|bool $value     Value.
	 * @param string             $cache_key Key.
	 * @return void
	 */
	public function set_version_info_cache( $value = '', $cache_key = '' ): void {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->cache_key;
		}

		$data = array(
			'timeout' => strtotime( '+3 hours', time() ),
			'value'   => wp_json_encode( $value ),
		);

		update_option( $cache_key, $data, 'no' ); // @phpstan-ignore argument.type
	}
}
