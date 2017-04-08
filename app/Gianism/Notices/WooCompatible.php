<?php

namespace Gianism\Notices;


use Gianism\Pattern\AbstractNotice;

/**
 * WooCommerce notice
 *
 * @package Gianism\Notices
 */
class WooCompatible extends AbstractNotice {

	/**
	 * Get key
	 *
	 * @return string
	 */
	public function get_key() {
		return 'woo-role';
	}

	/**
	 * If WooCommerce is installed and default role is not customer.
	 *
	 * @return bool
	 */
	protected function has_notice() {
		return gianism_woocommerce_detected() && 'customer' !== get_option( 'default_role' );
	}

	/**
	 * Error message
	 *
	 * @return string
	 */
	public function message() {
		$role = get_role( get_option( 'default_role' ) );
		/* translators: %1$s: default role, %2$s: setting screen url */
		return sprintf(
			__( '<strong>[Notice]</strong> WooCommerce detected but the default user role is <code>%1$s</code>. It is recommended to change default role to <strong>Customer</strong> on <a href="%2$s">Setting Page</a>.', 'wp-gianism' ),
			$role->name,
			admin_url( 'options-general.php' )
		);
	}

}