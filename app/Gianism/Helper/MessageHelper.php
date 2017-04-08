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
	 */
	protected function add_message( $string, $error = false ) {
		$key = 'gianism_' . ( $error ? 'error' : 'updated' );
		if ( isset( $_COOKIE[ $key ] ) && ! empty( $_COOKIE[ $key ] ) ) {
			$messages   = json_decode( stripcslashes( $_COOKIE[ $key ] ), true );
			$messages[] = $string;
		} else {
			$messages = array( $string );
		}
		setcookie( $key, json_encode( $messages ), current_time( 'timestamp' ) + 180, '/' );
	}
}