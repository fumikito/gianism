<?php
/**
 * Simple membership related functions.
 *
 * @package gianism
 * @since 4.4.0
 */

/**
 * If simple membership plugin is active
 *
 * @return bool
 */
function gianism_simple_membership_is_active() {
	return ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) && SIMPLE_WP_MEMBERSHIP_VER );
}

// If Simple Membership Plugin is not acitve, skip.
if ( ! gianism_simple_membership_is_active() ) {
	return;
}

/**
 * Login user if simple membership is also active.
 *
 * @param int    $user_id
 * @param string $service_name
 */
add_action(
	'gianism_after_set_login_cookie',
	function ( $user_id, $service_name ) {
		global $simple_membership;
		$user = get_userdata( $user_id );
		if ( $user && is_a( $simple_membership, 'SimpleWpMembership' ) ) {
			$simple_membership->wp_login_hook_handler( $user->user_login, $user );
		}
	},
	10,
	2
);
