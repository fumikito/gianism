<?php
/*
 * Delete all data for Literally WordPress
 * 
 * @package gianism
 * @since 1.0
 */
//Check whether WordPress is initialized or not.
if(!defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')){
	exit();
}

//Delete Option
delete_option('wp_gianism_option');

//Delete All message if exists.
$query = new WP_Query('post_type=gianism_message&posts_per_page=-1');
if($query->have_posts()){
	while($query->have_posts()){
		$query->the_post();
		wp_delete_post(get_the_ID());
	}
	wp_reset_query();
}