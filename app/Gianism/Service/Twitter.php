<?php

namespace Gianism\Service;

use Abraham\TwitterOAuth\TwitterOAuth;
use Gianism\Plugins\Bot;

/**
 * Description of twitter_controller
 *
 * @package Gianism\Service
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
	 * User's scrren name
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
		if ( $this->tw_use_cron ) {
			Bot::get_instance();
		}
	}

	/**
	 * Handle callback
	 *
	 * @param string $action
	 */
	protected function handle_default( $action ) {
		/** @var \wpdb $wpdb */
		global $wpdb;
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
					$twitter_id  = $access_token['user_id'];
					$screen_name = $access_token['screen_name'];
					$user_id     = $this->get_meta_owner( $this->umeta_id, $twitter_id );
					if ( ! $user_id ) {
						// Test
						$this->test_user_can_register();
						// Make pseudo mail
						$email = $screen_name . '@' . $this->pseudo_domain;
						if ( email_exists( $email ) ) {
							$email = 'tw-' . $twitter_id . '@' . $this->pseudo_domain;
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
						$wpdb->update(
							$wpdb->users,
							[
								'display_name' => "@{$screen_name}",
								'user_url'     => 'https://twitter.com/' . $screen_name,
							],
							[ 'ID' => $user_id ],
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
					$redirect_url = wp_login_url( $redirect_url, true );
				}
				wp_redirect( $redirect_url );
				exit;
				break;
			case 'connect':
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
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
				}
				wp_redirect( $redirect_url = $this->filter_redirect( $redirect_url, 'connect' ) );
				exit;
			default:
				// Nothing to do
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
	private function get_oauth( $oauth_token = null, $oauth_token_secret = null ) {
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
		return 'https://api.twitter.com/oauth/authorize?oauth_token='.$token['oauth_token'];
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
