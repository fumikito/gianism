<?php
/**
 * Function test
 *
 * @package Gianism
 */

/**
 * Sample test case.
 */
class Gianism_Basic_Test extends WP_UnitTestCase {

	/**
	 * A single example test
	 *
	 */
	function test_auto_loader() {
		// Check class exists
		$this->assertTrue( class_exists( 'Gianism\\Bootstrap' ) );
	}

	/**
	 * Test common utility
	 */
	function test_common_utility() {
		$this->assertFalse( gianism_is_user_connected_with( 'facebook', 1 ) );
	}

	/**
	 * Test twitter
	 */
	function test_twitter() {
		$json = gianism_twitter_get_timeline( '@wpGianism' );
		$this->assertTrue( is_wp_error( $json ) || is_a( $json, 'stdClass' ) );
		$this->assertFalse( gianism_is_user_connected_with( 'facebook', 1 ) );
		$this->assertWPError( gianism_update_twitter_status( 'Test Tweet' ) );
	}

	/**
	 * Redirect options
	 */
	function test_exclude_redirect() {
		$instance = \Gianism\Controller\ProfileChecker::get_instance();
		$this->assertTrue( $instance->is_excluded_paths( '/', '/' ) );
		$this->assertTrue( $instance->is_excluded_paths( '/my-account/login', '/my-account*' ) );
		$this->assertTrue( $instance->is_excluded_paths( '/my-account', '/my-account*' ) );
		$this->assertTrue( $instance->is_excluded_paths( '/my-account', "/login\n/my-account" ) );
	}
}
