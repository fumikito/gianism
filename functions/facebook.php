<?php
/**
 * Facebook related functions
 *
 * @package Gianism
 */


/**
 * Returns Facebook ID
 *
 * @since 3.0.0
 * @package Gianism
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
 * @package Gianism
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
 * @package Gianism
 * @since 3.0.0
 * @return int|string
 */
function gianism_fb_admin_id() {
	/** @var \Gianism\Service\Facebook $facebook */
	$facebook = \Gianism\Service\Facebook::get_instance();

	return $facebook->admin_id;
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
 * @package Gianism
 * @since 3.0.0
 * @return \Facebook\Facebook|WP_Error
 */
function gianism_fb_admin() {
	/** @var \Gianism\Service\Facebook $facebook */
	$facebook = \Gianism\Service\Facebook::get_instance();

	return $facebook->admin;
}

/**
 * Get currently set page API
 *
 * @package Gianism
 * @since 3.0.6
 * @return \Facebook\Facebook|WP_Error
 */
function gianism_fb_page_api() {
	return \Gianism\Service\Facebook::get_instance()->get_current_page_api();
}

/**
 * Get instant article status
 *
 * @package Gianism
 * @since 3.0.8
 * @param string|int $url_or_post_id If post id is set, convert it to permalink.
 * @param bool       $development    If need development content, set to true.
 * @return WP_Error|array
 */
function gianism_fb_instant_article_status( $url_or_post_id, $development = false ) {
	if ( is_numeric( $url_or_post_id ) ) {
		$url = get_permalink( $url_or_post_id );
	} else {
		$url = $url_or_post_id;
	}
	try {
		$api = gianism_fb_page_api();
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		// Get article ID.
		$args     = [
			'access_token' => $api->getDefaultAccessToken()->getValue(),
			'id'           => $url,
			'fields'       => $development ? 'development_instant_article' : 'instant_article',
		];
		$response = $api->get( add_query_arg( $args, '/' ) )->getGraphNode()->getField( 'instant_article' );
		return [
			'url'         => $url,
			'id'          => $response->getField( 'id' ),
			'html_source' => $response->getField( 'html_source' ),
		];
	} catch ( Exception $e ) {
		return new WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Update instant article
 *
 * You don't have to determine create and update
 * because fb treats them same way.
 *
 * @package Gianism
 * @since 3.0.8
 * @param string $html       HTML content
 * @param bool   $publish    If this is live or not.
 * @param bool   $is_develop If develop, set to true.
 * @return string|WP_Error   Import Status ID if success. See {https://developers.facebook.com/docs/instant-articles/api/}
 */
function gianism_fb_update_instant_article( $html, $publish = true, $is_develop = false ) {
	try {
		$api = gianism_fb_page_api();
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$args     = [
			'access_token'     => $api->getDefaultAccessToken()->getValue(),
			'html_source'      => $html,
			'published'        => ! $is_develop && $publish,
			'development_mode' => $is_develop,
		];
		$response = $api->post( 'me/instant_articles', $args );
		return $response->getGraphNode()->getField( 'id' );
	} catch ( Exception $e ) {
		return new WP_Error( $e->getCode(), $e->getMessage() );
	}
}

/**
 * Delete post from Instant article
 *
 * @package Gianism
 * @since 3.0.8
 * @param string|int $url_or_id   URL or post ID.
 * @param bool       $development Default false.
 * @return bool|WP_Error
 */
function gianism_fb_delete_instant_article( $url_or_id, $development = false ) {
	try {
		$api = gianism_fb_page_api();
		if ( is_wp_error( $api ) ) {
			return $api;
		}
		$status = gianism_fb_instant_article_status( $url_or_id, $development );
		if ( is_wp_error( $status ) ) {
			return $status;
		}
		$result = $api->delete(
			"/{$status['id']}",
			[
				'access_token' => $api->getDefaultAccessToken()->getValue(),
			]
		);
		return $result->getGraphNode()->getField( 'success' );
	} catch ( Exception $e ) {
		return new WP_Error( $e->getCode(), $e->getMessage() );
	}
}
