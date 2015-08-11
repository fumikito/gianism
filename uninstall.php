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

// Delete Option
delete_option('wp_gianism_option');
delete_option('wpg_analytics_token');
delete_option('wpg_analytics_profile');
delete_option('gianism_facebook_admin_token');
delete_option('gianism_facebook_admin_refreshed');
delete_option('gianism_facebook_admin_id');


//Delete All message if exists.
// Backword compats.
$query = new WP_Query('post_type=gianism_message&posts_per_page=-1');
if($query->have_posts()){
	while($query->have_posts()){
		$query->the_post();
		wp_delete_post(get_the_ID());
	}
	wp_reset_query();
}

// Delete all user meta
global $wpdb;
$query = <<<EOS
    DELETE FROM {$wpdb->usermeta}
    WHERE meta_key LIKE '_wpg_%'
EOS;
$wpdb->query($query);

// Delete messages
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key = '_gianism_message'");

// Drop table
$wpdb->query("DROP TABLE {$wpdb->prefix}wpg_ga_ranking");


// Delete bot cron
if( wp_next_scheduled('gianism_bot') ){
	wp_clear_scheduled_hook('gianism_bot');
}
