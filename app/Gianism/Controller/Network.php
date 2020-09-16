<?php

namespace Gianism\Controller;

use Gianism\Bootstrap;
use Gianism\Helper\Option;
use Gianism\Pattern\AbstractController;

class Network extends AbstractController {
	
	/**
	 * Network constructor.
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		// Add network notice
		add_action( 'admin_notices', [ $this, 'network_notice' ] );
	}
	
	/**
	 * Detect if Gianism can network available.
	 *
	 * @return bool
	 */
	public function network_available() {
		return is_multisite() && ! is_subdomain_install();
	}
	
	/**
	 * Display notices
	 */
	public function network_notice() {
		if ( ! $this->network_available() ) {
			// This is not network available install.
			return;
		}
		if ( Admin::get_instance()->no_nag_notice() ) {
			return;
		}
		try {
			if ( current_user_can( 'manage_network' ) && ! $this->option->is_network_activated() ) {
				throw new \Exception( __( 'Gianism provides network sites supports. Please consider network activation.', 'wp-gianism' ) );
			}
		} catch ( \Exception $e ) {
			printf( '<div class="notice notice-info is-dismissible"><p>%s</p></div>', wp_kses_post( $e->getMessage() ) );
		}
	}
}
