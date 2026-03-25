<?php
/**
 * Admin View: Plugin License Key Notice
 *
 * @package           ymmvpl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$url = esc_url( admin_url( 'options-general.php?page={{plugin_slug}}-settings&tab=license' ) );

$text = sprintf(
	/* translators: %s: URL to the settings page */
	__( '<strong>{{plugin_name}} is not registered</strong>. Please enter your License Key to get security and feature updates. <a href="%s">Go to the plugin settings</a>.', '{{textdomain}}' ),
	$url
);

?>
<div id="message" class="notice notice-warning is-dismissible" data-dismissible="{{textdomain}}-license-key-notice" data-nonce="<?php echo esc_attr( wp_create_nonce( '{{textdomain}}_dismiss_license_notice' ) ); ?>">
	<p>
	<?php
	echo wp_kses(
		$text,
		array(
			'strong' => array(),
			'a'      => array( 'href' => array() ),
		)
	);
	?>
	</p>
</div>