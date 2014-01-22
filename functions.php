<?php

/**
 * Class auto loader
 *
 * Load base class and vendor libraries.
 *
 * @ignore
 * @param string $class_name
 */
function _gianism_autoloader($class_name){
    $class_name = ltrim($class_name, '\\');
    $vendor_dir = __DIR__.DIRECTORY_SEPARATOR.'vendor';
    $path = false;
    switch( $class_name ){
        case 'Facebook':
            $path = implode(DIRECTORY_SEPARATOR, array( $vendor_dir, 'facebook-php-sdk', 'src', 'facebook.php' ));
            break;
        case 'TwitterOAuth':
            $path = implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'twitteroauth', 'twitteroauth', 'twitteroauth.php'));
            break;
        case 'OAuthException':
        case 'OAuthConsumer':
        case 'OAuthToken':
        case 'OAuthSignatureMethod':
        case 'OAuthSignatureMethod_HMAC_SHA1':
        case 'OAuthSignatureMethod_PLAINTEXT':
        case 'OAuthSignatureMethod_RSA_SHA1':
        case 'OAuthRequest':
        case 'OAuthServer':
        case 'OAuthDataStore':
        case 'OAuthUtil':
            require_once implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'twitteroauth', 'twitteroauth', 'OAuth.php'));
            return;
            break;
        case 'JWT':
            $path = implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'jwt', 'JWT.php'));
            break;
        default:
            if( 0 === strpos( $class_name, 'Gianism\\') ){
                // Original Class
                $base_dir = __DIR__.DIRECTORY_SEPARATOR.'app';
                $path_segments = explode('\\', $class_name);
                $path_segments[0] = $base_dir;
                $path = implode(DIRECTORY_SEPARATOR, array_map(function($path){
                    return strtolower(preg_replace_callback('/(?<!^)([A-Z]+)/u', function($matches){
                        return strtolower('-'.$matches[1]);
                    }, $path));
                }, $path_segments)).'.php';
            }elseif( 0 === strpos( $class_name, 'Google_') ){
                // Google
                $path_segments = explode('_', $class_name);
                $path = implode(DIRECTORY_SEPARATOR, array_merge(
                        array($vendor_dir, 'google-api-php-client', 'src'),
                        $path_segments
                    )).'.php';
            }elseif( 0 === strpos($class_name, 'YConnect\\')){
                // YConnect
                $path = str_replace('YConnect\\', implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'yconnect-php-sdk', 'lib', '')), $class_name).'.php';
            }
            break;
    }
    if( $path && file_exists($path)){
        require $path;
    }
}

/**
 * Save message
 *
 * @param int $user_id
 * @param string $body
 * @param int $from
 * @param string $subject
 * @return int|WP_Error
 */
function gianism_message($user_id, $body, $from = 0, $subject = '' ){
    /** @var \Gianism\Bootstrap $gianism */
    $gianism = \Gianism\Bootstrap::get_instance();
    $now = current_time('mysql');
    return wp_insert_post(array(
        'post_type' => $gianism->message_post_type,
        'post_title' => $subject,
        'post_content' => $body,
        'post_author' => $user_id,
        'post_status' => 'publish',
        'post_date' => $now,
        'post_date_gmt' => get_gmt_from_date($now),
    ));
}

/**
 * Returns Facebook ID
 *
 * @param int $user_id
 * @return string
 */
function get_facebook_id($user_id){
    /** @var \Gianism\Service\Facebook $facebook */
    $facebook = \Gianism\Service\Facebook::get_instance();
	return get_user_meta($user_id, $facebook->umeta_id, true);
}



/**
 * Returns url to get Publish stream permission
 *
 * This function itself has no effect without action hook.
 * See example below:
 *
 * <code>
 * // In some template, display button.
 * get_facebook_publish_permission_link(get_permalink(), 'my_favorite_action', array('post_id' => get_the_ID()));
 *
 * // Then, hook action in functions.php in your theme.
 * add_action('my_favorite_action', 'my_publish_action');
 *
 * function my_publish_action($facebook, $args){
 *      $post = get_post($args['post_id']);
 *      try{
 *          $facebook->api("/me/feed", "POST", array(
 *          "message" => "I read this article!",
 *          "link" => get_permalink($post->ID),
 *          "name" => get_the_title($post->ID),
 *          "description" => strip_tags($post->post_content),
 *          "action" => json_encode(array(
 *              "name" => get_bloginfo('name'),
 *              "link" => home_url('/')))
 *          ));
 *      }catch(FacebookApiException $e){
 *          // Error
 *          wp_die($e->getMessage());
 *       }
 * }
 * </code>
 *
 * @param string $redirect_url URL where user will be redirect afeter authentication
 * @param string $action Action name which will be fired after authenticaction
 * @param array $args Array which will be passed to action hook
 * @return string
 */
function get_facebook_publish_permission_link($redirect_url = null, $action = '', $args = array()){
    /** @var \Gianism\Service\Facebook $facebook */
    $facebook = \Gianism\Service\Facebook::get_instance();
    return $facebook->get_publish_permission_link($redirect_url, $action, $args);
}



/**
 * Returns if user is connected with particular web service.
 *
 * @param string $service One of facebook, mixi, yahoo, twitter or google.
 * @param int $user_id If not specified, current user id will be used.
 * @return boolean 
 */
function is_user_connected_with($service, $user_id = 0){
    /** @var \Gianism\Bootstrap $gianism */
	$gianism = \Gianism\Bootstrap::get_instance();
	if(!$user_id){
		$user_id = get_current_user_id();
	}
    return ($instance = $gianism->get_service_instance($service) ) && $instance->is_connected($user_id);
}



/**
 * Get user object by credencial
 *
 * Only facebook is supported.
 *
 * @todo Make other services to work.
 * @global \wpdb $wpdb
 * @param string $service
 * @param mixed $credential
 * @return \WP_User
 */
function get_user_by_service($service, $credential){
    global $wpdb;
	$gianism = \Gianism\Bootstrap::get_instance();
    $instance = $gianism->get_instance($service);
    if(!$instance){
        return false;
    }
	switch($service){
		case 'facebook':
			$user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$instance->umeta_id}' AND meta_value = %s", $credential));
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
 * You can use this function only on Facebook fan page tab.
 * 
 * @global WP_Gianism $gianism
 * @return boolean
 */
function is_user_like_fangate(){
    /** @var \Gianism\Service\Facebook $fb */
    $fb = \Gianism\Service\Facebook::get_instance();
	return $fb->is_user_like_me_on_fangate();
}



/**
 * Returns if current user is guest.
 *
 * Valid only on facebook fan gate.
 *
 * @return boolean
 */
function is_guest_on_fangate(){
    /** @var \Gianism\Service\Facebook $fb */
    $fb = \Gianism\Service\Facebook::get_instance();
	return $fb->is_guest_on_fangate();
}



/**
 * Returns if current user has wordpress account.
 * 
 * You can this function only on Facebook fan page tab.
 * 
 * @global \wpdb $wpdb
 * @param string $service
 * @return boolean
 */
function is_user_registered_with($service){
    global $wpdb;
    $gianism = \Gianism\Bootstrap::get_instance();
    $instance = $gianism->get_instance($service);
    switch($service){
		case 'facebook':
			$user_id = get_user_id_on_fangate();
			$sql = <<<EOS
				SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = '{$instance->umeta_id}' AND meta_value = %s
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
 *
 * @return string|boolean
 */
function get_user_id_on_fangate(){
    /** @var \Gianism\Service\Facebook $fb */
    $fb = \Gianism\Service\Facebook::get_instance();
    return $fb->is_registered_user_on_fangate();
}



/**
 * Get Twitter Screen Name
 *
 * @param int $user_id
 * @return string|false 
 */
function get_twitter_screen_name($user_id){
    /** @var \Gianism\Service\Twitter $twitter */
    $twitter = \Gianism\Service\Twitter::get_instance();
    return get_user_meta($user_id, $twitter->umeta_screen_name, true);
}



/**
 * Update Twitter timeline
 *
 * @param string $string
 */
function update_twitter_status($string){
    /** @var \Gianism\Service\Twitter $twitter */
    $twitter = \Gianism\Service\Twitter::get_instance();
    $twitter->tweet($string);
}



/**
 * Reply to specified user by Owner Account
 *
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
 * Caching is recommended.
 *
 * <pre>
 * $timeline = get_transient('twitter_timeline');
 * if( false === $cache){
 *     $timeline = twitter_get_timeline('my_screen_name');
 *     set_transient('twitter_timeline', $timeline, 3600);
 * }
 * foreach($timeline as $status){
 *     // Echo status
 * }
 * </pre>
 *
 * @param string $screen_name If not specified, admin user's screen name will be used.
 * @param array $additional_data
 * @return object JSON format object.
 */
function twitter_get_timeline($screen_name = null, array $additional_data = array()){
    /** @var \Gianism\Service\Twitter $twitter */
    $twitter = \Gianism\Service\Twitter::get_instance();
	if(is_null($screen_name)){
		$screen_name = $twitter->tw_screen_name;
	}
	return $twitter->call_api('statuses/user_timeline', array_merge(
        array('screen_name' => $screen_name),
        $additional_data
    ));
}



/**
 * Show Login buttons
 *
 * Show login buttons where you want.
 *
 * @param string $before
 * @param string $after
 */
function gianism_login($before = '', $after = ''){
    /** @var \Gianism\Login $login */
	$login = \Gianism\Login::get_instance();
	$login->login_form($before, $after);
}


