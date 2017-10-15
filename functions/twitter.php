<?php
/**
 * Twitter related functions
 *
 * @package Gianism
 */




/**
 * Get Twitter Screen Name
 *
 * @package Gianism
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
 * @package Gianism
 * @since 3.0.0
 *
 * @param string $string
 * @return bool
 */
function gianism_update_twitter_status( $string ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();
	$response = $twitter->tweet( $string );
	return ! $response->errors;
}

/**
 * Reply to specified user by Owner Account
 *
 * @package Gianism
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
 * @package Gianism
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