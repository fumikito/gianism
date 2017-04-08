<?php

namespace Gianism\Helper;
use Gianism\Pattern\DummyPhpMailer;

/**
 * Pseudo PHPMailer class for Hijack mail sending.
 * @package Gianism
 */
class PseudoPhpMailer implements DummyPhpMailer {

	public $sent = true;

	/**
	 * PseudoPhpMailer constructor.
	 *
	 * @param bool $sent
	 */
	public function __construct( $sent = true ) {
		$this->sent = $sent;
	}

	/**
	 * Send mail
	 *
	 * @return bool
	 */
	public function Send() {
		return $this->sent;
	}
}
