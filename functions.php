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