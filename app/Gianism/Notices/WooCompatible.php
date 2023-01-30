<?php

namespace Gianism\Notices;


use Gianism\Pattern\AbstractNotice;

/**
 * WooCommerce notice
 *
 * @package Gianism\Notices
 */
class WooCompatible extends AbstractNotice {

	protected function init() {
		add_filter( 'login_url', [ $this, 'login_url' ], 10, 3 );
	}


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
		return gianism_woocommerce_detected() && ( 'customer' !== $this->option->get( 'default_role' ) );
	}

	/**
	 * Error message
	 *
	 * @return string
	 */
	public function message() {
		$role = get_role( $this->option->get( 'default_role' ) );
		return sprintf(
			// translators: %1$s: default role, %2$s: setting screen url
			__( '<strong>[Notice]</strong> WooCommerce detected but the default user role is <code>%1$s</code>. It is recommended to change default role to <strong>Customer</strong> on <a href="%2$s">Setting Page</a>.', 'wp-gianism' ),
			$role->name,
			admin_url( 'options-general.php' )
		);
	}

	/**
	 * Change login url to woocommerce page.
	 *
	 * @param $login_url
	 * @param $redirect
	 * @param $force_reauth
	 *
	 * @return mixed
	 */
	public function login_url( $login_url, $redirect, $force_reauth ) {
		if ( ! function_exists( 'wc_get_page_permalink' ) ) {
			return $login_url;
		}
		$login_url = wc_get_page_permalink( 'myaccount' );
		$args      = [];
		if ( $redirect ) {
			$args['redirect_to'] = $redirect;
		}
		if ( $force_reauth ) {
			$args['reauth'] = 1;
		}
		if ( ! empty( $args ) ) {
			$login_url = add_query_arg( $args, $login_url );
		}
		return $login_url;
	}
}
