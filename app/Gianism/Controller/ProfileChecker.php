<?php

namespace Gianism\Controller;

use Gianism\Pattern\AbstractController;

/**
 * Profile checker.
 *
 * @package gianism
 */
class ProfileChecker extends AbstractController {
	
	/**
	 * Get default URL
	 *
	 * @return string
	 */
	public function default_url() {
		$url = get_edit_profile_url();
		if ( gianism_woocommerce_detected() && ( $page = wc_get_page_permalink( 'myaccount' ) ) ) {
			$url = get_permalink( $page );
		}
		return $url;
	}
	
	/**
	 * Get redirect URL
	 *
	 * @return string
	 */
	public function redirect_url() {
		if ( $this->option->profile_completion_path ) {
			$url = home_url( $this->option->profile_completion_path );
		} else {
			$url = $this->default_url();
		}
		return apply_filters( 'gianism_profile_url', $url );
	}
}
