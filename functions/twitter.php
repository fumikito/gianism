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
 * @param string          $text    Tweet string.
 * @param int|null|string $media   If set, tweet with media.
 * @param array           $options Options.
 *
 * @return bool
 * @since 3.0.0
 * @see https://developer.twitter.com/en/docs/twitter-api/tweets/manage-tweets/api-reference/post-tweets
 *
 * @package Gianism
 */
function gianism_update_twitter_status( $text, $media = null, $options = [] ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();
	if ( $media ) {
		$media_id = $twitter->upload( $media );
		if ( is_wp_error( $media_id ) ) {
			return false;
		}
		if ( ! isset( $options['media'] ) ) {
			$options['media'] = [];
		}
		$options['media']['media_ids'] = [ $media_id ];
	}
	$response = $twitter->tweet( $text, null, $options );
	return ! is_wp_error( $response );
}

/**
 * Reply to specified user by Owner Account
 *
 * @param int    $user_id
 * @param string $text
 *
 * @return boolean
 * @since 3.0.0
 *
 * @package Gianism
 */
function gianism_twitter_reply_to( $user_id, $text ) {
	$screen_name = gianism_get_twitter_screen_name( $user_id );
	if ( $screen_name ) {
		gianism_update_twitter_status( "@{$screen_name} " . $text );
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
 * @since 5.1.0
 *
 * @todo Requires basic plan of Twitter API.
 * @param string $screen_name If not specified, admin user's screen name will be used.
 * @param array $additional_data
 *
 * @return object|WP_Error JSON format object.
 */
function gianism_twitter_get_timeline( $screen_name = '', array $additional_data = [] ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();
	$user_id = gianism_get_twitter_user_id( $screen_name );
	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}
	try {
		return $twitter->call_api( "users/{$user_id}/tweets", [] );
	} catch ( \Exception $e ) {
		return new WP_Error(
			'twitter_api_error',
			$e->getMessage(),
			[
				'code' => $e->getCode(),
			]
		);
	}
}

/**
 * Convert screen name to user id.
 *
 * @param string $screen_name twitter screen name.
 * @return WP_Error|string
 */
function gianism_get_twitter_user_id( $screen_name = '' ) {
	/** @var \Gianism\Service\Twitter $twitter */
	$twitter = \Gianism\Service\Twitter::get_instance();
	if ( ! $screen_name ) {
		$endpoint = 'users/me';
	} else {
		$endpoint = 'users/by/username/' . rawurlencode( $screen_name );
	}
	try {
		$response = $twitter->call_api( $endpoint, [] );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		return $response->data->id;
	} catch ( \Exception $e ) {
		return new WP_Error(
			'twitter_api_error',
			$e->getMessage(),
			[
				'code' => $e->getCode(),
			]
		);
	}
}
