<?php
/**
 * Test REST API endpoints.
 *
 * @package matture
 */

namespace Matture\Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test the Matture REST API endpoints.
 */
class Test_Rest extends TestCase {

	/**
	 * REST server instance.
	 *
	 * @var \WP_REST_Server
	 */
	protected $server;

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$wp_rest_server = new \WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		parent::tear_down();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

	/**
	 * Test GET /matture/v1/status/{block_id} returns 200.
	 */
	public function test_get_status_returns_200() {
		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/test-block-123' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test response contains block_id, state, timestamp keys.
	 */
	public function test_response_contains_required_keys() {
		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/test-block-123' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'block_id', $data );
		$this->assertArrayHasKey( 'state', $data );
		$this->assertArrayHasKey( 'timestamp', $data );
	}

	/**
	 * Test state is always 'hidden' in response.
	 */
	public function test_state_is_always_hidden() {
		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/test-block-123' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'hidden', $data['state'] );
	}

	/**
	 * Test block_id in response matches the requested block_id.
	 */
	public function test_block_id_matches_request() {
		$block_id = 'my-custom-block-id';
		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/' . $block_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( $block_id, $data['block_id'] );
	}

	/**
	 * Test matture_ai_classify_content filter can add extra fields to response.
	 */
	public function test_matture_ai_classify_content_filter_adds_extra_fields() {
		add_filter(
			'matture_ai_classify_content',
			function ( $data, $request ) {
				$data['custom_field'] = 'custom_value';
				return $data;
			},
			10,
			2
		);

		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/test-block-123' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'custom_field', $data );
		$this->assertEquals( 'custom_value', $data['custom_field'] );

		remove_all_filters( 'matture_ai_classify_content' );
	}

	/**
	 * Test matture_ai_classify_content filter cannot override state or block_id (server enforces these).
	 */
	public function test_matture_ai_classify_content_filter_cannot_override_canonical_keys() {
		add_filter(
			'matture_ai_classify_content',
			function ( $data, $request ) {
				$data['state']    = 'revealed'; // Try to override.
				$data['block_id'] = 'hacked';   // Try to override.
				return $data;
			},
			10,
			2
		);

		$block_id = 'test-block-123';
		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/' . $block_id );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Server should enforce these values.
		$this->assertEquals( 'hidden', $data['state'], 'State should remain "hidden" and not be overridable' );
		$this->assertEquals( $block_id, $data['block_id'], 'Block ID should match request and not be overridable' );

		remove_all_filters( 'matture_ai_classify_content' );
	}

	/**
	 * Test that timestamp is in ISO 8601 format.
	 */
	public function test_timestamp_is_iso_8601() {
		$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/test-block-123' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'timestamp', $data );
		// ISO 8601 format check (basic validation).
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/', $data['timestamp'] );
	}

	/**
	 * Test that route accepts alphanumeric, underscore, and hyphen in block_id.
	 */
	public function test_route_accepts_valid_block_id_characters() {
		$valid_ids = array( 'test-123', 'test_456', 'testABC', 'test-abc_123' );

		foreach ( $valid_ids as $block_id ) {
			$request  = new \WP_REST_Request( 'GET', '/matture/v1/status/' . $block_id );
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status(), "Block ID '$block_id' should be valid" );
			$this->assertEquals( $block_id, $response->get_data()['block_id'] );
		}
	}
}
