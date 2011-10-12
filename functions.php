<?php

/**
 * Returns Facebook ID
 * @param int $user_id
 * @return string
 */
function get_facebook_id($user_id){
	return get_user_meta($user_id, 'wpg_facebook_id', true);
}

/**
 * Returns if user is connected with particular web service.
 * @global int $user_ID
 * @param string $service
 * @return boolean 
 */
function is_user_connected_with($service){
	global $user_ID;
	switch($service){
		case 'facebook':
			return (boolean) get_facebook_id($user_ID);
			break;
		default:
			return false;
			break;
	}
}

/**
 * Get user object by credencial
 * @global wpdb $wpdb
 * @param string $service
 * @param mixed $credential
 * @return Object 
 */
function get_user_by_service($service, $credential){
	global $wpdb;
	switch($service){
		case 'facebook':
			$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wpg_facebook_id' AND meta_value = %s", $credential));
			if($user_id){
				return new WP_User($user_id);
			}else{
				return null;
			}
			break;
		default:
			return null;
			break;
	}
}