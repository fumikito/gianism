<?php

namespace Gianism\Pattern;

/**
 * Treated as PHPMailer's instance in `wp_mail`.
 *
 * @see wp-includes/pluggable.php
 * @package Gianism\Pattern
 */
interface DummyPhpMailer {
	/**
	 * Actually do nothing.
	 *
	 * @return bool
	 */
	public function Send();
}
