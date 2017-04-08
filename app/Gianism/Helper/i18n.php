<?php

namespace Gianism\Helper;

/**
 * i18n helper
 * @package Gianism
 */
trait i18n {

	/**
	 * i18n Domain
	 *
	 * @var string
	 */
	protected $domain = \GIANISM_DOMAIN;

	/**
	 * Alias of __
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public function _( $string ) {
		return __( $string, $this->domain );
	}

	/**
	 * Alias of _e
	 *
	 * @param string $string
	 */
	public function e( $string ) {
		_e( $string, $this->domain );
	}

}