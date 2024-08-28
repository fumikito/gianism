<?php

/**
 * Returns Facebook ID
 *
 * @deprecated 3.0.0
 * @since 1.0
 *
 * @param int $user_id
 *
 * @return string
 */
function get_facebook_id( $user_id ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'gianism_get_facebook_id' );

	return gianism_get_facebook_id( $user_id );
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
 * @deprecated 3.0.0
 *
 * @param int $user_id
 * @param string $body
 * @param int $from
 * @param string $subject
 *
 * @return bool
 */
function gianism_message( $user_id, $body, $from = 0, $subject = '' ) {
	_deprecated_function( __FUNCTION__, '3.0.0' );

	return false;
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
 * @deprecated 3.0.0
 * @param string $redirect_url URL where user will be redirect afeter authentication
 * @param string $action Action name which will be fired after authenticaction
 * @param array $args Array which will be passed to action hook
 *
 * @return string
 */
function get_facebook_publish_permission_link( $redirect_url = null, $action = '', $args = array() ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'gianism_get_facebook_publish_permission_link' );
	return gianism_get_facebook_publish_permission_link( $redirect_url, $action, $args );
}

/**
 * Returns if user is connected with particular web service.
 *
 * @since 1.0
 * @deprecated 3.0.0
 * @param string $service One of facebook, mixi, yahoo, twitter or google.
 * @param int $user_id If not specified, current user id will be used.
 *
 * @return boolean
 */
function is_user_connected_with( $service, $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'gianism_is_user_connected_with' );
	return gianism_is_user_connected_with( $service, $user_id );
}

/**
 * Get user object by credential
 *
 * Only facebook is supported.
 *
 * @todo Make other services to work.
 * @global \wpdb $wpdb
 * @since 1.0
 * @deprecated 3.0.0
 * @param string $service
 * @param mixed $credential
 *
 * @return \WP_User
 */
function get_user_by_service( $service, $credential ) {
	_deprecated_function( __FUNCTION__, '3.0.0', 'gianism_get_user_by_service' );
	return gianism_get_user_by_service( $service, $credential );
}

/**
 * Returns if current user liked or not.
 *
 * You can use this function only on Facebook fan page tab.
 *
 * @deprecated 3.0.0
 * @since 1.0
 * @return boolean
 */
function is_user_like_fangate() {
	_deprecated_function( __FILE__, '3.0.0' );
	return false;
}



/**
 * Returns if current user is guest.
 *
 * Valid only on facebook fan gate.
 *
 * @deprecated 3.0.0
 * @since 1.0
 * @return boolean
 */
function is_guest_on_fangate() {
	_deprecated_function( __FILE__, '3.0.0' );
	return false;
}


/**
 * Returns if current user has WordPress account.
 *
 * You can this function only on Facebook fan page tab.
 *
 * @deprecated 3.0.0
 * @global \wpdb $wpdb
 * @param string $service
 *
 * @return boolean
 */
function is_user_registered_with( $service ) {
	_deprecated_function( __FUNCTION__, '3.0.0' );
	return false;
}


/**
 * Returns facebook id on fan gate.
 *
 * @since 1.0
 * @deprecated 3.0.0
 * @return string|boolean
 */
function get_user_id_on_fangate() {
	_deprecated_function( __FILE__, '3.0.0' );
	return false;
}

/**
 * Get Twitter Screen Name
 *
 * @since 1.2
 * @deprecated 3.0.0
 * @param int $user_id
 *
 * @return string|false
 */
function get_twitter_screen_name( $user_id ) {
	_deprecated_function( __FILE__, '3.0.0', 'gianism_get_twitter_screen_name' );
	return gianism_get_twitter_screen_name( $user_id );
}

/**
 * Update Twitter timeline
 *
 * @since 1.3
 * @deprecated 3.0.0
 * @param string $text
 */
function update_twitter_status( $text ) {
	_deprecated_function( __FILE__, '3.0.0', 'gianism_update_twitter_status' );
	gianism_update_twitter_status( $text );
}


/**
 * Reply to specified user by Owner Account
 *
 * @since 1.0
 * @deprecated 3.0.0
 * @param int $user_id
 * @param string $text
 *
 * @return boolean
 */
function twitter_reply_to( $user_id, $text ) {
	_deprecated_function( __FILE__, '3.0.0', 'gianism_twitter_reply_to' );
	return gianism_twitter_reply_to( $user_id, $text );
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
 * @deprecated 3.0.0
 *
 * @param string $screen_name If not specified, admin user's screen name will be used.
 * @param array $additional_data
 *
 * @return object JSON format object.
 */
function twitter_get_timeline( $screen_name = null, array $additional_data = array() ) {
	_deprecated_function( __FILE__, '3.0.0', 'gianism_twitter_get_timeline' );
	gianism_twitter_get_timeline( $screen_name, $additional_data );
}


/**
 * Detect if user is geek
 *
 * Geek means user is connected with Github.
 *
 * @since 2.0.0
 * @deprecated 3.0.0
 * @param int $user_id
 *
 * @return bool
 */
function is_geek( $user_id ) {
	/** @var \Gianism\Service\Github $github */
	$github = \Gianism\Service\Github::get_instance();

	return (bool) get_user_meta( $user_id, $github->umeta_id );
}

/**
 * Detect if current user is geek
 *
 * @since 2.0.0
 * @deprecated 3.0.0
 * @see is_geek
 * @return bool
 */
function is_current_user_geek() {
	return is_geek( get_current_user_id() );
}
