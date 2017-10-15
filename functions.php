<?php
/**
 * Global functions for Gianism.
 *
 * @package Gianism
 * @since 1.0
 * @author Takahashi Fumiki
 */





/**
 * Returns if user is connected with particular web service.
 *
 * @package Gianism
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

	return ( $instance = $gianism->service->get( $service ) ) && $instance->is_connected( $user_id );
}


/**
 * Get user object by credential
 *
 * Only facebook is supported.
 *
 * @todo Make other services to work.
 * @package Gianism
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
	$instance = $gianism->service->get( $service );
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
 * Show Login buttons
 *
 * Show login buttons where you want.
 *
 * @package Gianism
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

/**
 * Add UTM campaign link to WordPress admin
 *
 * @package Gianism
 * @since 3.0.0
 * @internal
 * @param string $url
 * @param array  $args
 *
 * @return string
 */
function gianism_utm_link( $url, $args = [] ) {
	$args = wp_parse_args( $args, [
		'utm_source'   => 'wp-admin',
	    'utm_medium'   => 'link',
	    'utm_campaign' => 'Plugin User',
	] );
	return add_query_arg( $args, $url );
}

/**
 * Detect if WooCommerce is activated
 *
 * @package Gianism
 * @since 3.0.5
 * @return bool
 */
function gianism_woocommerce_detected() {
	return class_exists( 'WooCommerce' );
}
