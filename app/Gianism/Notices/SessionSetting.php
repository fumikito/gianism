<?php

namespace Gianism\Notices;


use Gianism\Helper\Session;
use Gianism\Pattern\AbstractNotice;

/**
 * Session checker
 * @package Gianism
 */
class SessionSetting extends AbstractNotice {

	/**
	 * Get key
	 *
	 * @return string
	 */
	public function get_key() {
		return 'gianism-session';
	}

	/**
	 * Check if session has notice
	 *
	 * @return bool
	 */
	protected function has_notice() {
		$session_helper = Session::get_instance();
		// Try session start.
		if ( ! $session_helper->is_available() && ! $session_helper->start() ) {
			return true;
		}
		// Is session actually accessible?
		$timestamp = current_time( 'timestamp' );
		$session_helper->write( 'tmp', $timestamp );
		if ( $timestamp != $session_helper->get( 'tmp' ) ) {
			return true;
		}
		// Everything is O.K.
		return false;
	}

	/**
	 * Error message
	 *
	 * @return string
	 */
	public function message() {
		return sprintf( __( 'Mmm... session seemed to be not working. Please check permission of <code>session.save_path</code>( current value is <code>%s</code>).', 'wp-gianism' ), Session::get_instance()->path );
	}


}
