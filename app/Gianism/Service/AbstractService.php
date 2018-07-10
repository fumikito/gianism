<?php

namespace Gianism\Service;

use Gianism\Helper\Option;
use Gianism\Pattern\AppBase;
use Gianism\Pattern\Application;
use Gianism\Pattern\Singleton;
use Gianism\Controller\Login;

/**
 * Common Utility for Social Service
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 * @property-read string $service_name
 * @property-read bool $enabled
 */
abstract class AbstractService extends Application {

	/**
	 * URL prefix
	 *
	 * If this property is empty,
	 * service name will be used.
	 * e.g. http://example.jp/facebook/
	 *
	 * @var string
	 */
	public $url_prefix = '';

	/**
	 * Option key to retrieve
	 *
	 * @var array
	 */
	protected $option_keys = [];

	/**
	 * Verbose service name
	 *
	 * If not set, $this->service_name will be used;
	 *
	 * @var string
	 */
	public $verbose_service_name = '';

	/**
	 * Constructor
	 *
	 * If you override constructor, call inside that
	 *
	 * <code>
	 * parent::construct();
	 * </code>
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		// Setup name
		if ( empty( $this->verbose_service_name ) ) {
			$this->verbose_service_name = $this->service_name;
		}
		if ( empty( $this->url_prefix ) ) {
			$this->url_prefix = $this->service_name;
		}
		// Sync options
		$this->fill_default_option();
		$this->set_option();
		add_action( Option::UPDATED_ACTION, array( $this, 'set_option' ) );
		// Register actions if enabled.
		if ( $this->enabled ) {
			// Initialize
			$this->init_action();
			// Show profile page
			add_action( 'gianism_user_profile', array( $this, 'profile_connect' ) );
			//Add Hook on Login Form page
			add_action( 'gianism_login_form', array( $this, 'login_form' ), 10, 3 );
			if ( method_exists( $this, 'print_script' ) ) {
				//Add Hook On footer
				add_action( 'admin_print_footer_scripts', array( $this, 'print_script' ) );
				add_action( 'wp_footer', array( $this, 'print_script' ) );
				add_action( 'login_footer', array( $this, 'print_script' ) );
			}
		}
	}

	/**
	 * Initialize
	 *
	 * If some stuff is required, override this.
	 *
	 * @return void
	 */
	protected function init_action() {
		// Do stuff.
	}

	/**
	 * Fill default option
	 */
	final public function fill_default_option() {
		foreach ( $this->option_keys as $key => $default ) {
			$this->option->set_default( $key, $default );
		}
	}

	/**
	 * Setup default option
	 *
	 * @return void
	 */
	final public function set_option() {
		foreach ( $this->option_keys as $key => $default ) {
			if ( isset( $this->option->values[ $key ] ) ) {
				$this->{$key} = $this->option->values[ $key ];
			}
		}
	}

	/**
	 * Return default option keys
	 *
	 * @return array
	 */
	final public function get_keys() {
		return $this->option_keys;
	}

	/**
	 * Get setting path
	 *
	 * If this class is external, override this function.
	 *
	 * @param string $template_dir setting, setup
	 * @return string
	 */
	public function get_admin_template( $template_dir ) {
		$dir = trailingslashit( $this->dir . 'templates/' . basename( $template_dir ) );
		if ( is_dir( $dir ) ) {
			return sprintf( '%s%s.php', $dir, $this->service_name );
		} else {
			return false;
		}
	}


	/**
	 * Detect if user is connected to this service
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	abstract public function is_connected( $user_id );

	/**
	 * Disconnect user from this service
	 *
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	abstract public function disconnect( $user_id );

	/**
	 * This controller return always false
	 *
	 * @param string $mail
	 *
	 * @return bool
	 */
	public function is_pseudo_mail( $mail ) {
		return false;
	}

	/**
	 * Called on redirect endpoint
	 *
	 * @param string $action
	 * @param \WP_Query $wp_query
	 *
	 * @return void
	 */
	public function parse_request( $action, \WP_Query &$wp_query ) {
		nocache_headers();
		$method = 'handle_' . strtolower( str_replace( '-', '_', $action ) );
		if ( ! $this->enabled ) {
			// Not enabled.
			$wp_query->set_404();
			return;
		}
		if ( 'default' != $action && ! $this->input->verify_nonce( $this->input->nonce_action( "{$this->service_name}_{$action}" ) ) ) {
			// If not default, nonce required.
			$this->input->wp_die( $this->_( 'Cheatin\'? Wrong access.' ), 403 );
		}
		if ( 'default' != $action && method_exists( get_called_class(), $method ) ) {
			// Method found, just call
			$this->{$method}( $wp_query );
		} else {
			// Else, call default.
			if ( $specified_action = $this->session->get( 'action' ) ) {
				// If session is set, override with it.
				$action = $specified_action;
			}
			$this->handle_default( $action );
		}
	}

	/**
	 * Handle callback request
	 *
	 * This function must exit at last.
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	abstract protected function handle_default( $action );

	/**
	 * Handle connect
	 *
	 * @param \WP_Query $wp_query
	 */
	protected function handle_connect( \WP_Query $wp_query ) {
		try {
			// Is user logged in?
			if ( ! is_user_logged_in() ) {
				throw new \Exception( $this->_( 'You must be logged in.' ) );
			}
			// Is user connected already?
			if ( $this->is_connected( get_current_user_id() ) ) {
				throw new \Exception( sprintf( $this->_( 'You are already connected with %s' ), $this->verbose_service_name ) );
			}
			// Set redirect URL
			$url = $this->get_api_url( 'connect' );
			if ( ! $url ) {
				throw new \Exception( $this->_( 'Sorry, but failed to connect with API.' ) );
			}
			// Write session
			$this->session->write( 'redirect_to', $this->input->get( 'redirect_to' ) );
			$this->session->write( 'action', 'connect' );
			// OK, let's redirect.
			wp_redirect( $url );
			exit;
		} catch ( \Exception $e ) {
			$this->input->wp_die( $e->getMessage() );
		}
	}

	/**
	 * Handle disconnect
	 *
	 * @param \WP_Query $wp_query
	 */
	protected function handle_disconnect( \WP_Query $wp_query ) {
		try {
			$redirect_url = $this->input->get( 'redirect_to' ) ?: admin_url( 'profile.php' );
			/**
			 * Filter redirect URL
			 */
			$redirect_url = apply_filters( '', $redirect_url, $this->service_name, $wp_query );
			// Is user logged in?
			if ( ! is_user_logged_in() ) {
				throw new \Exception( $this->_( 'You must be logged in.' ) );
			}
			// Has connected
			if ( ! $this->is_connected( get_current_user_id() ) ) {
				throw new \Exception( sprintf( $this->_( 'Your account is not connected with %s' ), $this->verbose_service_name ) );
			}
			// O.K.
			$this->disconnect( get_current_user_id() );
			$this->add_message( sprintf( $this->_( 'Your account is now unlinked from %s.' ), $this->verbose_service_name ) );
			// Redirect
			wp_redirect( $this->filter_redirect( $redirect_url, 'disconnect' ) );
			exit;
		} catch ( \Exception $e ) {
			$this->input->wp_die( $e->getMessage() );
		}
	}

	/**
	 * Make user login
	 *
	 * @param \WP_Query $wp_query
	 */
	public function handle_login( \WP_Query $wp_query ) {
		try {
			// Is user logged in?
			if ( is_user_logged_in() ) {
				throw new \Exception( $this->_( 'You are logged in, ah?' ), 403 );
			}
			// Create URL
			$url = $this->get_api_url( 'login' );
			if ( ! $url ) {
				throw new \Exception( $this->_( 'Sorry, but failed to connect with API.' ) );
			}
			// Write session
			$this->session->write( 'redirect_to', $this->input->get( 'redirect_to' ) );
			$this->session->write( 'action', 'login' );
			// O.K. let's redirect
			wp_redirect( $url );
			exit;
		} catch ( \Exception $e ) {
			$this->input->wp_die( $e->getMessage() );
		}
	}

	/**
	 * Show connect button on profile page
	 *
	 * @param \WP_User $user
	 *
	 * @return void
	 */
	public function profile_connect( \WP_User $user ) {
		$html         = <<<EOS
<tr>
    <th><i class="lsf lsf-{$this->service_name}"></i> {$this->verbose_service_name}</th>
    <td class="wpg-connector {$this->service_name}">
        <p class="description desc-%s"><i class="lsf lsf-%s"></i> %s</p>
        <p class="button-wrap">%s</p>
    </td><!-- .wpg-connector -->
</tr>
EOS;
		$is_connected = $this->is_connected( $user->ID );
		if ( $is_connected ) {
			$class_name = 'connected';
			$icon_class = 'check';
			$message    = $this->connection_message( 'connected' );
			$button     = $this->is_pseudo_mail( $user->user_email ) ? '' : $this->disconnect_button();
		} else {
			$class_name = 'disconnected';
			$icon_class = 'login';
			$message    = $this->connection_message( 'disconnected' );
			$button     = $this->connect_button();
		}
		/**
		 * Filtering message on connection table
		 *
		 * @filter gianism_connect_message
		 * @param string $message
		 * @param string $service
		 * @param bool $is_connected
		 *
		 * @return string
		 */
		$message = apply_filters( 'gianism_connect_message', $message, $this->service_name, $is_connected );
		printf( $html, $class_name, $icon_class, $message, $button );
	}

	/**
	 * Connection message
	 *
	 * Overriding this function, you can
	 * customize connection message
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function connection_message( $context = 'connected' ) {
		switch ( $context ) {
			case 'connected':
				return sprintf( $this->_( 'Your account is already connected with %s account.' ), $this->verbose_service_name );
				break;
			default: // Disconnected
				return sprintf( $this->_( 'Connecting with %1$s, you can login with %2$s via %1$s without password or email address.' ), $this->verbose_service_name, get_bloginfo( 'name' ) );
				break;
		}
	}

	/**
	 * Display login buttons
	 *
	 * @param boolean $is_register
	 * @param string  $redirect_to
	 * @param string  $context
	 *
	 * @return void
	 */
	public function login_form( $is_register = false, $redirect_to = '', $context = '' ) {
		echo $this->login_button( $redirect_to, $is_register, $context );
	}

	/**
	 * Returns redirect to url if set.
	 *
	 * @param string $default
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_redirect_to( $default, $args = array() ) {
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$domain = $_SERVER['SERVER_NAME'];
			if ( preg_match( "/^(https?:\/\/{$domain}|\/)/", $_REQUEST['redirect_to'] ) ) {
				$redirect_to = $_REQUEST['redirect_to'];
				if ( ! empty( $args ) ) {
					$redirect_to .= ( false !== strpos( $redirect_to, '?' ) ) ? '&' : '?';
					$counter = 0;
					foreach ( $args as $key => $val ) {
						if ( 0 == $counter ) {
							$redirect_to .= '&';
						}
						$redirect_to .= $key . '=' . rawurlencode( $val );
						$counter ++;
					}
				}
			} else {
				$redirect_to = $default;
			}
		} else {
			$redirect_to = $default;
		}
		return $this->filter_redirect( $redirect_to, 'default' );
	}

	/**
	 * Returns current action name.
	 *
	 * @return string
	 */
	protected function get_action() {
		return $this->input->request( 'wpg' );
	}

	/**
	 * Return api URL to authenticate
	 *
	 * If you need additional information (ex. token),
	 * use $this->session->write inside.
	 *
	 * <code>
	 * $this->session->write('token', $token);
	 * return $url;
	 * </code>
	 *
	 * @param string $action 'connect', 'login'
	 *
	 * @return string|false URL to redirect
	 * @throws \Exception
	 */
	abstract protected function get_api_url( $action );

	/**
	 * Returns link to filter
	 *
	 * @param string $markup
	 * @param string $href
	 * @param string $text
	 * @param bool $is_register
	 *
	 * @return string
	 */
	public function filter_link( $markup, $href, $text, $is_register = false, $context = '' ) {
		/**
		 * gianism_link_html
		 *
		 * @package Gianism
		 * @since 3.0.4 Add context parameter.
		 * @param string $markup      Final markup.
		 * @param string $href        Link's attribute.
		 * @param string $text        Link text.
		 * @param bool   $is_register Is register form.
		 * @param string $service     Service name. facebook, twitter, etc.
		 * @param string $context     Context. Default empty.
		 */
		$link = apply_filters( 'gianism_link_html', $markup, $href, $text, $is_register, $this->service_name, $context );
		return $link;
	}

	/**
	 * Filter redirect URL
	 *
	 * @param string $url
	 * @param string $context login, connect, disconnect
	 *
	 * @return string
	 */
	protected function filter_redirect( $url, $context ) {
		/**
		 * Filter hook to override redirect url
		 *
		 * @filter gianism_redirect_to
		 * @param string $url     The URL user will be redirect to.
		 * @param string $service 'facebook', 'twitter', and so on.
		 */
		return apply_filters( 'gianism_redirect_to', $url, $this->service, $context );
	}

	/**
	 * Get URL for immediate endpoint.
	 *
	 * @param string $action 'connect', 'disconnect', 'login' or else.
	 * @param string $nonce_key If empty, nonce won't be set.
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_redirect_endpoint( $action = '', $nonce_key = '', $args = array() ) {
		$prefix = empty( $this->url_prefix ) ? $this->service_name : $this->url_prefix;
		$url    = untrailingslashit( home_url( $prefix, ( $this->option->is_ssl_required() ? 'https' : 'http' ) ) ) . '/';
		if ( ! empty( $action ) ) {
			$url .= $action . '/';
		}
		if ( ! empty( $args ) ) {
			$url .= '?' . http_build_query( $args );
		}
		if ( ! empty( $nonce_key ) ) {
			$url = wp_nonce_url( $url, $this->input->nonce_action( $nonce_key ) );
		}

		return $url;
	}

	/**
	 * Detect if current client is smart phone.
	 *
	 * @deprecated 3.0.0
	 * @return boolean
	 */
	protected function is_smartphone() {
		return (boolean) preg_match( '/(iPhone|iPad|Android|MSIEMobile)/', $this->input->server( 'HTTP_USER_AGENT' ) );
	}

	/**
	 * Create common button
	 *
	 * @param string $text        Text to display.
	 * @param string $href        Link's href.
	 * @param bool   $icon_name   Icon name of LSF.
	 * @param array  $class_names Class name for this button.
	 * @param array  $attributes  Attributes for link.
	 * @param string $context     Display context. Default empty.
	 *
	 * @return string
	 */
	public function button( $text, $href, $icon_name = true, array $class_names = [ 'wpg-button' ], array $attributes = [], $context = '' ) {
		// Create icon
		if ( true === $icon_name ) {
			$icon = "<i class=\"lsf lsf-{$this->service_name}\"></i> ";
		} elseif ( is_string( $icon_name ) ) {
			$icon = "<i class=\"lsf lsf-{$icon_name}\"></i> ";
		} else {
			$icon = '';
		}
		$class_attr = implode( ' ', array_map( function ( $attr ) {
			return esc_attr( $attr );
		}, $class_names ) );
		$atts       = [];
		foreach ( $attributes as $key => $value ) {
			switch ( $key ) {
				case 'onclick':
					// Do nothing
					break;
				default:
					$key = 'data-' . $key;
					break;
			}
			$value  = esc_attr( $value );
			$atts[] = "{$key}=\"{$value}\"";
		}
		$atts = ' ' . implode( ' ', $atts );

		switch ( $context ) {
			case 'woo-checkout':
				return __( 'Log in', 'wp-gianism' );
				break;
			default:
				return sprintf(
					'<a href="%2$s" rel="nofollow" class="%4$s"%5$s>%3$s%1$s</a>',
					$text,
					$href,
					$icon,
					$class_attr,
					$atts
				);
				break;
		}
	}

	/**
	 * Show login button
	 *
	 * @param string $redirect
	 * @param bool   $register
	 * @param string $context
	 *
	 * @return string
	 */
	public function login_button( $redirect = '', $register = false, $context = '' ) {
		if ( ! $redirect ) {
			$redirect = admin_url( 'profile.php' );
			/**
			 * gianism_default_redirect_link
			 *
			 * @package Gianism
			 * @since 3.0.4
			 * @param string $redirect Redirect URL.
			 * @param string $service  Service name. e.g. twitter.
			 * @param bool   $register Detect if this is register context.
			 * @param string $context  Context of this button. Default empty string.
			 */
			$redirect = apply_filters( 'gianism_default_redirect_link', $redirect, $this->service_name, $register, $context );
		}
		$url    = $this->get_redirect_endpoint( 'login', $this->service_name . '_login', array(
			'redirect_to' => $redirect,
		) );
		$text   = sprintf( $this->_( 'Log in with %s' ), $this->verbose_service_name );
		$args = [
			'gianism-ga-category' => "gianism/{$this->service_name}",
			'gianism-ga-action'   => 'login',
			'gianism-ga-label'    => sprintf( $this->_( 'Login with %s' ), $this->verbose_service_name ),
		];
		if ( $this->need_confirmation( $context ) ) {
			$args['gianism-confirmation'] = $this->confirmation_message( $context );
			/**
			 * gianism_user_credentails
			 *
			 * @param array  $credentials An array of credentail names. Default, [ 'User ID', 'Email' ]
			 * @param string $service
			 * @return array
			 */
			$credentials = apply_filters( 'gianism_user_credentials', $this->target_credentials( $context ), $this->service_name );
			$args['gianism-target']     = implode( ',', $credentials );
		}
		$button = $this->button( $text, $url, $this->service_name, array( 'wpg-button', 'wpg-button-login' ), $args, $context );

		return $this->filter_link( $button, $url, $text, $register, $context );
	}


	/**
	 * Get connect button
	 *
	 * @param string $redirect_to If not set, profile page's URL
	 *
	 * @return string
	 */
	public function connect_button( $redirect_to = '' ) {
		if ( empty( $redirect_to ) ) {
			$redirect_to = admin_url( 'profile.php' );
		}
		$url  = $this->get_redirect_endpoint( 'connect', $this->service_name . '_connect', array(
			'redirect_to' => $redirect_to,
		) );
		$args = array(
			'gianism-ga-category' => "gianism/{$this->service_name}",
			'gianism-ga-action'   => 'connect',
			'gianism-ga-label'    => sprintf( $this->_( 'Connect %s' ), $this->verbose_service_name ),
		);

		return $this->button( $this->_( 'Connect' ), $url, 'link', array( 'wpg-button', 'connect' ), $args );
	}

	/**
	 * Get disconnect button
	 *
	 * @param string $redirect_to If not set, profile page's URL
	 *
	 * @return string
	 */
	public function disconnect_button( $redirect_to = '' ) {
		if ( empty( $redirect_to ) ) {
			$redirect_to = admin_url( 'profile.php' );
		}
		$url  = $this->get_redirect_endpoint( 'disconnect', $this->service_name . '_disconnect', array(
			'redirect_to' => $redirect_to,
		) );
		$args = array(
			'gianism-ga-category' => "gianism/{$this->service_name}",
			'gianism-ga-action'   => 'disconnect',
			'gianism-ga-label'    => sprintf( $this->_( 'Disconnect %s' ), $this->verbose_service_name ),
			'gianism-confirm'     => sprintf( $this->_( 'You really disconnect from %s? If so, please be sure about your credential(email, passowrd), or else you might not be able to login again.' ), $this->verbose_service_name ),
		);

		return $this->button( $this->_( 'Disconnect' ), $url, 'logout', array( 'wpg-button', 'disconnect' ), $args );
	}

	/**
	 * Fires connect hook
	 *
	 * @param int $user_id
	 * @param mixed $data
	 * @param bool $on_creation
	 */
	protected function hook_connect( $user_id, $data, $on_creation = false ) {
		/**
		 * Fires when user account is disconnected from SNS account.
		 *
		 * @action wpg_disconnect
		 *
		 * @param int $user_id
		 * @param mixed $data
		 * @param string $service_name
		 * @param bool $on_creation
		 */
		do_action( 'wpg_connect', $user_id, $data, $this->service_name, (bool) $on_creation );
	}

	/**
	 * Fires disconnect hook
	 *
	 * @param $user_id
	 */
	protected function hook_disconnect( $user_id ) {
		/**
		 * Fires when user account is disconnected from SNS account.
		 *
		 * @action wpg_disconnect
		 *
		 * @param int $user_id
		 * @param string $service_name
		 */
		do_action( 'wpg_disconnect', $user_id, $this->service_name );
	}

	/**
	 * Use's password is automatically generated
	 *
	 * @param int $user_id
	 */
	protected function user_password_unknown( $user_id ) {
		update_user_meta( $user_id, '_wpg_unknown_password', true );
	}

	/**
	 * Create valid username from email address
	 *
	 * @param string $email
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function valid_username_from_mail( $email ) {
		$suffix = array_shift( explode( '@', $email ) );
		if ( ! username_exists( $suffix ) ) {
			return $suffix;
		}
		$service_domain = $suffix . '@' . $this->service_name;
		if ( ! username_exists( $service_domain ) ) {
			return $service_domain;
		}
		$original_domain = $suffix . '@' . $_SERVER['SERVER_NAME'];
		if ( ! username_exists( $original_domain ) ) {
			return $original_domain;
		}
		throw new \Exception( $this->_( 'Sorry, but cannot create valid user name.' ) );
	}

	/**
	 * Returns API error string
	 *
	 * @return string
	 */
	protected function api_error_string() {
		return sprintf( $this->_( '%s API returns error.' ), $this->verbose_service_name );
	}

	/**
	 * Message account duplication
	 *
	 * @return string
	 */
	protected function duplicate_account_string() {
		return sprintf( $this->_( 'This %s account is already connected with others.' ), $this->verbose_service_name );
	}

	/**
	 * Add welcome message
	 *
	 * @param string $who
	 */
	protected function welcome( $who ) {
		$this->add_message( sprintf( $this->_( 'Welcome, %s!' ), $who ) );
	}

	/**
	 * Add error message
	 *
	 * @param string $message
	 */
	protected function auth_fail( $message ) {
		$this->add_message( $this->_( 'Oops, Failed to Authenticate.' ) . ' ' . $message, true );
	}

	/**
	 * Add error message
	 *
	 * @return string
	 */
	protected function mail_fail_string() {
		return $this->_( 'Cannot retrieve email address.' );
	}

	/**
	 * Registration error string
	 *
	 * @return string
	 */
	protected function registration_error_string() {
		return $this->_( 'Cannot register. Please try again later.' );
	}

	/**
	 * Kill wrong access
	 */
	protected function kill_wrong_access() {
		$this->input->wp_die( sprintf( $this->_( 'Sorry, but wrong access. Please go back to <a href="%s">%s</a>.' ), home_url( '/', 'http' ), get_bloginfo( 'name' ) ), 500, false );
	}

	/**
	 * Test if can register.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function test_user_can_register() {
		if ( ! $this->user_can_register() ) {
			throw new \Exception( sprintf( $this->_( 'Sorry, but registration via %s is not allowed.' ), $this->verbose_service_name ) );
		}

		return true;
	}

	/**
	 * Detect if user can register or not
	 *
	 * @return bool
	 */
	public function user_can_register() {
		/**
		 * Whether if user can register for service
		 *
		 * @filter gianism_user_can_register
		 * @param bool   $can_register
		 * @param string $service
		 *
		 * @return bool
		 */
		return (bool) apply_filters( 'gianism_user_can_register', $this->option->user_can_register(), $this->service_name );
	}

	/**
	 * Get Request
	 *
	 * @param string $endpoint
	 * @param string|array $request
	 * @param string $method If x-www-form-urlencoded required, pass array or else, pass query string.
	 * @param bool $json if this request is JSON
	 * @param array $additional_headers Additional headers.
	 *
	 * @return array|\stdClass|bool|null
	 */
	protected function get_response( $endpoint, $request = '', $method = 'POST', $json = false, array $additional_headers = array() ) {
		$method = strtoupper( $method );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_TIMEOUT, 20 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		if ( $json ) {
			$additional_headers[] = 'Content-Type: application/json';
		}
		switch ( $method ) {
			case 'PUT':
			case 'PATCH':
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
			case 'POST':
				curl_setopt( $ch, CURLOPT_POST, true );
				if ( is_array( $request ) ) {
					$additional_headers = array_merge( $additional_headers, array( 'Content-Type: application/x-www-form-urlencoded' ) );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $request ) );
				} else {
					curl_setopt( $ch, CURLOPT_POSTFIELDS, $request );
				}
				break;
			case 'DELETE':
				curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
			case 'GET':
				$args = array();
				if ( is_array( $request ) ) {
					$request = http_build_query( $request );
				}
				if ( ! empty( $request ) ) {
					$endpoint .= '?' . $request;
				}
				break;
			default:
				return array();
				break;
		}
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		if ( ! empty( $additional_headers ) ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $additional_headers );
		}
		$response = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $response );

	}

	/**
	 * Does this service needs confirmation?
	 *
	 * @param string $context 'login' or 'register'
	 * @return bool
	 */
	public function need_confirmation( $context = 'login' ) {
		return false;
	}

	/**
	 * Get confirmation message
	 *
	 * @param string $context
	 * @return string
	 */
	public function confirmation_message( $context = 'login' ) {
		$message = sprintf(
			// translators: %1$s blog name, %2$s is service name.
			__( '%1$s get your information below from %2$s. Please proceed with your agreement.', 'wp-gianism' ),
			get_bloginfo( 'name' ),
			$this->verbose_service_name
		);
		/**
		 * gianism_confirmation_message
		 *
		 * @param string $message
		 * @param string $service
		 * @param string $context
		 * @return string
		 */
		return apply_filters( 'gianism_confirmation_message', $message, $this->service_name, $context );
	}

	/**
	 * Information to retrieve.
	 *
	 * @param string $context
	 * @return array
	 */
	public function target_credentials( $context = 'login' ) {
		return [
			'id'      => sprintf( __( '%s User ID', 'wp-gianism' ), $this->verbose_service_name ),
			'profile' => __( 'Profile', 'wp-gianism' ),
			'email'   => __( 'Email', 'wp-gianism' ),
		];
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
			case 'service_name':
				$segments = explode( '\\', get_called_class() );

				return strtolower( $segments[ count( $segments ) - 1 ] );
				break;
			case 'enabled':
				return $this->option->is_enabled( $this->service_name );
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}
}
