<?php

namespace Gianism\Helper;


use Gianism\Pattern\Singleton;

/**
 * Session controller
 *
 * This class is named "Session" but actually it doesn't rely on PHP session.
 * Since 4.0, Gianism uses Cookie instead of PHP Session.
 * For backward compatibility, it keeps old name.
 *
 * @package Gianism
 * @since 3.0.0
 * @since 4.0.0 Drop PHP Session usage.
 * @property string $path
 */
class Session extends Singleton {

	protected $name = 'gianism_session';

	protected $data = null;

	/**
	 * Check and start session if not started
	 *
	 * @deprecated No more PHP Session.
	 * @return bool
	 */
	public function start() {
		return true;
	}

	/**
	 * Check if session is available
	 *
	 * @return bool
	 */
	public function is_available() {
		// @phpstan-ignore  isset.variable
		return isset( $_COOKIE );
	}

	/**
	 * Get cookie data as JSON.
	 *
	 * @return array
	 */
	protected function get_data() {
		if ( ! isset( $_COOKIE[ $this->name ] ) ) {
			return [];
		}
		$cookie = $_COOKIE[ $this->name ];
		$cookie = json_decode( stripslashes( $cookie ), true );
		return is_array( $cookie ) ? $cookie : [];
	}

	/**
	 * Ensure cookie data.
	 */
	protected function ensure_cookie() {
		if ( is_null( $this->data ) ) {
			$this->data = $this->get_data();
		}
	}

	/**
	 * Save cookie data.
	 *
	 * @return bool
	 */
	protected function save_cookie() {
		$this->ensure_cookie();
		$json   = json_encode( $this->data );
		$expire = time() + 60 * 20;
		return gianism_set_cookie( $this->name, rawurlencode( $json ), $expire );
	}

	/**
	 * Write session
	 *
	 * @param string|array $key   If array, treated as key=>value.
	 * @param mixed        $value Omitted if $key is array.
	 * @return bool
	 */
	public function write( $key, $value = '' ) {
		$this->ensure_cookie();
		if ( is_array( $key ) ) {
			$this->data = array_merge( $this->data, $key );
		} else {
			$this->data[ $key ] = $value;
		}
		return $this->save_cookie();
	}

	/**
	 * Get session
	 *
	 * After get, session key will be deleted.
	 *
	 * @param string $key
	 *
	 * @return bool|string
	 */
	public function get( $key ) {
		$this->ensure_cookie();
		if ( ! isset( $this->data[ $key ] ) ) {
			return false;
		}
		$value = $this->data[ $key ];
		$this->delete( $key );
		return $value;
	}

	/**
	 * Delete session
	 *
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key ) {
		$this->ensure_cookie();
		if ( isset( $this->data[ $key ] ) ) {
			unset( $this->data[ $key ] );
			return $this->save_cookie();
		} else {
			return false;
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
