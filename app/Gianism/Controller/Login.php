<?php

namespace Gianism\Controller;

use Gianism\Pattern\AbstractController;


/**
 * Login screen controller
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Login extends AbstractController {

	/**
	 * Hook name
	 *
	 * @deprecated 3.0.4
	 */
	const LOGIN_FORM_ACTION = 'gianism_login_form';

	/**
	 * Hook name
	 *
	 * @deprecated 3.0.4
	 */
	const BEFORE_LOGIN_FORM = 'gianism_before_login_form';

	/**
	 * Hook name
	 * @deprecated 3.0.4
	 */
	const AFTER_LOGIN_FORM = 'gianism_after_login_form';

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {

		if ( $this->option->is_enabled() ) {
			// Only for acount holder
			if ( $this->option->show_button_on_login( 'login' ) ) {
				add_action( 'login_form', array( $this, 'login_form' ) );
			}
			// Registration allowed
			if ( $this->option->show_button_on_login( 'register' ) ) {
				add_action( 'register_form', array( $this, 'register_form' ) );
			}
			// WooCommerce found.
			if ( gianism_woocommerce_detected() && $this->option->show_button_on_login( 'woocommerce' ) ) {
				add_action( 'woocommerce_login_form', array( $this, 'woo_form' ) );
				add_action( 'woocommerce_register_form', array( $this, 'woo_form' ) );
				add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'woo_form' ) );
				add_action( 'woocommerce_lostpassword_form', array( $this, 'woo_form' ) );
				add_action( 'woocommerce_resetpassword_form', array( $this, 'woo_form' ) );
			}
		}
	}

	/**
	 * Show Login Form
	 *
	 * @param string $before      Default '<div id="wpg-login">'.
	 * @param string $after       Default '</div>'.
	 * @param bool   $register    Is register form.
	 * @param string $redirect_to Redirect URL. Default empty string.
	 * @param string $context     Context of this form.
	 *
	 * @return void
	 */
	public function login_form( $before = '', $after = '', $register = false, $redirect_to = '', $context = '' ) {
		if ( empty( $before ) ) {
			$class_name = [];
			if ( $register ) {
				$class_name[] = 'register';
			}
			if ( $this->option->button_type ) {
				$class_name[] = 'large';
			}
			$class_name = empty( $class_name ) ? '' : sprintf( ' class="%s"', implode( ' ', $class_name ) );
			$before     = sprintf( '<div id="wpg-login"%s>', $class_name );
		}
		if ( empty( $after ) ) {
			$after = '</div>';
		}
		if ( '' === $redirect_to && ( $redirect_query = $this->input->get( 'redirect_to' ) ) ) {
			$redirect_to = $redirect_query;
		}
		echo $before;
		/**
		 * gianism_before_login_form
		 *
		 * @package Gianism
		 * @since 3.0.4 Add context param.
		 * @param bool $register Is register form
		 * @param string $context     Context of this button action.
		 */
		do_action( 'gianism_before_login_form', $register, $context );
		/**
		 * gianism_login_form
		 *
		 * Display login buttons.
		 *
		 * @package Gianism
		 * @since 3.0.4 Add context param.
		 * @param bool   $register Is register form
		 * @param string $redirect_to Redirect URL after login
		 * @param string $context     Context of this button action.
		 */
		do_action( 'gianism_login_form', $register, $redirect_to, $context );
		/**
		 * gianism_after_login_form
		 *
		 * @package Gianism
		 * @since 3.0.4 Add context param.
		 * @param bool $register Is register form
		 * @param string $context     Context of this button action.
		 */
		do_action( 'gianism_after_login_form', $register, $context );
		echo $after;
	}

	/**
	 * Show buttons on register form.
	 *
	 * @return void
	 */
	public function register_form() {
		$this->login_form( '', '', true );
	}

	/**
	 * Show button on WooCommerce login form
	 *
	 * @since 3.0.4
	 */
	public function woo_form() {
		if ( is_checkout() ) {
			$redirect = wc_get_checkout_url();
		} else {
			$redirect = wc_get_page_permalink( 'myaccount' );
		}
		$this->login_form( '', '', false, $redirect, 'woo-account' );
	}

} 