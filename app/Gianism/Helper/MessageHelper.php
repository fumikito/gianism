<?php

namespace Gianism\Helper;


/**
 * Add message helper
 * @package Gianism
 * @since 3.0.0
 */
trait MessageHelper {

	/**
	 * Add message to show
	 *
	 * @param string $string
	 * @param bool $error
	 * @return bool
	 */
	protected function add_message( $string, $error = false ) {
		$key = 'gianism_' . ( $error ? 'error' : 'updated' );
		if ( isset( $_COOKIE[ $key ] ) && ! empty( $_COOKIE[ $key ] ) ) {
			$messages   = json_decode( stripcslashes( $_COOKIE[ $key ] ), true );
			$messages[] = $string;
		} else {
			$messages = array( $string );
		}
		return gianism_set_cookie( $key, rawurlencode( json_encode( $messages ) ), current_time( 'timestamp', true ) + 180, '', false );
	}
}
