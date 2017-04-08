<?php

namespace Gianism\Helper;


use Gianism\Pattern\Singleton;

/**
 * Input Helper
 * @package Gianism
 */
class Input extends Singleton {

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	public function __construct( array $argument = array() ) {
		// Do nothing because it's empty singleton
	}

	/**
	 * Short hand for $_GET
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		return isset( $_GET[ $key ] ) ? $_GET[ $key ] : null;
	}

	/**
	 * Short hand for $_POST
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function post( $key ) {
		return isset( $_POST[ $key ] ) ? $_POST[ $key ] : null;
	}

	/**
	 * Short hand for $_POST
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function request( $key ) {
		return isset( $_REQUEST[ $key ] ) ? $_REQUEST[ $key ] : null;
	}

	/**
	 * Check nonce
	 *
	 * @param string $action
	 * @param string $key
	 * @param string $referrer
	 * @return boolean
	 */
	public function verify_nonce( $action, $key = '_wpnonce', $referrer = '' ) {
		if ( ! wp_verify_nonce( $this->request( $key ), $action ) ) {
			return false;
		}
		if ( $referrer && $referrer == $this->request( '_wp_http_referer' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Get $_SERVER variables
	 *
	 * @param string $key
	 *
	 * @return null
	 */
	public function server( $key ) {
		return isset( $_SERVER[ $key ] ) ? $_SERVER[ $key ] : null;
	}

	/**
	 * Get nonce action
	 *
	 * @param string $action
	 *
	 * @return string
	 */
	public function nonce_action( $action ) {
		return "wp_gianism_{$action}";
	}

	/**
	 * Short hand for wp_die
	 *
	 * @param string $message
	 * @param int $status_code
	 * @param bool $return
	 */
	public function wp_die( $message, $status_code = 500, $return = true ) {
		wp_die( $message, get_status_header_desc( $status_code ) . ' | ' . get_bloginfo( 'name' ), [
			'response'  => (int) $status_code,
			'back_link' => (boolean) $return,
		] );
	}
}
