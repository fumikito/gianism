<?php

/**
 * Check facebook functions
 *
 * @package Gianism
 */
class Gianism_Facebook_Test extends WP_UnitTestCase{

	/**
	 * Test Facebook
	 */
	function test_basic() {
		$this->assertEmpty( gianism_get_facebook_id( 1 ) );
		$this->assertTrue( is_wp_error( gianism_fb_page_api() ) );
		$this->assertEmpty( gianism_get_facebook_id( 1 ) );
	}

	/**
	 * Test global functions
	 */
	function test_globals() {
		$this->assertEquals( 'me', gianism_fb_admin_id() );
		$this->assertWPError( gianism_fb_delete_instant_article( 2, true ) );
		$this->assertWPError( gianism_fb_update_instant_article( '', false, true ) );
		$this->assertWPError( gianism_fb_instant_article_status( 2, true ) );
	}

}
