<?php
/**
 * Global functions for Gianism.
 *
 * @package Gianism
 * @since 1.0
 * @author Takahashi Fumiki
 */

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
            $path = implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'facebook-php-sdk', 'src', 'facebook.php' ));
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
            $path = implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'twitteroauth', 'twitteroauth', 'OAuth.php'));
            break;
        case 'JWT':
            $path = implode(DIRECTORY_SEPARATOR, array($vendor_dir, 'jwt', 'JWT.php'));
            break;
        default:
            if( 0 === strpos( $class_name, 'Gianism\\') ){
                // Original Class
                $base_dir = __DIR__.DIRECTORY_SEPARATOR.'app';
                $path_segments = explode('\\', $class_name);
                array_shift($path_segments);
                $path = $base_dir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, array_map(function($path){
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
        require_once $path;
    }
}

/**
 * Save message to specified user
 *
 * If user's email is pseudo one(e.g. example@pseudo.twitter.com),
 * WordPress's mail function <code>wp_mail</code> fails.
 * This function is originally used for <code>wp_mail</code> fallback.
 * But you can use this function to send message which will be
 * displayed on admin screen.
 *
 * @param int $user_id
 * @param string $body
 * @param int $from
 * @param string $subject
 * @return bool
 */
function gianism_message($user_id, $body, $from = 0, $subject = '' ){
    /** @var \Gianism\Message $gianism */
    $gianism = \Gianism\Message::get_instance();
    return $gianism->send_message($user_id, $body, $from, $subject);
}

/**
 * Returns Facebook ID
 *
 * @since 1.0
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
 * <pre>
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
 * </pre>
 *
 * @since 1.3
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
 * Get facebook API client for admin
 *
 * <pre>
 * $fb = gianism_fb_admin();
 * if( !is_wp_error($fb) ){
 *     // Get feed.
 *     $feeds = $fb->api('/me/feed');
 *     // Post feed.
 *     $fb->api('/me/feed', 'POST', array(
 *         'message' => 'Hola!',
 *     ));
 * }
 * </pre>
 *
 * @return Facebook|WP_Error
 */
function gianism_fb_admin(){
	/** @var \Gianism\Service\Facebook $facebook */
	$facebook = \Gianism\Service\Facebook::get_instance();
	return $facebook->admin;
}

/**
 * Get admin facebook id
 *
 * This will be user id or facebook page id.
 *
 * <pre>
 * // Example
 * $fb = gianism_fb_admin();
 * if( !is_wp_error($fb) ){
 *     $feed = $fb->api(gianism_fb_admin_id().'/feed');
 *     foreach( $feed['data'] as $status ){
 *         // Do stuff.
 *     }
 * }
 * </pre>
 *
 * @return int|string
 */
function gianism_fb_admin_id() {
	/** @var \Gianism\Service\Facebook $facebook */
	$facebook = \Gianism\Service\Facebook::get_instance();
	return $facebook->admin_id;
}

/**
 * Returns if user is connected with particular web service.
 *
 * @since 1.0
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
 * @since 1.0
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
 * @since 1.0
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
 * @since 1.0
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
 * @since 1.0
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
 * @since 1.2
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
 * @since 1.3
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
 * @since 1.0
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
 * Get twitter time line in JSON format object
 *
 * Caching is recommended.
 *
 * <pre>
 * $timeline = get_transient('twitter_timeline');
 * if( false === $timeline){
 *     $timeline = twitter_get_timeline('my_screen_name');
 *     set_transient('twitter_timeline', $timeline, 3600);
 * }
 * foreach($timeline as $status){
 *     // Echo status
 * }
 * </pre>
 *
 * @since 1.3
 * @param string $screen_name If not specified, admin user's screen name will be used.
 * @param array $additional_data
 * @return object JSON format object.
 */
function twitter_get_timeline($screen_name = null, array $additional_data = array()){
    /** @var \Gianism\Service\Twitter $twitter */
    $twitter = \Gianism\Service\Twitter::get_instance();
	if( is_null($screen_name) ){
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
 * @since 1.0
 * @param string $before
 * @param string $after
 * @param string $redirect_to
 */
function gianism_login($before = '', $after = '', $redirect_to = ''){
    /** @var \Gianism\Login $login */
	$login = \Gianism\Login::get_instance();
	$login->login_form($before, $after, false, $redirect_to);
}

/**
 * Detect if user is geek
 *
 * Geek means user is connected with Github.
 *
 * @since 2.0.0
 * @param int $user_id
 * @return bool
 */
function is_geek($user_id){
    /** @var \Gianism\Service\Github $github */
    $github = \Gianism\Service\Github::get_instance();
    return (bool)get_user_meta($user_id, $github->umeta_id);
}

/**
 * Detect if current user is geek
 *
 * @since 2.0.0
 * @see is_geek
 * @return bool
 */
function is_current_user_geek(){
    return is_geek(get_current_user_id());
}
