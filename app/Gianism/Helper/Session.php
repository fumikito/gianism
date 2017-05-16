<?php

namespace Gianism\Helper;


use Gianism\Pattern\Singleton;

/**
 * Session controller
 *
 * @package Gianism
 * @since 3.0.0
 * @property string $path
 */
class Session extends Singleton  {

	protected $name = 'gianism';

	/**
	 * Check and start session if not started
	 *
	 * @return bool
	 */
	public function start() {
		if ( session_id() && isset( $_SESSION[ $this->name ] ) ) {
			return true;
		}
		if ( ! session_start() ) {
			return false;
		}
		header( 'X-Gianism-Session: true' );
		if ( ! isset( $_SESSION[ $this->name ] ) || ! is_array( $_SESSION[ $this->name ] ) ) {
			$_SESSION[ $this->name ] = [];
		}
		return true;
	}

	/**
	 * Check if session is available
	 *
	 * @return string
	 */
	public function is_available() {
		return session_id();
	}

	/**
	 * Write session
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function write( $key, $value ) {
		if ( isset( $_SESSION[ $this->name ] ) ) {
			$_SESSION[ $this->name ][ $key ] = $value;
		}
	}

	/**
	 * Get session
	 *
	 * After get, session key will be deleted.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function get( $key ) {
		if ( isset( $_SESSION[ $this->name ][ $key ] ) ) {
			$value = $_SESSION[ $this->name ][ $key ];
			$this->delete( $key );
			return $value;
		}
		return false;
	}

	/**
	 * Delete session
	 *
	 * @param string $key
	 */
	public function delete( $key ) {
		if ( isset( $_SESSION[ $this->name ][ $key ] ) ) {
			unset( $_SESSION[ $this->name ][ $key ] );
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'path':
				return session_save_path() ?: 'no value';
				break;
			default:
				return null;
				break;
		}
	}


}
