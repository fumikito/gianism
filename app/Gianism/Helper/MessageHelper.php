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
	 * @param string $text
	 * @param bool   $error
	 * @return bool
	 */
	protected function add_message( $text, $error = false ) {
		$key = 'gianism_' . ( $error ? 'error' : 'updated' );
		if ( isset( $_COOKIE[ $key ] ) && ! empty( $_COOKIE[ $key ] ) ) {
			$messages   = json_decode( stripcslashes( $_COOKIE[ $key ] ), true );
			$messages[] = $text;
		} else {
			$messages = array( $text );
		}
		return gianism_set_cookie( $key, rawurlencode( json_encode( $messages ) ), time() + 180, '', false );
	}
}
