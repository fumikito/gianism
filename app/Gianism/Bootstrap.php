<?php

namespace Gianism;

use Gianism\Controller\Admin;
use Gianism\Controller\Login;
use Gianism\Controller\Profile;
use Gianism\Controller\Rewrite;
use Gianism\Helper\ServiceManager;
use Gianism\Pattern\AppBase;
use Gianism\Pattern\Singleton;

/**
 * Main controller of Gianism
 *
 * This controller initialize Gianism.
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0.0
 */
class Bootstrap extends Singleton {

	use AppBase;

	/**
	 * Initialize Gianism
	 */
	public static function init() {
		$instance = self::get_instance();
		/**
		 * Fires after gianism is set up.
		 *
		 * @action gianism_init
		 */
		do_action( 'gianism_after_setup' );
	}

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		// Register assets
		add_action( 'init', array( $this, 'register_assets' ) );
		// Admin page
		add_action( 'admin_menu', function () {
			Admin::get_instance();
		} );
		// Remove WP Multi-byte Patch's CSS
		// Because it breaks icon font
		add_action( 'admin_enqueue_scripts', function () {
			wp_dequeue_style( 'wpmp-admin-custom' );
		}, 1000 );
		// Initialize service manager.
		ServiceManager::get_instance();
		// Initialize Rewrite rules.
		Rewrite::get_instance();
		// If enabled, create interface and rewrite rules.
		if ( $this->option->is_enabled() ) {
			// Init profile manager
			Profile::get_instance();
			// Init login manager
			Login::get_instance();
			// Enqueue scripts
			add_action( 'login_enqueue_scripts', [ $this, 'enqueue_global_assets' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_global_assets' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		}

	}

	/**
	 * Register assets
	 *
	 */
	public function register_assets() {
		// LigatureSymbols
		wp_register_style( 'ligature-symbols', $this->url . 'assets/css/lsf.css', [], '2.11' );
		// Gianism style
		wp_register_style( $this->name, $this->url . 'assets/css/gianism-style.css', [ 'ligature-symbols' ], $this->version );
		// JS Cookie
		wp_register_script( 'js-cookie', $this->url . 'assets/js/js.cookie.js', [], '2.1.3', true );
		// Gianism Notice
		wp_register_script( $this->name . '-notice-helper', $this->url . 'assets/js/public-notice.js', [
			'jquery-effects-highlight',
			'js-cookie',
		], $this->version, true );
		// Admin helper script
		wp_register_script( $this->name . '-admin-helper', $this->url . 'assets/js/admin-helper.js', [
			'jquery',
			'jquery-form',
		], $this->version, true );
		// Admin panel style
		wp_register_style( $this->name . '-admin-panel', $this->url . 'assets/css/gianism-admin.css', [ 'ligature-symbols' ], $this->version );
	}

	/**
	 * Enqueue assets for public screen
	 */
	public function enqueue_global_assets() {
		wp_enqueue_style( $this->name );
		wp_enqueue_script( $this->name . '-notice-helper' );
		wp_localize_script( $this->name . '-notice-helper', 'Gianism', array(
			'admin' => false,
		) );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script( $this->name . '-notice-helper' );
		wp_localize_script( $this->name . '-notice-helper', 'Gianism', array(
			'admin' => true,
		) );
	}
}
