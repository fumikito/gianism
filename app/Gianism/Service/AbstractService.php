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
#[\AllowDynamicProperties]
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
		parent::__construct( $argument );
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
			$connect_priority = apply_filters( 'gianism_service_priority', 10, $this->service_name, 'connect' );
			add_action( 'gianism_user_profile', array( $this, 'profile_connect' ), $connect_priority );
			// Add Hook on Login Form page
			$login_priority = apply_filters( 'gianism_service_priority', 10, $this->service_name, 'login' );
			add_action( 'gianism_login_form', array( $this, 'login_form' ), $login_priority, 3 );
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
		if ( 'default' !== $action && ! $this->input->verify_nonce( $this->input->nonce_action( "{$this->service_name}_{$action}" ) ) ) {
			// If not default, nonce required.
			$this->input->wp_die( __( 'This request seems to be a wrong access. Please try again.', 'wp-gianism' ), 403 );
		}
		if ( 'default' !== $action && method_exists( get_called_class(), $method ) ) {
			// Method found, just call
			$this->{$method}( $wp_query );
		} else {
			// Else, call default.
			$specified_action = $this->session->get( 'action' );
			if ( $specified_action ) {
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
				throw new \Exception( __( 'You must be logged in.', 'wp-gianism' ) );
			}
			// Is user connected already?
			if ( $this->is_connected( get_current_user_id() ) ) {
				// translators: %s is service name.
				throw new \Exception( sprintf( __( 'You are already connected with %s', 'wp-gianism' ), $this->verbose_service_name ) );
			}
			// Set redirect URL
			$url = $this->get_api_url( 'connect' );
			if ( ! $url ) {
				throw new \Exception( __( 'Sorry, but failed to connect with API.', 'wp-gianism' ) );
			}
			// Write session
			$this->session->write(
				[
					'redirect_to' => $this->input->get( 'redirect_to' ),
					'action'      => 'connect',
				]
			);
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
				throw new \Exception( __( 'You must be logged in.', 'wp-gianism' ) );
			}
			// Has connection?
			if ( ! $this->is_connected( get_current_user_id() ) ) {
				// translators: %s is service name.
				throw new \Exception( sprintf( __( 'Your account is not connected with %s', 'wp-gianism' ), $this->verbose_service_name ) );
			}
			// O.K.
			$this->disconnect( get_current_user_id() );
			// translators: %s is service name.
			$this->add_message( sprintf( __( 'Your account is now unlinked from %s.', 'wp-gianism' ), $this->verbose_service_name ) );
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
				throw new \Exception( __( 'You are already logged in.', 'wp-gianism' ), 403 );
			}
			// Create URL
			$url = $this->get_api_url( 'login' );
			if ( ! $url ) {
				throw new \Exception( __( 'Sorry, but failed to connect with API.', 'wp-gianism' ) );
			}
			// Write session
			$session = [
				'redirect_to' => $this->input->get( 'redirect_to' ),
				'action'      => 'login',
			];
			$blog_id = $this->input->get( 'blog_id' );
			if ( $blog_id ) {
				// Store blog id if blog id is specified.
				$session['blog_id'] = (int) $blog_id;
			}
			$this->session->write( $session );
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
				// translators: %s is service name.
				return sprintf( __( 'Your account is already connected with %s account.', 'wp-gianism' ), $this->verbose_service_name );
				break;
			default: // Disconnected
				// translators: %1$s is service name, %2$s is site name.
				return sprintf( __( 'Connecting with %1$s, you can login with %2$s via %1$s without password or email address.', 'wp-gianism' ), $this->verbose_service_name, get_bloginfo( 'name' ) );
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
	 * @param string $default_url
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_redirect_to( $default_url, $args = array() ) {
		$redirect_to = $default_url;
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$domain = $_SERVER['SERVER_NAME'];
			if ( preg_match( "/^(https?:\/\/{$domain}|\/)/", $_REQUEST['redirect_to'] ) ) {
				$redirect_to = $_REQUEST['redirect_to'];
				if ( ! empty( $args ) ) {
					$redirect_to = add_query_arg( $args, $redirect_to );
				}
			}
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
		 * @param string $context The context of this redirect.
		 */
		return apply_filters( 'gianism_redirect_to', $url, $this->service_name, $context );
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
		$prefix     = empty( $this->url_prefix ) ? $this->service_name : $this->url_prefix;
		$pre_prefix = $this->option->get_formatted_prefix();
		if ( $pre_prefix ) {
			$prefix = $pre_prefix . '/' . $prefix;
		}
		$scheme   = $this->option->is_ssl_required() ? 'https' : 'http';
		$base_url = $this->option->is_network_activated() ? get_site_url( $this->option->get_parent_blog_id(), '', $scheme ) : home_url( '', $scheme );
		$url      = trailingslashit( untrailingslashit( $base_url ) . '/' . ltrim( $prefix, '/' ) );
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
		return (bool) preg_match( '/(iPhone|iPad|Android|MSIEMobile)/', $this->input->server( 'HTTP_USER_AGENT' ) );
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
		// If SVG exists, use it for public button style.
		if ( in_array( 'wpg-guideline-button', $class_names, true ) ) {
			$file = $this->svg_path();
			if ( $file ) {
				$icon = $this->url . '/assets/img/brands/' . $file;
				$icon = sprintf( '<img src="%s" alt="" class="wpg-icon" />', esc_url( $icon ) );
			}
		}
		$class_attr = implode(
			' ',
			array_map(
				function ( $attr ) {
					return esc_attr( $attr );
				},
				$class_names
			)
		);
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
	 * Login label
	 *
	 * @param bool $register
	 * @param string $context
	 *
	 * @return string
	 */
	protected function login_label( $register = false, $context = '' ) {
		// translators: %s is service name.
		return sprintf( __( 'Log in with %s', 'wp-gianism' ), $this->verbose_service_name );
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
		$url_args = [
			'redirect_to' => $redirect,
		];
		if ( $this->network->is_child_site() ) {
			$url_args['blog_id'] = get_current_blog_id();
		}
		$url  = $this->get_redirect_endpoint( 'login', $this->service_name . '_login', $url_args );
		$text = apply_filters( 'gianism_login_button_label', $this->login_label(), $register, $context );

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
			$credentials            = apply_filters( 'gianism_user_credentials', $this->target_credentials( $context ), $this->service_name );
			$args['gianism-target'] = implode( ',', $credentials );
		}
		// Build class
		switch ( $this->option->button_type ) {
			case 2:
				$class_names = [ 'wpg-guideline-button' ];
				break;
			default:
				$class_names = [ 'wpg-button', 'wpg-button-login' ];
				break;
		}
		$class_names[] = $this->service_name;
		$button        = $this->button( $text, $url, $this->service_name, $class_names, $args, $context );

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
			$redirect_to = apply_filters( 'gianism_default_redirect_link', admin_url( 'profile.php' ), $this->service_name, false, 'connect' );
		}
		$url  = $this->get_redirect_endpoint(
			'connect',
			$this->service_name . '_connect',
			array(
				'redirect_to' => $redirect_to,
			)
		);
		$args = array(
			'gianism-ga-category' => "gianism/{$this->service_name}",
			'gianism-ga-action'   => 'connect',
			// translators: %s is service name.
			'gianism-ga-label'    => sprintf( __( 'Connect %s', 'wp-gianism' ), $this->verbose_service_name ),
		);

		return $this->button( __( 'Connect', 'wp-gianism' ), $url, 'link', array( 'wpg-button', 'connect' ), $args );
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
			$redirect_to = apply_filters( 'gianism_default_redirect_link', admin_url( 'profile.php' ), $this->service_name, false, 'disconnect' );
		}
		$url  = $this->get_redirect_endpoint(
			'disconnect',
			$this->service_name . '_disconnect',
			array(
				'redirect_to' => $redirect_to,
			)
		);
		$args = array(
			'gianism-ga-category' => "gianism/{$this->service_name}",
			'gianism-ga-action'   => 'disconnect',
			// translators: %s is service name.
			'gianism-ga-label'    => sprintf( __( 'Disconnect %s', 'wp-gianism' ), $this->verbose_service_name ),
			// translators: %s is service name.
			'gianism-confirm'     => sprintf( __( 'You really disconnect from %s? If so, please be sure about your credential(email, password), or else you might not be able to login again.', 'wp-gianism' ), $this->verbose_service_name ),
		);

		return $this->button( __( 'Disconnect', 'wp-gianism' ), $url, 'logout', array( 'wpg-button', 'disconnect' ), $args );
	}

	/**
	 * Set login cookie.
	 *
	 * @param int $user_id
	 */
	protected function set_auth_cookie( $user_id ) {
		/**
		 * Fires just before setting login cookie.
		 *
		 * @param int    $user_id
		 * @param string $service_name
		 */
		do_action( 'gianism_before_set_login_cookie', $user_id, $this->service_name );
		// Should remember?
		$remember = apply_filters( 'gianism_should_remember_cookie', true, $user_id, $this->service_name );
		wp_set_auth_cookie( $user_id, $remember );
		/**
		 * Fires just after setting login cookie.
		 *
		 * @param int    $user_id
		 * @param string $service_name
		 */
		do_action( 'gianism_after_set_login_cookie', $user_id, $this->service_name );
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
		 * Fires when user account is connected from SNS account.
		 *
		 * @action wpg_connect
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
		$emails = explode( '@', $email );
		$suffix = array_shift( $emails );
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
		throw new \Exception( __( 'Cannot create valid user name.', 'wp-gianism' ) );
	}

	/**
	 * Returns API error string
	 *
	 * @return string
	 */
	protected function api_error_string() {
		// translators: %s is service name.
		return sprintf( __( '%s API returns error.', 'wp-gianism' ), $this->verbose_service_name );
	}

	/**
	 * Message account duplication
	 *
	 * @return string
	 */
	protected function duplicate_account_string() {
		// translators: %s is service name.
		return sprintf( __( 'This %s account is already connected with others.', 'wp-gianism' ), $this->verbose_service_name );
	}

	/**
	 * Add welcome message
	 *
	 * @param string $who
	 */
	protected function welcome( $who ) {
		// translators: %s is user name.
		$this->add_message( sprintf( __( 'Welcome, %s!', 'wp-gianism' ), $who ) );
	}

	/**
	 * Add error message
	 *
	 * @param string $message
	 */
	protected function auth_fail( $message ) {
		$this->add_message( __( 'Sorry, but failed to Authenticate. Please try again.', 'wp-gianism' ) . ' ' . $message, true );
	}

	/**
	 * Add error message
	 *
	 * @return string
	 */
	protected function mail_fail_string() {
		return __( 'Cannot retrieve email address.', 'wp-gianism' );
	}

	/**
	 * Registration error string
	 *
	 * @return string
	 */
	protected function registration_error_string() {
		return __( 'Cannot register. Please try again later.', 'wp-gianism' );
	}

	/**
	 * Kill wrong access
	 */
	protected function kill_wrong_access() {
		// translators: %1$s URL, %2$s Blog name.
		$this->input->wp_die( sprintf( __( 'Sorry, but wrong access. Please go back to <a href="%1$s">%2$s</a>.', 'wp-gianism' ), home_url( '/' ), get_bloginfo( 'name' ) ), 500, false );
	}

	/**
	 * Test if can register.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function test_user_can_register() {
		if ( ! $this->user_can_register() ) {
			// translators: %s is service name.
			throw new \Exception( sprintf( __( 'Registration via %s is not allowed.', 'wp-gianism' ), $this->verbose_service_name ) );
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
		$ch     = curl_init();
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
				// Other, same as POST.
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
				// Other, same as GET.
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
			__( '%1$s gets your information below from %2$s. Please proceed with your agreement.', 'wp-gianism' ),
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
			// translators: %s is serfice name.
			'id'      => sprintf( __( '%s User ID', 'wp-gianism' ), $this->verbose_service_name ),
			'profile' => __( 'Profile', 'wp-gianism' ),
			'email'   => __( 'Email', 'wp-gianism' ),
		];
	}

	/**
	 * Get SVG path
	 *
	 * @return string
	 */
	protected function svg_path() {
		return '';
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
			case 'enabled':
				return $this->option->is_enabled( $this->service_name );
			default:
				return parent::__get( $name );
		}
	}
}
