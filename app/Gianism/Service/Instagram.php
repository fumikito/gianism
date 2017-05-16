<?php

namespace Gianism\Service;

/**
 * Instagram
 *
 * @since 3.0.0
 * @package Gianism
 */
class Instagram extends NoMailService {

	public $verbose_service_name = 'Instagram';

	/**
	 * URL prefix
	 *
	 * @var string
	 */
	public $url_prefix = 'instagram-auth';

	/**
	 * @var bool
	 */
	public $instagram_enabled = false;

	/**
	 * @var string
	 */
	public $instagram_client_id = '';

	/**
	 * @var string
	 */
	public $instagram_client_secret = '';

	/**
	 * User ID for instagram
	 *
	 * @var string
	 */
	public $umeta_id = '_wpg_instagram_id';

	/**
	 * User's access token
	 *
	 * @var string
	 */
	public $umeta_token = '_wpg_instagram_token';

	/**
	 * User's screen name
	 *
	 * @var string
	 */
	public $umeta_screen_name = '_wpg_instagram_name';

	/**
	 * Pseudo email address
	 *
	 * @var string
	 */
	protected $pseudo_domain = 'pseudo.instagram.com';

	/**
	 * @var array
	 */
	protected $option_keys = [
		'instagram_enabled' => false,
	    'instagram_client_id' => '',
		'instagram_client_secret' => '',
	];

	/**
	 * Instagram constructor.
	 *
	 * @param array $argument
	 */
	public function __construct( array $argument ) {
		parent::__construct( $argument );
		add_filter( 'gianism_filter_service_prefix', function( $service ) {
			if ( $service == $this->url_prefix ) {
				$service = 'instagram';
			}
			return $service;
		} );
	}



	/**
	 * Returns whether user has twitter account
	 *
	 * @param int $user_id
	 *
	 * @return boolean
	 */
	public function is_connected( $user_id ) {
		return (boolean) get_user_meta( $user_id, $this->umeta_id, true );
	}

	/**
	 * Disconnect user from this service
	 *
	 * @param int $user_id
	 *
	 * @return mixed|void
	 */
	public function disconnect( $user_id ) {
		delete_user_meta( $user_id, $this->umeta_id );
		delete_user_meta( $user_id, $this->umeta_screen_name );
		delete_user_meta( $user_id, $this->umeta_token );
	}

	/**
	 * Handle callback
	 *
	 * @param string $action
	 */
	protected function handle_default( $action ) {
		// Get common values
		$redirect_url = $this->session->get( 'redirect_to' );
		switch ( $action ) {
			case 'login':
				try {
					$token = $this->validate_code();
					$user = $this->request_api( $token, '/users/self' );
					$user_id = $this->get_meta_owner( $this->umeta_id, $user->data->id );
					if ( ! $user_id ) {
						$this->test_user_can_register();
						// Make pseudo mail
						$email = $user->data->username . '@' . $this->pseudo_domain;
						// Make username from screen name
						$user_name = ( ! username_exists( '@' . $user->data->username ) ) ? '@' . $user->data->username : $email;
						/**
						 * @see Facebook
						 */
						$user_name = apply_filters( 'gianism_register_name', $user_name, $this->service, $token );
						// Create user
						$user_id = wp_create_user( $user_name, wp_generate_password(), $email );
						if ( is_wp_error( $user_id ) ) {
							throw new \Exception( $this->registration_error_string() );
						}
						// Save user meta
						foreach ( [
							$this->umeta_id          => $user->data->id,
							$this->umeta_screen_name => $user->data->username,
							$this->umeta_token       => $token,
							'nickname'               => $user->data->full_name,
							'description'            => $user->data->bio,
						] as $key => $value ) {
							update_user_meta( $user_id, $key, $value );
						}
						// Save
						$this->db->update(
							$this->db->users,
							[
								'display_name' => $user_name,
								'user_url'     => sprintf( 'https://www.instagram.com/%s/', $user->data->username ),
							],
							[
								'ID' => $user_id,
							],
							[ '%s', '%s' ],
							[ '%d' ]
						);
						// Password is unknown
						$this->user_password_unknown( $user_id );
						$this->hook_connect( $user_id, [
							'data'  => $user->data,
						    'token' => $token,
						], true );
						// Let user follow me
						$this->welcome( '@' . $user_name );
					}
					// Let user log in.
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
			case 'connect':
				// Connection finished. Let's redirect.
				if ( ! $redirect_url ) {
					$redirect_url = admin_url( 'profile.php' );
				}
				try {
					if ( ! is_user_logged_in() ) {
						throw new \Exception( $this->_( 'You must be logged in.' ) );
					}
					$token = $this->validate_code();
					$user = $this->request_api( $token, '/users/self' );
					$id_owner = $this->get_meta_owner( $this->umeta_id, $user->data->id );
					if ( $id_owner && ( get_current_user_id() != $id_owner ) ) {
						throw new \Exception( $this->duplicate_account_string() );
					}
					// O.K.
					foreach ( [
						$this->umeta_id          => $user->data->id,
						$this->umeta_screen_name => $user->data->username,
						$this->umeta_token       => $token,
					] as $key => $value ) {
						update_user_meta( get_current_user_id(), $key, $value );
					}
					$this->hook_connect( get_current_user_id(), [
						'data'  => $user->data,
						'token' => $token,
					], false );
					$this->welcome( $user->data->username );
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect-failure' );
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
				] );
				$this->input->wp_die( sprintf( $this->_( 'Sorry, but wrong access. Please go back to <a href="%s">%s</a>.' ), home_url( '/' ), get_bloginfo( 'name' ) ), 500, false );
				break;
		}
	}

	/**
	 * Returns API endpoint
	 *
	 * @param string $action
	 *
	 * @return false|string
	 * @throws \Exception
	 */
	protected function get_api_url( $action ) {
		$scopes = [ 'basic' ];
		/**
		 * Filter scopes
		 *
		 * @filter gianism_instagram_scopes
		 * @since 3.0.0
		 * @see https://www.instagram.com/developer/authorization/
		 * @param array  $scopes Array of scopes. Default is 'basic'.
		 * @param string $action 'login' or 'connect'
		 * @return array
		 */
		$scopes = apply_filters( 'gianism_instagram_scopes', $scopes, $action );
		$authorize_url = 'https://api.instagram.com/oauth/authorize/?';
		$authorize_url .= http_build_query( [
			'client_id'     => $this->instagram_client_id,
		    'redirect_uri'  => $this->get_redirect_endpoint(),
		    'scope'         => implode( '+', $scopes ),
		    'response_type' => 'code',
		] );
		return $authorize_url;
	}

	/**
	 * Get access token
	 *
	 * @throws \Exception
	 * @return string Access token
	 */
	public function validate_code() {
		if ( ! ( $code = $this->input->get( 'code' ) ) ) {
			$err = $this->input->get( 'error' );
			$msg = $this->input->get( 'error_description' );
			if ( ! ( $err && $msg ) ) {
				$err = 'api_error';
				$msg = $this->api_error_string();
			}
			throw new \Exception( $msg, $err );
		}
		$response = $this->get_response( 'https://api.instagram.com/oauth/access_token', [
			'client_id' => $this->instagram_client_id,
		    'client_secret' => $this->instagram_client_secret,
		    'grant_type' => 'authorization_code',
		    'redirect_uri' => $this->get_redirect_endpoint(),
		    'code' => $code,
		] );
		if ( ! ( isset( $response->access_token ) && $response->access_token ) ) {
			throw new \Exception( $this->api_error_string() );
		}
		return $response->access_token;
	}

	/**
	 * Request to REST API of Instagram
	 *
	 * @param string $token    Access token
	 * @param string $endpoint Request endpoint e.g. 'user/self'
	 * @param array  $params   Request parameters
	 * @param string $method   Request method. 'GET', 'POST', etc.
	 * @param array $headers   Additional headers
	 *
	 * @return \stdClass|array
	 * @throws \Exception
	 */
	public function request_api( $token, $endpoint, $params = [], $method = 'GET', $headers = [] ) {
		$headers = array_merge( [ 'Accept: application/json' ], $headers );
		$endpoint = 'https://api.instagram.com/v1/' . trailingslashit( ltrim( $endpoint, '/' ) );
		$params['access_token'] = $token;
		$response = $this->get_response( $endpoint, $params, $method, false, $headers );
		$code = 400;
		if ( isset( $response->meta->code ) ) {
			$code = $response->meta->code;
		}
		if ( 200 != $response->meta->code ) {
			$msg  = $this->api_error_string();
			if ( isset( $response->meta->error_message ) ) {
				$msg  = $response->meta->error_message;
			}
			throw new \Exception( $msg, $code );
		}
		return $response;
	}

}
