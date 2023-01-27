<?php

namespace Gianism\Helper;

use Facebook\PersistentData\PersistentDataInterface;

class FacebookCookiePersistentDataHandler implements PersistentDataInterface {

	/**
	 * Session handler
	 *
	 * @var Session
	 */
	private $cookie = null;

	protected $prefix = 'fb_';

	protected $store = [];

	/**
	 * Cookie constructor.
	 *
	 */
	public function __construct() {
		$this->cookie = Session::get_instance();
	}

	public function get( $key ) {
		$value = $this->cookie->get( $this->prefix . $key );
		if ( $value ) {
			$this->store[ $key ] = $value;
			return $value;
		} elseif ( isset( $this->store[ $key ] ) ) {
			return $this->store[ $key ];
		} else {
			return false;
		}
	}

	public function set( $key, $value ) {
		$this->cookie->write( $this->prefix . $key, $value );
	}
}
