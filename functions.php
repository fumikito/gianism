<?php
/**
 * Global functions for Gianism.
 *
 * @package Gianism
 * @since 1.0
 * @author Takahashi Fumiki
 */


/**
 * Set Cookie wrapper
 *
 * @since 4.2.1
 * @param string    $cookie_name
 * @param string    $value
 * @param string    $expire
 * @param string    $domain
 * @param bool      $http_only
 * @param string    $same_site_policy
 * @param bool|null $is_ssl
 * @return bool
 */
function gianism_set_cookie( $cookie_name, $value, $expire, $domain = '', $http_only = true, $same_site_policy = 'Lax', $is_ssl = null ) {
	if ( preg_match( '#^https?://([^/:]+)#u', home_url(), $matches ) ) {
		// TODO: Consider domain extraction method.
		$domain = $matches[1];
	}
	if ( is_null( $is_ssl ) ) {
		$is_ssl = is_ssl();
	}
	if ( version_compare( phpversion(), '7.3.0', '>=' ) ) {
		return setrawcookie(
			$cookie_name,
			$value,
			[
				'secure'   => $is_ssl,
				'httponly' => $http_only,
				'expires'  => $expire,
				'domain'   => $domain,
				'path'     => '/',
				'samesite' => $same_site_policy,
			]
		);
	} else {
		return setrawcookie( $cookie_name, $value, $expire, '/; SameSite=' . $same_site_policy, $domain, $is_ssl, $http_only );
	}
}

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
			$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", $instance->umeta_id, $credential ) );
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
 * If user is logged in, display SNS connect buttons.
 *
 * @since 4.3.4
 * @param
 */
function gianism_connection( $user = null ) {
	if ( is_null( $user ) ) {
		$user = wp_get_current_user();
	}
	if ( $user ) {
		$profile = \Gianism\Controller\Profile::get_instance();
		$handler = apply_filters( 'gianism_connect_buttons_handler', $user );
		if ( is_callable( $handler ) ) {
			$handler( $user );
		} else {
			$profile->admin_connect_buttons( $user );
		}
	}
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
	$args = wp_parse_args(
		$args,
		[
			'utm_source'   => 'wp-admin',
			'utm_medium'   => 'link',
			'utm_campaign' => 'Plugin User',
		]
	);
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
