<?php

namespace Gianism\Helper;

use Gianism\Pattern\Singleton;

/**
 * Option interface
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 *
 * @property-read ServiceManager $service
 * @property-read Input          $input
 * @property-read bool           $force_register
 * @property-read bool           $show_button_on_login
 * @property-read int            $button_type
 */
class Option extends Singleton {

	use i18n, MessageHelper;

	/**
	 * Action name which fires on updating option
	 */
	const UPDATED_ACTION = 'gianism_option_updated';

	/**
	 * Key name for option
	 *
	 * @var string
	 */
	protected $key = 'wp_gianism_option';

	/**
	 * Option values
	 *
	 * @var array
	 */
	public $values = [];

	/**
	 * Option's initial value
	 *
	 * @var array
	 */
	protected $default_option = [];

	/**
	 * Original option values
	 *
	 * @var array
	 */
	protected $original_option = [
		'force_register'         => true,
		'show_button_on_login'   => true,
		'button_type'            => 0,
		'do_cron'                => false,
	];


	/**
	 * Constructor
	 *
	 * @param array $argument Settings array.
	 */
	protected function __construct( array $argument = [] ) {
		$this->values = get_option( $this->key, [] );
		foreach ( $this->default_option as $key => $value ) {
			if ( ! isset( $this->values[ $key ] ) ) {
				$this->values[ $key ] = $value;
			}
		}
	}

	/**
	 * Set default option
	 *
	 * @param string $key
	 * @param mixed  $default
	 */
	public function set_default( $key, $default ) {
		if ( ! isset( $this->values[ $key ] ) ) {
			$this->values[ $key ] = $default;
		}
	}

	/**
	 * Save options with post data
	 */
	public function update() {
		foreach ( $this->get_option_keys() as $key => $default ) {
			$input = $this->input->post( $key );
			if ( is_numeric( $default ) ) {
				$this->values[ $key ] = (int) $input;
			} elseif ( is_bool( $default ) ) {
				$this->values[ $key ] = (bool) $input;
			} elseif ( is_array( $default ) ) {
				$this->values[ $key ] = (array) $input;
			} else {
				$this->values[ $key ] = trim( (string) $input );
			}
		}
		// Save message.
		if ( update_option( $this->key, $this->values ) ) {
			$this->add_message( $this->_( 'Option updated.' ) );
			do_action( self::UPDATED_ACTION, $this->values );
		} else {
			$this->add_message( $this->_( 'Option failed to update.' ), true );
		}
	}

	/**
	 * Get all option available.
	 *
	 * @return array
	 */
	public function get_option_keys() {
		$options = $this->original_option;
		foreach ( $this->service->all_services() as $service ) {
			$instance = $this->service->get( $service );
			foreach ( $instance->get_keys() as $key => $default ) {
				$options[ $key ] = $default;
			}
		}
		return $options;
	}

	/**
	 * Determine if service is enabled.
	 *
	 * @param string $service If not specified, one of the services are enabled, return true.
	 *
	 * @return bool
	 */
	public function is_enabled( $service = '' ) {
		if ( ! empty( $service ) ) {
			// Service is specified, use it
			switch ( $service ) { // Backward compatibility
				case 'facebook':
					$service = 'fb';
					break;
				case 'twitter':
					$service = 'tw';
					break;
				case 'google':
					$service = 'ggl';
					break;
			}
			$key = $service . '_enabled';

			return isset( $this->values[ $key ] ) && (bool) $this->values[ $key ];
		} else {
			foreach ( $this->values as $key => $value ) {
				if ( false !== strpos( $key, '_enabled' ) && $value ) {
					return true;
					break;
				}
			}

			return false;
		}
	}

	/**
	 * Partially update
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	public function partial_update( array $options ) {
		$changed = false;
		foreach ( $options as $key => $value ) {
			if ( array_key_exists( $key, $this->default_option ) ) {
				$this->values[ $key ] = $value;
				$changed              = true;
			}
		}
		if ( $changed ) {
			$result = update_option( $this->key, $this->values );
			if ( $result ) {
				do_action( self::UPDATED_ACTION, $this->values );
			}
			flush_rewrite_rules();
			return $result;
		}

		return false;
	}

	/**
	 * Detect if show login buttons
	 *
	 * @param string $context
	 *
	 * @return mixed|void
	 */
	public function show_button_on_login( $context = 'login' ) {
		/**
		 * Display Case-by-case filter
		 *
		 * @filter gianism_show_button_on_login
		 *
		 * @param bool $display Whether to display
		 * @param string $context 'login', 'register', etc.
		 *
		 * @return bool
		 */
		return apply_filters( 'gianism_show_button_on_login', $this->show_button_on_login, $context );
	}

	/**
	 * Return button types
	 *
	 * @return array
	 */
	public function button_types() {
		return [
			$this->_( 'Medium' ),
			$this->_( 'Large' ),
		];
	}

	/**
	 * Returns if option is wrong
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has_invalid_option( $name ) {
		switch ( $name ) {
			case 'google_redirect':
				$option = get_option( $this->key, [] );

				return isset( $saved_option['ggl_redirect_uri'] ) && ! empty( $saved_option['ggl_redirect_uri'] );
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Returns if login is forced to use SSL.
	 *
	 * To override it, use filter `gianism_force_ssl_login`
	 *
	 * @return boolean
	 */
	public function is_ssl_required() {
		$is_ssl = false;
		foreach ( [ 'siteurl', 'home' ] as $key ) {
			if ( 0 === strpos( get_option( $key, '' ), 'https://' ) ) {
				$is_ssl = true;
			}
		}
		if ( ! $is_ssl ) {
			if ( ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN ) || ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) ) {
				$is_ssl = true;
			}
		}

		/**
		 * Login action must be under SSL or not.
		 *
		 * @filter gianism_force_ssl_login
		 *
		 * @param bool $is_ssl
		 *
		 * @return bool
		 */
		return apply_filters( 'gianism_force_ssl_login', $is_ssl );
	}


	/**
	 * Detect if user can register
	 *
	 * @return bool
	 */
	public function user_can_register() {
		// WordPress' default.
		$can = $this->force_register ?: (bool) get_option( 'users_can_register' );
		// If WooCommerce is installed, change it.
		if ( gianism_woocommerce_detected() ) {
			$can = true;
		}
		return $can;
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'service':
				return ServiceManager::get_instance();
				break;
			case 'input':
				return Input::get_instance();
				break;
			default:
				if ( isset( $this->values[ $name ] ) ) {
					return $this->values[ $name ];
				} else {
					return null;
				}
				break;
		}
	}
}