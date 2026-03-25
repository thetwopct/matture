<?php
/**
 * Matture - REST API endpoints for AI-native integrations.
 *
 * @package WordPress
 * @subpackage matture
 */

namespace Matture;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers Matture REST API endpoints.
 */
class Matture_Rest {

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		register_rest_route(
			'matture/v1',
			'/status/(?P<block_id>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_block_status' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'block_id' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Return the status of a content-gate block for AI agents.
	 *
	 * State is always 'hidden' server-side; client-side JS manages the revealed state.
	 * Use the matture_ai_classify_content filter to enrich the response (e.g. populate mode).
	 *
	 * @param \WP_REST_Request $request REST API request object.
	 * @return \WP_REST_Response JSON response containing block_id, mode, state, and timestamp.
	 */
	public static function get_block_status( \WP_REST_Request $request ): \WP_REST_Response {
		$block_id = $request->get_param( 'block_id' );

		$data = apply_filters(
			'matture_ai_classify_content',
			array(
				'block_id' => $block_id,
				'mode'     => '',
				'state'    => 'hidden',
			),
			$request
		);

		// Ensure canonical keys are always present with correct server-side values.
		$data['block_id']  = $block_id;
		$data['state']     = 'hidden';
		$data['timestamp'] = gmdate( 'c' );

		return new \WP_REST_Response( $data, 200 );
	}
}
