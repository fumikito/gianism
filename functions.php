<?php
/**
 * Global functions for Gianism.
 *
 * @package Gianism
 * @since 1.0
 * @author Takahashi Fumiki
 */


/**
 * Returns Facebook ID
 *
 * @since 3.0.0
 *
 * @param int $user_id
 *
 * @return mixed
 */
function gianism_get_facebook_id( $user_id ) {
	/** @var \Gianism\Service\Facebook $facebook */
	$facebook = \Gianism\Service\Facebook::get_instance();

	return get_user_meta( $user_id, $facebook->umeta_id, true );
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
 * @since 3.0.0
 *
 * @param string $redirect_url URL where user will be redirect after authentication
 * @param string $action Action name which will be fired after authentication
 * @param array $args Array which will be passed to action hook
 *
 * @return string
 */
function gianism_get_facebook_publish_permission_link( $redirect_url = null, $action = '', $args = [] ) {
	/** @var \Gianism\Service\Facebook $facebook */
	$facebook = \Gianism\Service\Facebook::get_instance();

	return $facebook->get_publish_permission_link( $redirect_url, $action, $args );
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
 * @return \Facebook|WP_Error
 */
function gianism_fb_admin() {
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
 * @since 3.0.0
 *
 * @param string $service One of facebook, mixi, yahoo, twitter or google.
 * @param int $user_id If not specified, current user id will be used.
 *
 * @return boolean
 */
function gianism_is_user_connected_with( $service, $user_id = 0 ) {
	/** @var \Gianism\Bootstrap $gianism */
	$gianism = \Gianism\Bootstrap::get_instance();
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	return ( $instance = $gianism->get_service_instance( $service ) ) && $instance->is_connected( $user_id );
}


/**
 * Get user object by credential
 *
 * Only facebook is supported.
 *
 * @todo Make other services to work.
 * @since 3.0.0
 * @global \wpdb $wpdb
 * @param string $service
 * @param mixed $credential
 *
 * @return \WP_User
 */
function gianism_get_user_by_service( $service, $credential ) {
	global $wpdb;
	$gianism  = \Gianism\Bootstrap::get_instance();
	$instance = $gianism->get_instance( $service );
	if ( ! $instance ) {
		return false;
	}
	switch ( $service ) {
		case 'facebook':
			$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$instance->umeta_id}' AND meta_value = %s", $credential ) );
			if ( $user_id ) {
				return new WP_User( $user_id );
			} else {
				return null;
			}
			break;
		default:
			return null;
			break;
	}
}

/**
 * Get Twitter Screen Name
 *
 * @since 3.0.0
 *
 * @param int $user_id
 *
 * @return string|false
 */
function gianism_get_twitter_screen_name( $user_id ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();

	return get_user_meta( $user_id, $twitter->umeta_screen_name, true );
}


/**
 * Update Twitter time line
 *
 * @since 3.0.0
 *
 * @param string $string
 */
function gianism_update_twitter_status( $string ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();
	$twitter->tweet( $string );
}

/**
 * Reply to specified user by Owner Account
 *
 * @since 3.0.0
 *
 * @param int $user_id
 * @param string $string
 *
 * @return boolean
 */
function gianism_twitter_reply_to( $user_id, $string ) {
	$screen_name = gianism_get_twitter_screen_name( $user_id );
	if ( $screen_name ) {
		gianism_update_twitter_status( "@{$screen_name} " . $string );
		return true;
	} else {
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
 * @since 3.0.0
 *
 * @param string $screen_name If not specified, admin user's screen name will be used.
 * @param array $additional_data
 *
 * @return object JSON format object.
 */
function gianism_twitter_get_timeline( $screen_name = null, array $additional_data = [] ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();
	if ( is_null( $screen_name ) ) {
		$screen_name = $twitter->tw_screen_name;
	}

	return $twitter->call_api( 'statuses/user_timeline', array_merge(
		array( 'screen_name' => $screen_name ),
		$additional_data
	) );
}


/**
 * Show Login buttons
 *
 * Show login buttons where you want.
 *
 * @since 1.0
 *
 * @param string $before
 * @param string $after
 * @param string $redirect_to
 */
function gianism_login( $before = '', $after = '', $redirect_to = '' ) {
	/** @var \Gianism\Controller\Login $login */
	$login = \Gianism\Controller\Login::get_instance();
	$login->login_form( $before, $after, false, $redirect_to );
}

