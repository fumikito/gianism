<?php
/**
 * Hooks for WP-Members plugin.
 *
 * @package gianism
 * @since 3.1.0
 * @see https://wordpress.org/plugins/wp-members/
 */

// Avoid direct loading.
defined( 'ABSPATH' ) || die();

// If wp-members is not activated, skip this file.
if ( ! defined( 'WPMEM_VERSION' ) ) {
	return;
}

/**
 * Add redirect url if current page is singular page.
 *
 * @param string $redirect Redirect URL.
 * @return
 */
add_filter( 'gianism_default_redirect_link', function( $redirect ) {
	if ( is_singular() && admin_url( 'profile.php' ) == $redirect ) {
		/**
		 * gianism_wp_members_redirect
		 *
		 * Should redirect to single URL?
		 *
		 * @param bool    $redirect Default is `! is_page()` because some plugins set page as login screen.
		 * @param WP_Post $post     Current page's post object.
		 * @return bool
		 */
		$force_redirect_to_single = apply_filters( 'gianism_wp_members_redirect', ! is_page(), get_queried_object() );
		if ( $force_redirect_to_single ) {
			$redirect = get_permalink( get_queried_object() );
		}
	}
	return $redirect;
}, 9 );
