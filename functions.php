<?php

/**
 * Returns Facebook ID
 * @global WP_Gianism $gianism
 * @param int $user_id
 * @return string
 */
function get_facebook_id($user_id){
	global $gianism;
	return get_user_meta($user_id, $gianism->fb->umeta_id, true);
}



/**
 * Returns url to get Publish stream permission
 * @global WP_Gianism $gianism
 * @param string $redirect_url URL where user will be redirect afeter authentication
 * @param string $action Action name which will be fired after authenticaction
 * @param array $args Array which will be passed to action hook
 * @return string
 */
function get_facebook_publish_permission_link($redirect_url = null, $action = '', $args = array()){
	global $gianism;
	return $gianism->fb->get_publish_permission_link($redirect_url, $action, $args);
}



/**
 * Returns if user is connected with particular web service.
 * @global WP_Gianism $gianism
 * @param string $service One of facebook, mixi, yahoo, twitter or google.
 * @param int $user_id If not specified, current user id will be used.
 * @return boolean 
 */
function is_user_connected_with($service, $user_id = 0){
	global $gianism;
	if(!$user_id){
		$user_id = get_current_user_id();
	}
	switch($service){
		case 'facebook':
			return $gianism->fb && (boolean) get_facebook_id($user_id);
			break;
		case 'mixi':
			return $gianism->mixi && get_user_meta($user_id, $gianism->mixi->umeta_id, true);
			break;
		case 'yahoo':
			return $gianism->yahoo && get_user_meta($user_id, $gianism->yahoo->umeta_id, true);
			break;
		case 'twitter':
			return $gianism->twitter && get_user_meta($user_id, $gianism->twitter->umeta_id, true);
			break;
		case 'google':
			return $gianism->google && get_user_meta($user_id, $gianism->google->umeta_account, true);
			break;
		default:
			return false;
			break;
	}
}



/**
 * Get user object by credencial
 * @global wpdb $wpdb
 * @global WP_Gianism $gianism
 * @param string $service
 * @param mixed $credential
 * @return Object 
 */
function get_user_by_service($service, $credential){
	global $wpdb, $gianism;
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



/**
 * Returns if current user liked or not.
 * 
 * You can this function only on Facebook fan page tab.
 * 
 * @global WP_Gianism $gianism
 * @return boolean
 */
function is_user_like_fangate(){
	global $gianism;
	return $gianism->fb->is_user_like_me_on_fangate();
}



/**
 * Returns if current user is guest.
 * @global WP_Gianism $gianism
 * @return boolean
 */
function is_guest_on_fangate(){
	global $gianism;
	return $gianism->fb->is_guest_on_fangate();
}



/**
 * Returns if current user has wordpress account.
 * 
 * You can this function only on Facebook fan page tab.
 * 
 * @global WP_Gianism $gianism
 * @return boolean
 */
function is_user_registered_with($service){
	global $gianism, $wpdb;
	switch($service){
		case 'facebook':
			$user_id = get_user_id_on_fangate();
			$sql = <<<EOS
				SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = 'wpg_facebook_id' AND meta_value = %s
EOS;
			return $wpdb->get_var($wpdb->prepare($sql, $signed['user_id']));
			break;
		default:
			return false;
			break;
	}
}



/**
 * Returns facebook id on fan gate.
 * @global WP_Gianism $gianism
 * @return stiring|boolean
 */
function get_user_id_on_fangate(){
	global $gianism;
	return $gianism->fb->is_registered_user_on_fangate();
}



/**
 * Get Twitter Screen Name 
 * @global WP_Gianism $gianism
 * @param int $user_id
 * @return string|false 
 */
function get_twitter_screen_name($user_id){
	global $gianism;
	return get_user_meta($user_id, $gianism->twitter->umeta_screen_name, true);
}



/**
 * Update Twitter timeline
 * @global WP_Gianism $gianism
 * @param string $string 
 */
function update_twitter_status($string){
	global $gianism;
	$gianism->twitter->tweet($string);
}



/**
 * Reply to specified user by Owner Account
 * @param int $user_id
 * @param string $string
 * @return boolean 
 */
function twitter_reply_to($user_id, $string){
	$screen_name = get_twitter_screen_name($user_id);
	if($screen_name){
		update_twitter_status("@{$screen_name} ".$string);
		return true;
	}else{
		return false;
	}
}



/**
 * Get twitter timeline in JSON format object
 * 
 * @global WP_Gianism $gianism
 * @param string $screen_name If not specified, admin user's screen name will be used.
 * @param array $additional_data
 * @return Object
 */
function twitter_get_timeline($screen_name = null, $additional_data = array()){
	global $gianism;
	if(is_null($screen_name)){
		$screen_name = $gianism->twitter->screen_name;
	}
	$data = array_merge(array('screen_name' => $screen_name), $additional_data);
	return $gianism->twitter->request('statuses/user_timeline', $data);
}



/**
 * Show Login buttons
 * @global WP_Gianism $gianism 
 */
function gianism_login(){
	global $gianism;
	$gianism->show_login_form();
}