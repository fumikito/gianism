<?php

namespace Gianism\Service;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Description of twitter_controller
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Twitter extends NoMailService {

	/**
	 * URL prefix
	 *
	 * @var string
	 */
	public $url_prefix = 'twitter';

	/**
	 * Screen name of admin
	 *
	 * @var string
	 */
	public $tw_screen_name = '';

	/**
	 * Consumer key
	 *
	 * @var string
	 */
	public $tw_consumer_key = '';

	/**
	 * Consumer secret
	 *
	 * @var string
	 */
	public $tw_consumer_secret = '';

	/**
	 * Access token of admin
	 *
	 * @var string
	 */
	public $tw_access_token = '';

	/**
	 * Access token secret of admin
	 *
	 * @var string
	 */
	public $tw_access_token_secret = '';

	/**
	 * If use cron bot
	 *
	 * @var bool
	 */
	public $tw_use_cron = false;

	/**
	 * User's twitter id
	 *
	 * @var string
	 */
	public $umeta_id = '_wpg_twitter_id';

	/**
	 * User's screen name
	 *
	 * @var string
	 */
	public $umeta_screen_name = '_wpg_twitter_screen_name';

	/**
	 * Pseudo email address
	 *
	 * @var string
	 */
	protected $pseudo_domain = 'pseudo.twitter.com';

	/**
	 * @var TwitterOAuth
	 */
	private $oauth = null;

	/**
	 * Option key name to assign
	 *
	 * @var array
	 */
	protected $option_keys = [
		'tw_enabled'             => false,
		'tw_screen_name'         => '',
		'tw_consumer_key'        => '',
		'tw_consumer_secret'     => '',
		'tw_access_token'        => '',
		'tw_access_token_secret' => '',
		'tw_use_cron'            => false,
	];

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		parent::__construct( $argument );
		// TODO: Change this process if PHP requirements changed.
		if ( version_compare( phpversion(), '5.5.0', '<' ) && $this->enabled ) {
			add_action( 'admin_notices', function() {
				printf(
					'<div class="error"><p>%s</p></div>',
					sprintf( $this->_( 'Twitter Login requires PHP5.5 and over but yours is %s. Every twitter login will be failed.' ), phpversion() )
				);
			} );
		}
	}

	/**
	 * Handle callback
	 *
	 * @param string $action
	 */
	protected function handle_default( $action ) {
		// Get common values
		$redirect_url = $this->session->get( 'redirect_to' );
		/**
		 * @var array $token 'oauth_token_secret', 'oauth_token', ''
		 */
		$token = $this->session->get( 'token' );
		/** @var string $verifier */
		$verifier = $this->input->get( 'oauth_verifier' );
		// Process action
		switch ( $action ) {
			case 'login': // Make user login
				try {
					// Get information from twitter.
					if ( ! ( $verifier ) || ! $this->validate_token( $token ) ) {
						throw new \Exception( $this->api_error_string() );
					}
					$oauth        = $this->get_oauth( $token['oauth_token'], $token['oauth_token_secret'] );
					$access_token = $oauth->oauth( 'oauth/access_token', [
						'oauth_verifier' => $verifier,
					] );
					if ( ! isset( $access_token['user_id'], $access_token['screen_name'] ) ) {
						throw new \Exception( $this->api_error_string() );
					}
					$twitter_id   = $access_token['user_id'];
					$screen_name  = $access_token['screen_name'];
					$oauth_token  = $access_token['oauth_token'];
					$oauth_secret = $access_token['oauth_token_secret'];
					// Get exiting user ID
					$user_id     = $this->get_meta_owner( $this->umeta_id, $twitter_id );
					if ( ! $user_id ) {
						// Test
						$this->test_user_can_register();
						// Check if you can get email
						$verified = $this->get_oauth( $oauth_token, $oauth_secret );
						$profile  = $verified->get( 'account/verify_credentials', [
							'skip_status'   => 'true',
							'include_email' => 'true',
						] );
						if ( $profile && isset( $profile->email ) && $profile->email ) {
							// Yay! Email retrieved.
							$email = $profile->email;
						} else {
							$email = $this->create_pseudo_email( [
								'screen_name' => $screen_name,
								'twitter_id'  => $twitter_id,
							]);
						}
						// Make username from screen name
						$user_name = ( ! username_exists( '@' . $screen_name ) ) ? '@' . $screen_name : $email;
						/**
						 * @see Facebook
						 */
						$user_name = apply_filters( 'gianism_register_name', $user_name, $this->service, $access_token );
						// Create user
						$user_id = wp_create_user( $user_name, wp_generate_password(), $email );
						if ( is_wp_error( $user_id ) ) {
							throw new \Exception( $this->registration_error_string() );
						}
						// Update extra information
						update_user_meta( $user_id, $this->umeta_id, $twitter_id );
						update_user_meta( $user_id, $this->umeta_screen_name, $screen_name );
						update_user_meta( $user_id, 'nickname', '@' . $screen_name );
						$this->db->update(
							$this->db->users,
							[
								'display_name' => "@{$screen_name}",
								'user_url'     => 'https://twitter.com/' . $screen_name,
							],
							[
								'ID' => $user_id,
							],
							[ '%s', '%s' ],
							[ '%d' ]
						);
						// Password is unknown
						$this->user_password_unknown( $user_id );
						$this->hook_connect( $user_id, $oauth, true );
						// Let user follow me
						$this->follow_me( $oauth );
						$this->welcome( '@' . $screen_name );
					}
					// Let user log in.
					wp_set_auth_cookie( $user_id, true );
					$redirect_url = $this->filter_redirect( $redirect_url, 'login' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = $this->filter_redirect( wp_login_url( $redirect_url, true ), 'login-fail' );
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
					// Is user logged in?
					if ( ! is_user_logged_in() ) {
						throw new \Exception( $this->_( 'You must be logged in.' ) );
					}
					// Get information from twitter.
					if ( ! ( $verifier ) || ! $this->validate_token( $token ) ) {
						throw new \Exception( $this->api_error_string() );
					}
					// Get user
					$oauth        = $this->get_oauth( $token['oauth_token'], $token['oauth_token_secret'] );
					$access_token = $oauth->oauth( 'oauth/access_token', [
						'oauth_verifier' => $verifier,
					] );
					if ( ! isset( $access_token['user_id'], $access_token['screen_name'] ) ) {
						throw new \Exception( $this->api_error_string() );
					}
					$twitter_id  = $access_token['user_id'];
					$screen_name = $access_token['screen_name'];
					// Check if other user has registered
					$id_owner = $this->get_meta_owner( $this->umeta_id, $twitter_id );
					if ( $id_owner && ( get_current_user_id() != $id_owner ) ) {
						throw new \Exception( $this->duplicate_account_string() );
					}
					// O.K.
					update_user_meta( get_current_user_id(), $this->umeta_id, $twitter_id );
					update_user_meta( get_current_user_id(), $this->umeta_screen_name, $screen_name );
					$this->follow_me( $oauth );
					$this->hook_connect( get_current_user_id(), $oauth, false );
					$this->welcome( '@' . $screen_name );
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = $this->filter_redirect( $redirect_url, 'connect-failure' );
				}
				wp_redirect( $redirect_url );
				exit;
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
		switch ( $action ) {
			case 'connect':
			case 'login':
				$oauth = $this->get_oauth();
				$token = $oauth->oauth( 'oauth/request_token',  [
					'oauth_callback' => $this->get_redirect_endpoint(),
				] );
				if ( ! $this->validate_token( $token ) ) {
					throw new \Exception( $this->api_error_string() );
				}
				$url = $this->authorize_url( $token );
				if ( $url ) {
					$this->session->write( 'token', $token );
					return $url;
				} else {
					return false;
				}
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Get API wrapper
	 *
	 * @param string $oauth_token
	 * @param string $oauth_token_secret
	 *
	 * @return TwitterOAuth
	 */
	public function get_oauth( $oauth_token = null, $oauth_token_secret = null ) {
		$oauth = new TwitterOAuth( $this->tw_consumer_key, $this->tw_consumer_secret, $oauth_token, $oauth_token_secret );
		/**
		 * Twitter OAuth Client
		 *
		 * @filter gianism_twitter_oauth_client
		 * @param TwitterOauth $oauth
		 * @param string       $oauth_token
		 * @param string       $oauth_token_secret
		 * @return TwitterOAuth
		 */
		$oauth = apply_filters( 'gianism_twitter_oauth_client', $oauth, $oauth_token, $oauth_token_secret );
		return $oauth;
	}

	/**
	 * Validate token
	 *
	 * @param mixed $token
	 * @param bool $url_confirmed If redirect URL must be validated
	 *
	 * @return bool
	 */
	private function validate_token( $token, $url_confirmed = true ) {
		if ( ! is_array( $token ) || empty( $token ) ) {
			return false;
		}
		if ( ! isset( $token['oauth_token'], $token['oauth_token_secret'] ) ) {
			return false;
		}
		if ( $url_confirmed && ( ! isset( $token['oauth_callback_confirmed'] ) || ! $token['oauth_callback_confirmed'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Send direct message on twitter.
	 *
	 * @param int          $user_id
	 * @param string       $text
	 * @param TwitterOAuth $oauth
	 *
	 * @return object
	 */
	public function send_dm( $user_id, $text, $oauth = null ) {
		$twitter_id = get_user_meta( $user_id, $this->umeta_id, true );
		if ( $twitter_id ) {
			return $this->call_api( 'direct_messages/new', array(
				'user_id' => $twitter_id,
				'text'    => $text,
			), 'POST', $oauth );
		}
	}

	/**
	 * Tweet with Owner ID
	 *
	 * @param string       $string
	 * @param TwitterOAuth $oauth
	 *
	 * @return object Json format object.
	 */
	public function tweet( $string, $oauth = null ) {
		return $this->call_api( 'statuses/update', [
			'status' => $string,
		], 'POST', $oauth );
	}

	/**
	 * Tweet with media
	 *
	 * @param string       $string
	 * @param array        $medias
	 * @param TwitterOAuth $oauth
	 * @return object JSON format object
	 */
	public function tweet_with_media( $string, array $medias, $oauth = null ) {
		$media_ids = [];
		foreach ( $medias as $media ) {
			$media_id = $this->upload( $media, $oauth );
			if ( ! is_wp_error( $media_id ) ) {
				$media_ids[] = $media_id;
			}
		}
		if ( ! $media_ids ) {
			return new \WP_Error( 500, __( 'Failed to upload media', 'wp-gianism' ) );
		}
		return $this->call_api( 'statuses/update', [
			'status' => $string,
			'media_ids' => implode( ',', $media_ids ),
		], 'POST', $oauth );
	}

	/**
	 * Upload media to twitter.
	 *
	 * @since 3.0.7
	 * @param int|string $path_or_id attachment ID or URL. Integer is recognized as attachment ID.
	 * @param TwitterOAuth $oauth Default null.
	 * @return string|\WP_Error Media ID on twitter.
	 */
	public function upload( $path_or_id, $oauth = null ) {
		if ( is_null( $oauth ) ) {
			$oauth = $this->get_oauth( $this->tw_access_token, $this->tw_access_token_secret );
		}
		if ( is_numeric( $path_or_id ) ) {
			// This is attachment
			$path = get_attached_file( $path_or_id );
			if ( ! $path ) {
				return new \WP_Error( 404, __( 'File not found.', 'wp-gianism' ) );
			}
			$object = $path;
		} elseif ( preg_match( '#^https?://#u', $path_or_id ) ) {
			// This is URL
			$file = @file_get_contents( $path_or_id );
			if ( ! $file ) {
				return new \WP_Error( 404, __( 'File not found.', 'wp-gianism' ) );
			}
			$path = sys_get_temp_dir() . '/' . tmpfile() . '-' . basename( $path_or_id );
			if ( ! @file_put_contents( $path, $file ) ) {
				return new \WP_Error( 500, __( 'Failed to download media', 'wp-gianism' ) );
			}
			$object = $path;
		} else {
			// This is file
			if ( ! file_exists( $path_or_id ) ) {
				return new \WP_Error( 404, __( 'File not found.', 'wp-gianism' ) );
			}
			$object = $path_or_id;
		}
		$media = $oauth->upload( 'media/upload', [
			'media' => $object,
		] );
		if ( ! $media ) {
			return new \WP_Error( 500, __( 'Failed to upload media to twitter.', 'wp-gianism' ) );
		}
		return $media->media_id_string;
	}

	/**
	 * Force authenticated user to follow me
	 *
	 * @param TwitterOAuth $oauth
	 * @param string       $screen_name
	 *
	 * @return object Json format object.
	 */
	private function follow_me( TwitterOAuth $oauth = null, $screen_name = false ) {
		if ( ! empty( $this->tw_screen_name ) ) {
			if ( ! $screen_name ) {
				$screen_name = $this->tw_screen_name;
			}
			return $this->call_api( 'friendships/create', [
				'screen_name' => $screen_name,
				'follow'      => true,
			], 'POST', $oauth );
		} else {
			return null;
		}
	}

	/**
	 * Get mentions
	 *
	 * @param array        $args
	 * @param TwitterOAuth $oauth
	 *
	 * @return object
	 */
	public function get_mentions( $args = array(), $oauth = null ) {
		$args          = wp_parse_args( $args, array(
			'count'    => 20,
			'since_id' => false,
			'max_id'   => false,
		) );
		$args['count'] = max( 20, min( 200, $args['count'] ) );
		foreach ( array( 'since_id', 'max_id' ) as $key ) {
			if ( ! $args[ $key ] ) {
				unset( $args[ $key ] );
			}
		}

		return $this->call_api( 'statuses/mentions_timeline', $args, 'GET', $oauth );
	}

	/**
	 * Authorize URL
	 *
	 * @param array $token
	 * @return string
	 */
	private function authorize_url( $token ) {
		return 'https://api.twitter.com/oauth/authorize?oauth_token=' . $token['oauth_token'];
	}

	/**
	 * Get pseudo email
	 *
	 * @param mixed $prefix
	 *
	 * @return string
	 */
	protected function create_pseudo_email( $prefix ) {
		// No mail, let's make pseudo mail address.
		$email = $prefix['screen_name'] . '@' . $this->pseudo_domain;
		if ( email_exists( $email ) ) {
			$email = 'tw-' . $prefix['twitter_id'] . '@' . $this->pseudo_domain;
		}
		return $email;
	}


	/**
	 * Returns GET api request.
	 *
	 * You should know what kind of APIs are available.
	 *
	 * @see https://dev.twitter.com/docs/api/1.1
	 *
	 * @param string $endpoint API URL. Must not be started with slash. i.e. 'statuses/user_timeline'
	 * @param array $data
	 * @param string $method GET or POST. Default GET
	 * @param TwitterOAuth $oauth If not set, create own.
	 *
	 * @return object Maybe JSON object.
	 */
	public function call_api( $endpoint, array $data, $method = 'GET', TwitterOAuth $oauth = null ) {
		if ( is_null( $oauth ) ) {
			$oauth = $this->get_oauth( $this->tw_access_token, $this->tw_access_token_secret );
		}
		switch ( strtolower( $method ) ) {
			case 'post':
				return $oauth->post( $endpoint, $data );
				break;
			case 'delete':
				return $oauth->delete( $endpoint, $data );
				break;
			case 'put':
				return $oauth->put( $endpoint, $data );
				break;
			case 'get':
			default:
				return $oauth->get( $endpoint, $data );
				break;
		}
	}
}
