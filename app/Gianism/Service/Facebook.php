<?php

namespace Gianism\Service;
use Facebook\GraphNodes\GraphUser;
use Facebook\SignedRequest;
use Gianism\Helper\FacebookCookiePersistentDataHandler;

/**
 * Description of facebook_controller
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 * @property-read \Facebook\Facebook|\WP_Error $api Facebook object
 * @property-read \Facebook\Facebook|\WP_Error $admin Facebook object for Admin Use
 * @property-read int|string $admin_id
 * @property-read array|false $admin_account
 * @property-read array $admin_pages
 */
class Facebook extends NoMailService {

	/**
	 * Service name to display
	 *
	 * @var string
	 */
	public $verbose_service_name = 'Facebook';

	/**
	 * @var string Minimum graph api version
	 */
	public $minimum_api_version = 'v6.0';

	/**
	 * Facebook app version
	 *
	 * @var string
	 */
	public $fb_version = '';

	/**
	 * Facebook application ID
	 *
	 * @var string
	 */
	public $fb_app_id = '';

	/**
	 * Facebook application secret
	 *
	 * @var string
	 */
	public $fb_app_secret = '';

	/**
	 * Whether if use global setting
	 *
	 * @var bool
	 */
	public $fb_use_api = false;

	/**
	 * Facebook API Controller
	 *
	 * @var \Facebook\Facebook
	 */
	private $_api = null;

	/**
	 * Facebook API for user
	 *
	 * @var \Facebook\Facebook
	 */
	private $_admin_api = null;

	/**
	 * Meta key of user_meta for facebook id
	 *
	 * @var string
	 */
	public $umeta_id = '_wpg_facebook_id';

	/**
	 * Meta key of usermeta for facebook mail
	 *
	 * @var string
	 */
	public $umeta_mail = '_wpg_facebook_mail';

	/**
	 * Meta key of usermeta for Facebook access token
	 *
	 * @var string
	 */
	public $umeta_token = '_wpg_facebook_access_token';

	/**
	 * Pseudo email address
	 *
	 * @var string
	 */
	protected $pseudo_domain = 'pseudo.facebook.com';

	/**
	 * @var array
	 */
	private $_signed_request = array();

	/**
	 * Key to retrieve
	 *
	 * @var array
	 */
	protected $option_keys = [
		'fb_enabled'    => false,
		'fb_app_id'     => '',
		'fb_app_secret' => '',
		'fb_use_api'    => false,
		'fb_version'    => '',
	];

	/**
	 * Init action
	 */
	protected function init_action() {
		// Update option
		if ( $this->fb_use_api ) {
			// Save action
			add_action( 'admin_init', array( $this, 'update_facebook_admin' ) );
			// Add view for API
			add_filter(
				'gianism_setting_screen_views',
				function ( $views, $slug ) {
					if ( 'gianism' === $slug ) {
						$views['fb-api'] = sprintf( '<i class="lsf lsf-facebook"></i> %s', $this->_( 'Facebook API' ) );
					}
					return $views;
				},
				10,
				2
			);
		}
	}


	/**
	 * Disconnect user from this service
	 *
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function disconnect( $user_id ) {
		delete_user_meta( $user_id, $this->umeta_id );
		delete_user_meta( $user_id, $this->umeta_mail );
	}

	/**
	 * Returns API endpoint
	 *
	 * @param string $action
	 * @throws \Exception
	 * @return bool|false|string
	 */
	protected function get_api_url( $action ) {
		switch ( $action ) {
			case 'connect':
			case 'login':
				$permission = [ 'email' ];
				break;
			case 'publish':
				$permission = [ 'publish_actions' ];
				break;
			case 'admin':
				$permission = [ 'manage_pages' ];
				break;
			default:
				$permission = [];
				break;
		}
		/**
		 * Permission for Facebook
		 *
		 * @since 3.0.0
		 * @see https://developers.facebook.com/docs/facebook-login/permissions/
		 * @filter gianism_facebook_permissions
		 * @param array  $permission
		 * @param string $scope
		 * @return array
		 */
		$permission = apply_filters( 'gianism_facebook_permissions', $permission, $action );
		if ( ! $permission ) {
			return false;
		}
		$helper = $this->api->getRedirectLoginHelper();
		return $helper->getLoginUrl( $this->get_redirect_endpoint(), $permission );
	}

	/**
	 * Handle publish action
	 *
	 * @param \WP_Query $wp_query
	 */
	public function handle_publish( \WP_Query $wp_query ) {
		try {
			$url = $this->get_api_url( 'publish' );
			$this->session->write( 'redirect_to', $this->input->get( 'redirect_to' ) );
			$this->session->write( 'action', 'publish' );
			$this->session->write( 'hook', $this->input->get( 'hook' ) );
			$this->session->write( 'args', $_GET );
			wp_redirect( $url );
			exit;
		} catch ( \Exception $e ) {
			$this->input->wp_die( $e->getMessage() );
		}
	}

	/**
	 * @param \WP_Query $wp_query
	 */
	public function handle_admin( \WP_Query $wp_query ) {
		try {
			if ( $this->input->request( 'publish' ) ) {
				add_filter(
					'gianism_facebook_permissions',
					function ( $permission, $action ) {
						if ( 'admin' === $action ) {
							$permission[] = 'publish_actions';
						}
						return $permission;
					},
					9,
					2
				);
			}
			$url = $this->get_api_url( 'admin' );
			$this->session->write( 'redirect_to', $this->input->get( 'redirect_to' ) );
			$this->session->write( 'action', 'admin' );
			wp_redirect( $url );
			exit;
		} catch ( \Exception $e ) {
			$this->input->wp_die( $e->getMessage() );
		}
	}

	/**
	 * Communicate with Facebook API
	 *
	 * @global \wpdb $wpdb
	 *
	 * @param string $action
	 *
	 * @return void
	 */
	protected function handle_default( $action ) {
		global $wpdb;
		// Get common values
		$redirect_url = (string) $this->session->get( 'redirect_to' );
		// Process actions
		switch ( $action ) {
			case 'login': // Make user login
				try {
					// Is logged in?
					if ( is_user_logged_in() ) {
						throw new \Exception( $this->_( 'You are already logged in' ) );
					}
					// Get user ID
					$user = $this->get_returned_user();
					// If user doesn't exist, try to register.
					$user_id = $this->get_meta_owner( $this->umeta_id, $user['id'] );
					if ( ! $user_id ) {
						// Test
						$this->test_user_can_register();
						// Check email
						if ( isset( $user['email'] ) && is_email( $user['email'] ) ) {
							$email = (string) $user['email'];
						} else {
							$email = $this->create_pseudo_email( $user['id'] );
						}
						// Does mail duplicated?
						if ( $this->mail_owner( $email ) ) {
							throw new \Exception( $this->duplicate_account_string() );
						}
						/**
						 * Filter default user login name on register.
						 *
						 * There might be no available string for login name, so use Facebook id for login.
						 *
						 * @filter gianism_register_name
						 * @param string $user_login user login name.
						 * @param string $service    Service name.
						 * @param mixed  $user       User data or something. It varies by service.
						 */
						$user_name = apply_filters( 'gianism_register_name', 'fb-' . $user['id'], $this->service_name, $user );
						// Check if username exists
						$user_id = wp_create_user( $user_name, wp_generate_password(), $email );
						if ( is_wp_error( $user_id ) ) {
							throw new \Exception( $this->registration_error_string() );
						}
						// Ok, let's update user meta
						$wpdb->update(
							$wpdb->users,
							[
								'display_name' => $user['name'],
								// 'user_url'     => $user['link'], // Deprecated because of REST API 3 udpdate: https://developers.facebook.com/blog/post/2018/05/01/enhanced-developer-app-review-and-graph-api-3.0-now-live/
							],
							[
								'ID' => $user_id,
							],
							[ '%s', '%s' ],
							[ '%d' ]
						);
						foreach ( [
							$this->umeta_id   => $user['id'],
							$this->umeta_mail => $email,
							'nickname'        => $user['name'],
							'first_name'      => $user['first_name'],
							'last_name'       => $user['last_name'],
						] as $key => $value ) {
							update_user_meta( $user_id, $key, $value );
						}
						$this->user_password_unknown( $user_id );
						$this->hook_connect( $user_id, $user, true );
						$this->welcome( $user['name'] );
					}
					// Make user logged in
					$this->set_auth_cookie( $user_id );
					$redirect_url = $this->filter_redirect( $redirect_url, 'login' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = $this->filter_redirect( wp_login_url( $redirect_url, true ), 'login-failure' );
				}
				// Redirect user
				wp_redirect( $redirect_url );
				exit;
				break;
			case 'connect': // Connect user account to Facebook
				// Connection finished. Let's redirect.
				if ( ! $redirect_url ) {
					$redirect_url = admin_url( 'profile.php' );
				}
				try {
					// Get user ID
					$user = $this->get_returned_user();
					// Check email
					if ( ! isset( $user['email'] ) || ! is_email( $user['email'] ) ) {
						throw new \Exception( $this->mail_fail_string() );
					}
					$email       = $user['email'];
					$email_owner = $this->mail_owner( $email );
					// Check if other user has these as meta_value
					if ( $email_owner && get_current_user_id() !== $email_owner ) {
						throw new \Exception( $this->duplicate_account_string() );
					}
					// Now let's save user_data
					update_user_meta( get_current_user_id(), $this->umeta_id, $user['id'] );
					update_user_meta( get_current_user_id(), $this->umeta_mail, $email );
					// Fires hook
					$this->hook_connect( get_current_user_id(), $this->api );
					// Save message
					$this->welcome( $user['name'] );
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect-failure' );
				}
				wp_redirect( $redirect_url );
				exit;
				break;
			case 'publish':
				try {
					$hook = $this->session->get( 'hook' );
					$args = $this->session->get( 'args' );
					$user = $this->get_returned_user();
					// Check permission exists, save it.
					$token = $this->api->getRedirectLoginHelper()->getAccessToken( $this->get_redirect_endpoint() );
					$perms = $this->api->get( '/me/permissions', $token );
					// Get hook
					if ( $perms && isset( $perms['data'][0]['publish_actions'] ) && $perms['data'][0]['publish_actions'] ) {
						// Save access token.
						update_user_meta( get_current_user_id(), $this->umeta_token, $token );
						// If action is set, do it.
						if ( ! empty( $hook ) ) {
							do_action( (string) $hook, $this->api, $args, $token );
						}
					}
				} catch ( \Exception $e ) {
					$this->add_message( $e->getMessage(), true );
				}
				wp_redirect( $redirect_url );
				exit;
				break;
			case 'admin':
				try {
					$helper     = $this->api->getRedirectLoginHelper();
					$token      = $helper->getAccessToken( $this->get_redirect_endpoint() );
					$oauth      = $this->api->getOAuth2Client();
					$long_token = $oauth->getLongLivedAccessToken( $token );
					// O.K. Token ready and save it.
					update_option( 'gianism_facebook_admin_token', $long_token );
					// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
					update_option( 'gianism_facebook_admin_refreshed', current_time( 'timestamp' ) );
					$this->add_message( $this->_( 'Access token is saved.' ) );
				} catch ( \Exception $e ) {
					$this->add_message( $e->getMessage(), true );
				}
				wp_redirect( $redirect_url );
				exit;
				break;
			default:
				/**
				 * Do something if no action is set
				 *
				 * @action gianism_extra_action
				 * @param string $service_name facebook, google, etc.
				 * @param string $action
				 * @param array{redirect_to:string} $args
				 */
				do_action(
					'gianism_extra_action',
					$this->service_name,
					$action,
					[
						'redirect_to' => $redirect_url,
					]
				);
				$this->input->wp_die( sprintf( $this->_( 'Sorry, but wrong access. Please go back to <a href="%s">%s</a>.' ), home_url( '/' ), get_bloginfo( 'name' ) ), 500, false );
				break;
		}
	}

	/**
	 * Returns login url which get additional permission
	 *
	 * @param string $redirect_url
	 * @param string $action This action hook will booted.
	 * @param array $args Additional key-value
	 *
	 * @return string
	 */
	public function get_publish_permission_link( $redirect_url = null, $action = '', $args = array() ) {
		if ( ! $redirect_url ) {
			$redirect_url = admin_url( 'profile.php' );
		}
		$arguments = array(
			'redirect_to' => $redirect_url,
		);
		if ( ! empty( $action ) ) {
			$arguments['hook'] = $action;
		}

		return $this->get_redirect_endpoint( 'publish', $this->service_name . '_publish', array_merge( $arguments, $args ) );
	}

	/**
	 * Get admin connect link
	 *
	 * @param bool $require_publish
	 *
	 * @return string
	 */
	public function get_admin_connect_link( $require_publish = false ) {
		$arguments = array(
			'redirect_to' => admin_url( 'options-general.php?page=gianism&view=fb-api' ),
		);
		if ( $require_publish ) {
			$arguments['publish'] = 'true';
		}

		return $this->get_redirect_endpoint( 'admin', $this->service_name . '_admin', $arguments );
	}

	/**
	 * Update admin account id.
	 */
	public function update_facebook_admin() {
		if ( 'gianism' === $this->input->get( 'page' ) && wp_verify_nonce( $this->input->post( '_wpnonce' ), 'gianism_fb_account' ) ) {
			update_option( 'gianism_facebook_admin_id', $this->input->post( 'fb_account_id' ) );
			$this->add_message( $this->_( 'Saved facebook account to use.' ) );
			wp_redirect( admin_url( 'options-general.php?page=gianism&view=fb-api' ) );
			exit;
		}
	}

	/**
	 * Get returned user
	 *
	 * @return GraphUser|null
	 * @throws \Exception
	 */
	public function get_returned_user() {
		$redirect_helper = $this->api->getRedirectLoginHelper();
		$access_token    = $redirect_helper->getAccessToken( $this->get_redirect_endpoint() );
		if ( ! $access_token ) {
			throw new \Exception( $redirect_helper->getError(), $redirect_helper->getErrorCode() );
		}
		$user = $this->get_user_profile( 'login', $access_token );
		if ( ! $user ) {
			throw new \Exception( $this->_( 'Sorry, but failed to get user data.' ), 500 );
		}
		return $user;
	}

	/**
	 * Return user id of email if exists
	 *
	 * @param string $email
	 *
	 * @return int
	 */
	public function mail_owner( $email ) {
		$owner = email_exists( $email );
		if ( $owner ) {
			return $owner;
		}
		return $this->get_meta_owner( $this->umeta_mail, $email );
	}

	/**
	 * Returns user id with facebook id
	 *
	 * @param string $fb_id
	 *
	 * @return int
	 */
	public function id_owner( $fb_id ) {
		return $this->get_meta_owner( $this->umeta_id, $fb_id );
	}

	/**
	 * Get signed request
	 *
	 * @param string $key
	 * @throws \Exception
	 * @return SignedRequest
	 */
	private function signed_request( $key ) {
		$page_helper = $this->api->getPageTabHelper();
		return $page_helper->getSignedRequest();
	}

	/**
	 * Returns if user is connected to Facebook
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function is_connected( $user_id ) {
		return (bool) $this->get_facebook_id( $user_id );
	}

	/**
	 * Returns User's Facebook ID
	 *
	 * @param int $wp_user_id
	 *
	 * @return int
	 */
	public function get_facebook_id( $wp_user_id ) {
		return get_user_meta( $wp_user_id, $this->umeta_id, true ) ?: 0;
	}

	/**
	 * Returns Facebook mail
	 *
	 * @param int $wp_user_id
	 *
	 * @return string
	 */
	public function get_user_mail( $wp_user_id ) {
		return (string) get_user_meta( $wp_user_id, $this->umeta_mail, true );
	}

	/**
	 * Get current users profile
	 *
	 * @param string $context
	 * @param string $token   string
	 * @param string $user_id Default 'me'
	 * @throws \Exception
	 * @return GraphUser|null
	 */
	protected function get_user_profile( $context, $token, $user_id = 'me' ) {
		/**
		 * Information field of Facebook user.
		 *
		 * Default is id, name, email.
		 *
		 * @filter gianism_user_profile_fields
		 * @see https://developers.facebook.com/docs/graph-api/reference/user
		 * @param array $fields Default array('id', 'name', 'email', 'first_name', 'last_name')
		 * @param string $context 'login' or 'connect'
		 */
		$fields = apply_filters(
			'gianism_user_profile_fields',
			[
				'id',
				'name',
				'email',
				//          'link',
									'first_name',
				'last_name',
			],
			$context
		);

		return $this->api->get( "/{$user_id}?fields=" . implode( ',', $fields ), $token )->getGraphUser();
	}

	/**
	 * Save Facebook Mail
	 *
	 * @param string $mail
	 * @param int $user_id
	 *
	 * @return void
	 */
	public function set_user_mail( $mail, $user_id ) {
		update_user_meta( $user_id, $this->umeta_mail, $mail );
	}

	/**
	 * Save Facebook ID
	 *
	 * @param string $fb_user_id
	 * @param int $wp_user_id
	 *
	 * @return void
	 */
	public function set_user_id( $fb_user_id, $wp_user_id ) {
		update_user_meta( $wp_user_id, $this->umeta_id, $fb_user_id );
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'api':
				if ( is_null( $this->_api ) ) {
					$this->_api = new \Facebook\Facebook(
						[
							'app_id'                  => $this->fb_app_id,
							'app_secret'              => $this->fb_app_secret,
							'default_graph_version'   => $this->get_graph_version(),
							'persistent_data_handler' => new FacebookCookiePersistentDataHandler(),
						]
					);
				}
				return $this->_api;
				break;
			case 'admin':
				if ( is_null( $this->_admin_api ) ) {
					$token = $this->option->get( 'gianism_facebook_admin_token', false );
					if ( ! $this->fb_use_api || ! $token ) {
						return new \WP_Error( 404, $this->_( 'Token is not set. Please get it.' ) );
					}
					try {
						$this->_admin_api = new \Facebook\Facebook(
							[
								'app_id'                  => $this->fb_app_id,
								'app_secret'              => $this->fb_app_secret,
								'default_graph_version'   => $this->get_graph_version(),
								'persistent_data_handler' => new FacebookCookiePersistentDataHandler(),
							]
						);
						// Check last updated
						$updated = $this->option->get( 'gianism_facebook_admin_refreshed', 0 );
						// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
						if ( ! $updated || current_time( 'timestamp' ) > $updated + ( 60 * 60 * 24 * 60 ) ) {
							return new \WP_Error( 410, $this->_( 'Token is outdated. Please update it.' ) );
						}
						$this->_admin_api->setDefaultAccessToken( $token );
					} catch ( \Exception $e ) {
						return new \WP_Error( $e->getCode(), $e->getMessage() );
					}
				}
				return $this->_admin_api;
				break;
			case 'admin_account':
				if ( is_wp_error( $this->admin ) ) {
					return false;
				}
				try {
					return $this->admin->get( '/me' )->getGraphUser();
				} catch ( \Exception $e ) {
					return false;
				}
				break;
			case 'admin_pages':
				if ( is_wp_error( $this->admin ) ) {
					return [];
				} else {
					try {
						$response = $this->admin->get( '/me/accounts' )->getGraphEdge();
						$pages    = [];
						foreach ( $response as $node ) {
							$pages[] = [
								'id'    => $node->getProperty( 'id' ),
								'name'  => $node->getProperty( 'name' ),
								'token' => $node->getProperty( 'access_token' ),
							];
						}
						return $pages;
					} catch ( \Exception $e ) {
						trigger_error( $e->getMessage(), E_USER_WARNING );
						return [];
					}
				}
				break;
			case 'admin_id':
				return $this->option->get( 'gianism_facebook_admin_id', 'me' );
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}

	/**
	 * Get graph api version.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	public function get_graph_version() {
		$version = $this->fb_version;
		if ( ! preg_match( '/^v\d+\.\d+$/u', $version ) ) {
			return $this->minimum_api_version;
		} elseif ( version_compare( $version, $this->minimum_api_version, '<' ) ) {
			return $this->minimum_api_version;
		} else {
			return $version;
		}
	}

	/**
	 * Get current page api
	 *
	 * @package Gianism
	 * @since 3.0.6
	 * @return \Facebook\Facebook|\WP_Error
	 */
	public function get_current_page_api() {
		$page_id = $this->admin_id;
		if ( 'me' === $page_id ) {
			return new \WP_Error( 500, __( 'Page is not set.', 'wp-gianism' ) );
		}
		$token = '';
		foreach ( $this->admin_pages as $page ) {
			if ( $page_id === $page['id'] ) {
				$token = $page['token'];
				break;
			}
		}
		if ( ! $token ) {
			return new \WP_Error( 404, __( 'No page found. Do you have permission for that page?', 'wp-gianism' ) );
		}
		try {
			$api = new \Facebook\Facebook(
				[
					'app_id'                  => $this->fb_app_id,
					'app_secret'              => $this->fb_app_secret,
					'default_graph_version'   => $this->get_graph_version(),
					'persistent_data_handler' => new FacebookCookiePersistentDataHandler(),
				]
			);
			$api->setDefaultAccessToken( $token );
			return $api;
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Returns if user like my page. Only available on Facebook Tab page or application.
	 *
	 * @deprecated 3.0.0
	 * @return bool
	 */
	public function is_user_like_me_on_fangate() {
		_deprecated_function( __METHOD__, 'Gianism 3.0.0', null );
		return false;
	}

	/**
	 * Returns if current facebook user is WordPress registered user.
	 *
	 * If current Facebook user is registerd on your WordPress, returns user ID on WordPress.
	 *
	 * @deprecated 3.0.0
	 * @return int
	 */
	public function is_registered_user_on_fangate() {
		_deprecated_function( __METHOD__, 'Gianism 3.0.0', null );
		return false;
	}

	/**
	 * Return true if current user is not logged in Facebook
	 *
	 * @deprecated 3.0.0
	 * @return boolean
	 */
	public function is_guest_on_fangate() {
		_deprecated_function( __METHOD__, 'Gianism 3.0.0', null );
		return false;
	}

	/**
	 * Returns if current page is fan gate.
	 *
	 * @deprecated 3.0.0
	 * @return bool
	 */
	public function is_fangate() {
		_deprecated_function( __METHOD__, 'Gianism 3.0.0', null );
		return false;
	}

	/**
	 * Initialize Facebook Fangate Scripts
	 *
	 * @deprecated 3.0.0
	 */
	public function fan_gate_helper() {
		// Do nothing
		_deprecated_function( __METHOD__, 'Gianism 3.0.0', null );
	}
}
