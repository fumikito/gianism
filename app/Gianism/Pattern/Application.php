<?php

namespace Gianism\Pattern;


/**
 * Base class
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0
 *
 */
abstract class Application extends Singleton {

	use AppBase;

	/**
	 * Retrieve user meta's owner ID
	 *
	 * @global \wpdb $wpdb
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return int User ID. If not exists, return 0
	 */
	public function get_meta_owner( $key, $value ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
		$sql = <<<EOS
            SELECT user_id FROM {$wpdb->usermeta}
            WHERE meta_key = %s AND meta_value = %s
EOS;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $key, $value ) );
	}
}
