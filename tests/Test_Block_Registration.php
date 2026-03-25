<?php
/**
 * Test block registration.
 *
 * @package matture
 */

namespace Matture\Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Test that the matture/content-gate block is properly registered.
 */
class Test_Block_Registration extends TestCase {

	/**
	 * Test that block matture/content-gate is registered after plugin loads.
	 */
	public function test_block_is_registered() {
		$registry = \WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'matture/content-gate' );

		$this->assertNotNull( $block, 'Block matture/content-gate should be registered' );
	}

	/**
	 * Test that block is of type WP_Block_Type.
	 */
	public function test_block_is_wp_block_type() {
		$registry = \WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'matture/content-gate' );

		$this->assertInstanceOf( \WP_Block_Type::class, $block );
	}

	/**
	 * Test that block has a render callback set.
	 */
	public function test_block_has_render_callback() {
		$registry = \WP_Block_Type_Registry::get_instance();
		$block    = $registry->get_registered( 'matture/content-gate' );

		$this->assertNotNull( $block->render_callback, 'Block should have a render callback' );
		$this->assertIsCallable( $block->render_callback );
	}
}
