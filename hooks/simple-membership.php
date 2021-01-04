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
	return ( defined( 'SIMPLE_WP_MEMBERSHIP_VER' ) && SIMPLE_WP_MEMBERSHIP_VER ) ;
}

/**
 * Activate simple membership plugin.
 *
 * @param array $plugins
 * @return array
 */
add_filter( 'gianism_plugin_classes', function( $plugins ) {
	if ( gianism_simple_membership_is_active() ) {
		$plugins['simple-membership'] = 'Gianism\Plugins\SimpleMembership';
	}
	return $plugins;
} );
