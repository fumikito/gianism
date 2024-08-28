<?php

namespace Gianism\Controller;

use Gianism\Pattern\AbstractController;

/**
 * Profile checker.
 *
 * @package gianism
 */
class ProfileChecker extends AbstractController {

	public function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		add_action(
			'init',
			function () {
				if ( ! is_user_logged_in() ) {
					return;
				}
				add_action(
					'template_redirect',
					function () {
						if ( $this->should_redirect() ) {
							$this->redirect();
						} elseif ( $this->should_show_popup() ) {
							$this->show_popup();
						}
					},
					1
				);
			}
		);
	}

	/**
	 * Detect if WP is under WP 5.0
	 *
	 * @return bool
	 */
	public function is_over_5() {
		global $wp_version;
		return version_compare( $wp_version, '5.0.0', '>' );
	}

	/**
	 * Detect if should show popup.
	 *
	 * @return bool
	 */
	public function should_show_popup() {
		return $this->is_over_5() && ( 'popup' === $this->option->check_profile );
	}

	/**
	 * Detect if should redirect users.
	 *
	 * @return bool
	 */
	public function should_redirect() {
		return $this->is_over_5() && ( 'redirect' === $this->option->check_profile );
	}

	/**
	 * Get default URL
	 *
	 * @return string
	 */
	public function default_url() {
		$url = get_edit_profile_url();
		if ( gianism_woocommerce_detected() ) {
			$page = wc_get_page_permalink( 'myaccount' );
			if ( $page ) {
				$url = get_permalink( $page );
			}
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

	/**
	 * Detect if email is pseudo one.
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function is_pseudo_mail( $email ) {
		return false !== strpos( $email, '@pseudo.' );
	}

	/**
	 * Detect if user's password is his/her own.
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function is_password_unknown( $user_id ) {
		return (bool) get_user_meta( $user_id, '_wpg_unknown_password', true );
	}

	/**
	 * Register REST API endpoint.
	 */
	public function register_rest() {
		register_rest_route(
			'gianism/v1',
			'profile/me',
			[
				[
					'methods'             => 'GET',
					'args'                => [],
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'callback'            => function ( \WP_REST_Request $request ) {
						$error    = $this->get_error( get_current_user_id() );
						$response = [
							'errors' => $error->get_error_messages(),
							'url'    => $this->redirect_url(),
						];
					},
				],
			]
		);
	}

	/**
	 *
	 *
	 * @param int $user_id
	 * @return \WP_Error
	 */
	public function get_error( $user_id ) {
		static $error = null;
		if ( is_null( $error ) ) {
			$error = new \WP_Error();
			$user  = get_userdata( $user_id );
			if ( $this->is_password_unknown( $user->ID ) ) {
				$error->add( 'password_unknown', __( 'Your password is automatically generated. Please change it to your own.', 'wp-gianism' ) );
			}
			if ( $this->is_pseudo_mail( $user->user_email ) ) {
				$error->add( 'email', __( 'Your email is pseudo one because your SNS has no permission to provide email to us. ', 'wp-gianism' ) );
			}
			$error = apply_filters( 'gianism_profile_error', $error, $user );
		}
		return $error;
	}

	/**
	 * Redirect incomplete users.
	 */
	public function redirect() {
		$error = $this->get_error( get_current_user_id() );
		if ( ! $error->get_error_messages() ) {
			return;
		}
		$path          = empty( $_SERVER['REQUEST_URI'] ) ? '/' : $_SERVER['REQUEST_URI'];
		$skip_redirect = false !== strpos( $path, $this->option->profile_completion_path );
		if ( ! $skip_redirect && $this->is_excluded_paths( $path ) ) {
			$skip_redirect = true;
		}
		// If this is admin, skip redirect.
		if ( ! $skip_redirect && current_user_can( 'manage_options' ) ) {
			$skip_redirect = true;
		}
		// Hook for redirection.
		$skip_redirect = apply_filters( 'gianism_skip_redirect', $skip_redirect );
		if ( $skip_redirect ) {
			return;
		}
		wp_redirect( $this->redirect_url() );
	}

	/**
	 * Display popup for
	 */
	public function show_popup() {
		$error = $this->get_error( get_current_user_id() );
		if ( ! $error->get_error_messages() ) {
			return;
		}
		// translators: %s is URL
		$message = sprintf( __( 'You have an incomplete profile. To access full features of this site, please fill your profile <a href="%s">here</a>.', 'wp-gianism' ), $this->redirect_url() );
		$message = apply_filters( 'gianism_profile_error_popup', $message, $this->redirect_url(), $error );
		$this->add_message( $message, true );
	}

	/**
	 * Check if path is excluded.
	 *
	 * @param string      $path
	 * @param string|null $excluded
	 *
	 * @return bool
	 */
	public function is_excluded_paths( $path, $excluded = null ) {
		if ( is_null( $excluded ) ) {
			$excluded = $this->option->exclude_from_redirect;
		}
		$patterns = array_filter(
			array_map(
				function ( $line ) {
					return trim( $line );
				},
				preg_split( '#[\r\n]#u', $excluded )
			)
		);
		foreach ( $patterns as $pattern ) {
			if ( fnmatch( $pattern, $path ) ) {
				return true;
			}
		}
		return false;
	}
}
