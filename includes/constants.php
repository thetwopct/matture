<?php
/**
 * Tax Toggle for WooCommerce - Constants
 *
 * @package WordPress
 * @subpackage {{textdomain}}
 * @since   1.4.0
 */

// This file defines package-level constants for hosts loading via Composer/VCS.

// Transient keys.
// Cached license information for the current domain.
const YMMVPL_LICENSE_DATA_TRANSIENT = '{{key_prefix}}_license_data';
// Cached general license information retrieved using the dev domain.
const YMMVPL_LICENSE_INFO_TRANSIENT = '{{key_prefix}}_license_info';

// Options.
const YMMVPL_LICENSE_KEY = '{{key_prefix}}_license_key';

// endpoints.
const YMMVPL_UPDATE_ENDPOINT  = 'https://ymmv.co/wp-json/paddlepress-api/v1/update';
const YMMVPL_LICENSE_ENDPOINT = 'https://ymmv.co/wp-json/paddlepress-api/v1/license';

// Domain used to retrieve generic license information.
const YMMVPL_DEV_INFO_DOMAIN = 'http://test.test';
