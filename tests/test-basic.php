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
		$json = gianism_twitter_get_timeline( '@takahashifumiki' );
		$this->assertEquals( get_class( $json ), 'stdClass' );
		$this->assertFalse( gianism_update_twitter_status( 'Test Tweet' ) );
		$this->assertEquals( gianism_get_twitter_screen_name( 1 ), '' );
	}

	/**
	 * Test Facebook
	 */
	function test_facebook() {
		$this->assertEmpty( gianism_get_facebook_id( 1 ) );
	}
}
