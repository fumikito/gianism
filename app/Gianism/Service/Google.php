<?php

namespace Gianism\Service;

use Gianism\Cron\Daily;

/**
 * Google client
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 *
 * @property-read \Google_Client $api
 * @property-read \Google_Service_Plus $plus
 *
 */
class Google extends AbstractService {

	/**
	 * URL prefix to prepend
	 *
	 * @var string
	 */
	public $url_prefix = 'google-auth';

	/**
	 * Verbose service name
	 *
	 * @var string
	 */
	public $verbose_service_name = 'Google';

	/**
	 * @var bool
	 */
	public $ggl_enabled = false;

	/**
	 * @var string
	 */
	public $ggl_consumer_key = '';

	/**
	 * @var string
	 */
	public $ggl_consumer_secret = '';

	/**
	 * @var bool
	 */
	public $ggl_use_analytics = true;

	/**
	 * @var string
	 */
	public $umeta_account = '_wpg_google_account';

	/**
	 * @var string
	 */
	public $umeta_plus = '_wpg_google_plus_id';

	/**
	 * Oauth client store
	 *
	 * @var \Google_Client
	 */
	private $_api = null;

	/**
	 * Plus client
	 *
	 * @var \Google_Service_Plus
	 */
	private $_plus = null;

	/**
	 * Option to retrieve
	 *
	 * @var array
	 */
	protected $option_keys = [
		'ggl_enabled'         => false,
		'ggl_consumer_key'    => '',
		'ggl_consumer_secret' => '',
		'ggl_use_analytics'   => true,
	];

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		// Filter rewrite name
		add_filter( 'gianism_filter_service_prefix', function( $prefix ) {
			if ( 'google-auth' == $prefix ) {
				$prefix = 'google';
			}
			return $prefix;
		} );
	}

	/**
	 * Handle callback request
	 *
	 * @global \wpdb $wpdb
	 *
	 * @param string $action
	 *
	 * @return mixed
	 */
	protected function handle_default( $action ) {
		// Get common values
		$redirect_url = $this->session->get( 'redirect_to' );
		$code         = $this->input->request( 'code' );
		switch ( $action ) {
			case 'login': // Let user login
				try {
					// Authenticate and get token
					$token   = $this->api->authenticate( $code );
					$profile = $this->get_profile();
					// Check email validity
					if ( ! isset( $profile['email'] ) || ! is_email( $profile['email'] ) ) {
						throw new \Exception( $this->mail_fail_string() );
					}
					$email   = $profile['email'];
					$plus_id = isset( $profile['id'] ) ? $profile['id'] : 0;
					$user_id = $this->get_meta_owner( $this->umeta_account, $email );
					if ( ! $user_id ) {
						// Test
						$this->test_user_can_register();
						// Check email
						if ( email_exists( $email ) ) {
							throw new \Exception( $this->duplicate_account_string() );
						}
						// Create user name
						$user_name = $this->valid_username_from_mail( $email );
						/**
						 * @see Facebook
						 */
						$user_name = apply_filters( 'gianism_register_name', $user_name, $this->service, $profile );
						// Create user
						$user_id = wp_create_user( $user_name, wp_generate_password(), $email );
						if ( is_wp_error( $user_id ) ) {
							throw new \Exception( $this->registration_error_string() );
						}
						// Update user meta
						update_user_meta( $user_id, $this->umeta_account, $email );
						if ( $plus_id ) {
							update_user_meta( $user_id, $this->umeta_plus, $plus_id );
						}
						update_user_meta( $user_id, 'nickname', $profile['name'] );
						$this->db->update(
							$this->db->users,
							array(
								'display_name' => $profile['name'],
							),
							array(
								'ID' => $user_id,
							),
							array( '%s' ),
							array( '%d' )
						);
						$this->user_password_unknown( $user_id );
						$this->hook_connect( $user_id, $profile, true );
						$this->welcome( $profile['name'] );
					}
					// Make user logged in
					wp_set_auth_cookie( $user_id, true );
					$redirect_url = $this->filter_redirect( $redirect_url, 'login' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = wp_login_url( $redirect_url, true );
					$redirect_url = $this->filter_redirect( $redirect_url, 'login-failure' );
				}
				wp_redirect( $redirect_url );
				exit;
				break;
			case 'connect': // Connect account
				// Connection finished. Let's redirect.
				if ( ! $redirect_url ) {
					$redirect_url = admin_url( 'profile.php' );
				}
				try {
					// Authenticate and get token
					$token   = $this->api->authenticate( $code );
					$profile = $this->get_profile();
					// Check email validity
					if ( ! isset( $profile['email'] ) || ! is_email( $profile['email'] ) ) {
						throw new \Exception( $this->mail_fail_string() );
					}
					// Check if other user has these as meta_value
					$email       = $profile['email'];
					$email_owner = $this->get_meta_owner( $this->umeta_account, $email );
					if ( $email_owner && ( get_current_user_id() != $email_owner ) ) {
						throw new \Exception( $this->duplicate_account_string() );
					}
					// Now let's save user data
					update_user_meta( get_current_user_id(), $this->umeta_account, $email );
					if ( isset( $profile['id'] ) && $profile['id'] ) {
						update_user_meta( get_current_user_id(), $this->umeta_plus, $profile['id'] );
					}
					// Fires hook
					$this->hook_connect( get_current_user_id(), $profile );
					// Save message
					$this->welcome( $profile['name'] );
					// Apply filter
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					// Apply filter
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect-failure' );
				}
				// Connection finished. Let's redirect.
				if ( ! $redirect_url ) {
					$redirect_url = admin_url( 'profile.php' );
				}
				wp_redirect( $redirect_url );
				exit;
				break;
			default:
				/**
				 * @see Facebook
				 */
				do_action( 'gianism_extra_action', $this->service_name, $action, [
					'redirect_to' => $redirect_url,
				    'code'        => $code,
				] );
				$this->input->wp_die( sprintf( $this->_( 'Sorry, but wrong access. Please go back to <a href="%s">%s</a>.' ), home_url( '/' ), get_bloginfo( 'name' ) ), 500, false );
				break;
		}
	}


	/**
	 * Returns Profile
	 *
	 * @return \Google_Service_Oauth2_Userinfoplus
	 */
	private function get_profile() {
		$oauth = new \Google_Service_Oauth2( $this->api );
		return $oauth->userinfo->get();
	}

	/**
	 * Detect if user is connected to this service
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function is_connected( $user_id ) {
		return (boolean) get_user_meta( $user_id, $this->umeta_account, true );
	}

	/**
	 * Disconnect user from this service
	 *
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	public function disconnect( $user_id ) {
		delete_user_meta( $user_id, $this->umeta_account );
		delete_user_meta( $user_id, $this->umeta_plus );
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
	protected function get_api_url( $action ) {
		switch ( $action ) {
			case 'login':
			case 'connect':
				return $this->api->createAuthUrl();
				break;
			default:
				return false;
				break;
		}
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
			case 'api':
				if ( ! $this->_api ) {
					$this->_api = new \Google_Client();
					$this->_api->setApplicationName( get_bloginfo( 'name' ) );
					$this->_api->setClientId( $this->ggl_consumer_key );
					$this->_api->setClientSecret( $this->ggl_consumer_secret );
					$this->_api->setRedirectUri( $this->get_redirect_endpoint() );
					$this->_api->setApprovalPrompt( 'auto' );
					$this->_api->setAccessType( 'online' );
					$this->_api->setScopes( array(
						'https://www.googleapis.com/auth/userinfo.profile',
						'https://www.googleapis.com/auth/userinfo.email',
						'https://www.googleapis.com/auth/plus.me',
					) );
				}
				return $this->_api;
				break;
			case 'plus':
				if ( ! $this->_plus ) {
					$this->_plus = new \Google_Service_Plus( $this->api );
				}

				return $this->_plus;
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}
}
