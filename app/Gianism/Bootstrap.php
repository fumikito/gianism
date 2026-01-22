<?php

namespace Gianism;

use Gianism\Api\ShortCodes;
use Gianism\Controller\Admin;
use Gianism\Controller\Login;
use Gianism\Controller\Network;
use Gianism\Controller\Profile;
use Gianism\Controller\ProfileChecker;
use Gianism\Controller\Rewrite;
use Gianism\Controller\UserList;
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
		// Register Gianism command
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'gianism', 'Gianism\\Commands\\TestCommand' );
		}
	}

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		// Register assets
		add_action( 'init', array( $this, 'register_assets' ) );
		// Admin page
		add_action(
			'admin_menu',
			function () {
				Admin::get_instance();
			}
		);
		// Register notices
		$notices = [];
		foreach ( scandir( __DIR__ . '/Notices' ) as $file ) {
			if ( preg_match( '#^([^_.]+)\.php$#u', $file, $matches ) ) {
				$class_name = "Gianism\\Notices\\{$matches[1]}";
				if ( class_exists( $class_name ) && ! $class_name::DEPRECATED ) {
					$notices[] = $class_name;
				}
			}
		}
		/**
		 * gianism_admin_notices_class
		 *
		 * @package Gianism
		 * @since 3.0.4
		 * @param array $notices Class name array.
		 */
		$notices = apply_filters( 'gianism_admin_notices_class', $notices );
		foreach ( $notices as $notice ) {
			if ( is_subclass_of( $notice, 'Gianism\\Pattern\\AbstractNotice' ) ) {
				$instance = new $notice();
			}
		}

		// Remove WP Multi-byte Patch's CSS
		// Because it breaks icon font
		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_dequeue_style( 'wpmp-admin-custom' );
			},
			1000
		);

		/**
		 * Fires before gianism start.
		 *
		 * @action gianism_before_setup
		 * @since 3.0.0
		 */
		do_action( 'gianism_before_setup' );
		// Initialize service manager.
		$service = ServiceManager::get_instance();
		$service->init();

		// Initialize Rewrite rules.
		Rewrite::get_instance();
		// Network controller.
		Network::get_instance();
		// User list
		UserList::get_instance();

		// If enabled, create interface and rewrite rules.
		if ( $this->option->is_enabled() ) {
			// Init profile manager
			Profile::get_instance();
			ProfileChecker::get_instance();
			// Init login manager
			Login::get_instance();
			// Enqueue scripts
			add_action( 'login_enqueue_scripts', [ $this, 'enqueue_global_assets' ] );
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_global_assets' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
			// Add short codes.
			ShortCodes::get_instance();
		}
	}

	/**
	 * Register assets
	 *
	 */
	public function register_assets() {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// JS Cookie
		wp_register_script( 'js-cookie', $this->url . 'assets/vendor/js.cookie' . $min . '.js', [], '3.0.5', true );
		// Register assets in dependencies.json
		$json = $this->dir . '/wp-dependencies.json';
		if ( file_exists( $json ) ) {
			$deps = json_decode( file_get_contents( $json ), true );
			if ( $deps ) {
				foreach ( $deps as $dep ) {
					if ( empty( $dep['path'] ) ) {
						continue;
					}
					$url = $this->url . $dep['path'];
					switch ( $dep['ext'] ) {
						case 'js':
							$footer = [ 'in_footer' => $dep['footer'] ];
							if ( in_array( $dep['strategy'], [ 'defer', 'async' ], true ) ) {
								$footer['strategy'] = $dep['strategy'];
							}
							wp_register_script( $dep['handle'], $url, $dep['deps'], $dep['hash'], $footer );
							break;
						case 'css':
							wp_register_style( $dep['handle'], $url, $dep['deps'], $dep['hash'], 'screen' );
							break;
					}
				}
			}
		}
	}

	/**
	 * Enqueue assets for public screen
	 */
	public function enqueue_global_assets() {
		wp_enqueue_style( $this->name );
		wp_enqueue_script( $this->name . '-notice-helper' );
		wp_localize_script(
			$this->name . '-notice-helper',
			'GianismHelper',
			[
				'confirmLabel' => __( 'Consent Required', 'wp-gianism' ),
				'btnConfirm'   => __( 'Confirm', 'wp-gianism' ),
				'btnCancel'    => __( 'Cancel', 'wp-gianism' ),
			]
		);
		wp_localize_script(
			$this->name . '-notice-helper',
			'Gianism',
			array(
				'admin' => false,
			)
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script( $this->name . '-notice-helper' );
		wp_localize_script(
			$this->name . '-notice-helper',
			'Gianism',
			array(
				'admin' => true,
			)
		);
	}
}
