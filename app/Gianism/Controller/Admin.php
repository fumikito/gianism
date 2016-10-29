<?php

namespace Gianism\Controller;

use Gianism\Helper\ExtensionManager;
use Gianism\Pattern\AbstractController;
use Gianism\Service\Google;
use Gianism\UI\Screen;


/**
 * Create admin panel for Gianism
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0.0
 */
class Admin extends AbstractController {

	use ExtensionManager;

	/**
	 * @var array
	 */
	protected $views = [];

	protected $default_views = [];

	protected $invalid_options = [];

	/**
	 * Constructor executed on admin_menu hook
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		//Create plugin link
		add_filter( 'plugin_action_links', [ $this, 'plugin_page_link' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 4 );
		// Register script
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );


		$admin_screens = [
			'Gianism\\UI\\SettingScreen',
		];

		/**
		 * These classes will be rendered
		 *
		 * @filter gianism_admin_screens
		 * @param array $admin_screens An array of class name.
		 * @return array
		 */
		$admin_screens = apply_filters( 'gianism_admin_screens', $admin_screens );

		// Call all screen
		foreach ( $admin_screens as $class_name ) {
			if ( $this->is_callable( $class_name, 'Gianism\\UI\\Screen' ) ) {
				/** @var Screen $instance */
				$instance = new $class_name();
			}
		}
		// Error message.
		$message = [];
		// No service is available.
		if ( ! $this->option->is_enabled() ) {
			$message[] = sprintf( $this->_( 'No service is enabled. Please go to <a href="%s">Gianism Setiting</a> and follow instructions there.' ), $this->setting_url() );
		}
		// Check permalink
		if ( ! get_option( 'rewrite_rules', '' ) ) {
			$message[] = sprintf( $this->_( 'You should set rewrite rules. Go to <a href="%s">Permalink Setting</a> and enable it.' ), admin_url( 'options-permalink.php' ) );
		}
		// Check session
		if ( ! session_id() ) {
			session_start();
		}
		if ( ! session_id() ) {
			// Oops, session seemed to be not available.
			if ( current_user_can( 'manage_options' ) ) {
				$message[] = $this->_( 'Session is not supported. Gianism requires session for SNS connection, so please contact to your server administrator.' );
			}
		}
		if ( current_user_can( 'manage_options' ) && $this->option->has_invalid_option( 'google_redirect' ) ) {
			$message[] = sprintf( $this->_( 'Google redirect URL is deprecated since version 2.0. <strong>You must change setting on Google API Console</strong>. Please <a href="%s">update option</a> and follow the instruction there.' ), admin_url( 'options-general.php?page=gianism' ) );
		}
		/**
		 * Admin error message to display
		 *
		 * @filter gianism_admin_notices
		 * @param  array $message
		 * @return array
		 */
		$this->invalid_options = apply_filters( 'gianism_admin_notices', $message );
		add_action( 'admin_notices', [ $this, 'invalid_option_notices' ] );
	}


	/**
	 * Register assets
	 *
	 * @param string $hook_suffix
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		// Setting page and profile page
		if ( false !== array_search( $hook_suffix, [
			'settings_page_gianism',
			'profile.php',
			'tools_page_gianism_ga',
			'post-new.php',
			'post.php',
		] ) ) {
			wp_enqueue_script( $this->name . '-admin-helper' );
		}
		// Other
		wp_enqueue_style( $this->name . '-admin-panel' );
	}

	/**
	 * Show message is options are invalid.
	 */
	public function invalid_option_notices() {
		if ( ! empty( $this->invalid_options ) ) {
			array_unshift( $this->invalid_options, '<strong>[Gianism]</strong>' );
			printf( '<div class="error"><p>%s</p></div>', implode( '<br />', $this->invalid_options ) );
		}
	}

	/**
	 * Setup plugin links.
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array
	 */
	public function plugin_page_link( $links, $file ) {
		if ( false !== strpos( $file, 'wp-gianism' ) ) {
			array_unshift( $links, '<a href="' . $this->setting_url() . '">' . __( 'Settings' ) . '</a>' );
		}

		return $links;
	}


	/**
	 * Plugin row meta
	 *
	 * @param array $plugin_meta
	 * @param string $plugin_file
	 * @param array $plugin_data
	 * @param string $status
	 *
	 * @return mixed
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( false !== strpos( $plugin_file, 'wp-gianism' ) ) {
			for ( $i = 0, $l = count( $plugin_meta ); $i < $l; $i ++ ) {
				if ( false !== strpos( $plugin_meta[ $i ], 'http://takahashifumiki.com' ) ) {
					$plugin_meta[ $i ] = str_replace( 'http://takahashifumiki.com', $this->ga_link( 'http://takahashifumiki.com', 'link' ), $plugin_meta[ $i ] );
				}
			}
			$plugin_meta[] = sprintf( '<a href="http://github.takahashifumiki.com/Gianism/">Github</a>' );
		}

		return $plugin_meta;
	}
}