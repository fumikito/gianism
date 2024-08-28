<?php

namespace Gianism\Service;

use Abraham\TwitterOAuth\Consumer;
use Abraham\TwitterOAuth\HmacSha1;
use Abraham\TwitterOAuth\Request;
use Abraham\TwitterOAuth\Token;
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
			add_action(
				'admin_notices',
				function () {
					printf(
						'<div class="error"><p>%s</p></div>',
						sprintf( $this->_( 'Twitter Login requires PHP5.5 and over but yours is %s. Every twitter login will be failed.' ), phpversion() )
					);
				}
			);
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
					$access_token = $oauth->oauth(
						'oauth/access_token',
						[
							'oauth_verifier' => $verifier,
						]
					);
					if ( ! isset( $access_token['user_id'], $access_token['screen_name'] ) ) {
						throw new \Exception( $this->api_error_string() );
					}
					$twitter_id   = $access_token['user_id'];
					$screen_name  = $access_token['screen_name'];
					$oauth_token  = $access_token['oauth_token'];
					$oauth_secret = $access_token['oauth_token_secret'];
					// Get exiting user ID
					$user_id = $this->get_meta_owner( $this->umeta_id, $twitter_id );
					if ( ! $user_id ) {
						// Test
						$this->test_user_can_register();
						// Check if you can get email
						$verified = $this->get_oauth( $oauth_token, $oauth_secret );
						$profile  = $verified->get(
							'account/verify_credentials',
							[
								'skip_status'   => 'true',
								'include_email' => 'true',
							]
						);
						if ( $profile && isset( $profile->email ) && $profile->email ) {
							// Yay! Email retrieved.
							$email = $profile->email;
						} else {
							$email = $this->create_pseudo_email(
								[
									'screen_name' => $screen_name,
									'twitter_id'  => $twitter_id,
								]
							);
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
						$this->welcome( '@' . $screen_name );
					}
					// Let user log in.
					$this->set_auth_cookie( $user_id );
					$redirect_url = $this->filter_redirect( $redirect_url, 'login' );
				} catch ( \Exception $e ) {
					$this->auth_fail( $e->getMessage() );
					$redirect_url = $this->filter_redirect( wp_login_url( $redirect_url, true ), 'login-failure' );
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
					$access_token = $oauth->oauth(
						'oauth/access_token',
						[
							'oauth_verifier' => $verifier,
						]
					);
					if ( ! isset( $access_token['user_id'], $access_token['screen_name'] ) ) {
						throw new \Exception( $this->api_error_string() );
					}
					$twitter_id  = $access_token['user_id'];
					$screen_name = $access_token['screen_name'];
					// Check if other user has registered
					$id_owner = $this->get_meta_owner( $this->umeta_id, $twitter_id );
					if ( $id_owner && ( get_current_user_id() !== $id_owner ) ) {
						throw new \Exception( $this->duplicate_account_string() );
					}
					// O.K.
					update_user_meta( get_current_user_id(), $this->umeta_id, $twitter_id );
					update_user_meta( get_current_user_id(), $this->umeta_screen_name, $screen_name );
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
	 * Returns whether the user has a twitter account.
	 *
	 * @param int $user_id
	 *
	 * @return boolean
	 */
	public function is_connected( $user_id ) {
		return (bool) get_user_meta( $user_id, $this->umeta_id, true );
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
				$token = $oauth->oauth(
					'oauth/request_token',
					[
						'oauth_callback' => $this->get_redirect_endpoint(),
					]
				);
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
	 * Authorize URL
	 *
	 * @param array $token
	 * @return string
	 */
	private function authorize_url( $token ) {
		return 'https://api.twitter.com/oauth/authorize?oauth_token=' . $token['oauth_token'];
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
	 * Tweet with Owner ID
	 *
	 * @see https://developer.twitter.com/en/docs/twitter-api/tweets/manage-tweets/api-reference/post-tweets
	 *
	 * @param string $text
	 * @param null   $deprecated Since 5.1.0
	 * @param array  $options
	 * @param string $token
	 * @param string $secret
	 *
	 * @return object|\WP_Error Json format object.
	 */
	public function tweet( $text, $deprecated = null, array $options = [], $token = '', $secret = '' ) {
		return $this->call_api(
			'tweets',
			array_merge(
				[
					'text' => $text,
				],
				$options
			),
			'POST',
			$deprecated,
			$token,
			$secret
		);
	}

	/**
	 * Tweet with media
	 *
	 * @param string       $text
	 * @param array        $medias
	 * @param TwitterOAuth $oauth
	 * @param string       $token
	 * @param string       $secret
	 *
	 * @return object|\WP_Error JSON format object
	 */
	public function tweet_with_media( $text, array $medias, $oauth = null, $token = '', $secret = '' ) {
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
		return $this->tweet(
			$text,
			$oauth,
			[
				'media' => [
					'media_ids' => $media_ids,
				],
			],
			$token,
			$secret
		);
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
			if ( ! file_put_contents( $path, $file ) ) {
				return new \WP_Error( 500, __( 'Failed to download media', 'wp-gianism' ) );
			}
			$object = $path;
		} else {
			// This is file.
			if ( ! file_exists( $path_or_id ) ) {
				return new \WP_Error( 404, __( 'File not found.', 'wp-gianism' ) );
			}
			$object = $path_or_id;
		}
		$media = $oauth->upload(
			'media/upload',
			[
				'media' => $object,
			]
		);
		if ( ! $media ) {
			return new \WP_Error( 500, __( 'Failed to upload media to twitter.', 'wp-gianism' ) );
		}
		return $media->media_id_string;
	}

	/**
	 * Send direct message on twitter.
	 *
	 * @param int          $user_id
	 * @param string       $text
	 * @param TwitterOAuth $oauth
	 * @deprecated 5.1.0
	 *
	 * @return object|\WP_Error
	 */
	public function send_dm( $user_id, $text, $oauth = null ) {
		return new \WP_Error( 'twitter_api_error', __( 'twitter DM is deprecated.', 'wp-gianism' ) );
	}

	/**
	 * Force authenticated user to follow me
	 *
	 * @param TwitterOAuth $oauth
	 * @param string       $screen_name
	 * @deprecated 5.1.0
	 *
	 * @return object|\WP_Error Json format object.
	 */
	private function follow_me( TwitterOAuth $oauth = null, $screen_name = false ) {
		return new \WP_Error( 'twitter_api_error', __( 'Follow API is deprecated.', 'wp-gianism' ) );
	}

	/**
	 * Get mentions
	 *
	 * @param array        $args
	 * @param TwitterOAuth $oauth
	 *
	 * @return object|\WP_Error
	 */
	public function get_mentions( $args = array(), $oauth = null ) {
		$args          = wp_parse_args(
			$args,
			array(
				'count'    => 20,
				'since_id' => false,
				'max_id'   => false,
			)
		);
		$args['count'] = max( 20, min( 200, $args['count'] ) );
		foreach ( array( 'since_id', 'max_id' ) as $key ) {
			if ( ! $args[ $key ] ) {
				unset( $args[ $key ] );
			}
		}

		return $this->call_api( 'statuses/mentions_timeline', $args, 'GET', $oauth );
	}

	/**
	 * Returns GET api request.
	 *
	 * You should know what kind of APIs are available.
	 *
	 * @see https://dev.twitter.com/docs/api/1.1
	 * @since 5.2.0 Change twitter api v1.1 to v2
	 *
	 * @param string $endpoint     API URL. Must not be started with slash. i.e. 'statuses/user_timeline'
	 * @param array  $data         Data to send to API.
	 * @param string $method       GET, POST, PUT, or DELETE. Default GET
	 * @param null   $deprecated   Formerly used for $token. No longer used.
	 * @param string $access_token If set, use this token.
	 * @param string $token_secret If set, use this token secret.
	 *
	 * @return object|\WP_Error Maybe JSON object.
	 */
	public function call_api( $endpoint, array $data, $method = 'GET', $deprecated = null, $access_token = '', $token_secret = '' ) {
		$method   = strtoupper( $method );
		$consumer = new Consumer( $this->tw_consumer_key, $this->tw_consumer_secret );
		$token    = new Token(
			$access_token ?: $this->tw_access_token,
			$token_secret ?: $this->tw_access_token_secret
		);
		// Only post allow json payload.
		$json = 'POST' === $method;
		$url  = sprintf( 'https://api.twitter.com/2/%s', ltrim( $endpoint, '/' ) );
		// Create request object.
		$request = Request::fromConsumerAndToken( $consumer, $token, $method, $url, $data, $json );
		if ( array_key_exists( 'oauth_callback', $data ) ) {
			unset( $data['oauth_callback'] );
		}
		// Create request.
		$request->signRequest( new HmacSha1(), $consumer, $token );
		$authorization = $request->toHeader();
		if ( array_key_exists( 'oauth_verifier', $data ) ) {
			unset( $data['oauth_verifier'] );
		}
		$arguments = [
			'method' => $method,
		];
		$headers   = [
			'Accept'        => 'application/json',
			'Authorization' => str_replace( 'Authorization: ', '', $authorization ),
		];
		if ( 'POST' === $method ) {
			$headers['Content-Type'] = 'application/json';
			$arguments['body']       = json_encode( $data );
		} elseif ( ! empty( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = rawurlencode( $value );
			}
			$url = add_query_arg( $data, $url );
		}
		$arguments['headers'] = $headers;
		$response             = wp_remote_request( $url, $arguments );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$status = $response['response']['code'];
		if ( preg_match( '/[45]\d{2}/u', $status ) ) {
			return new \WP_Error(
				'twitter_api_error',
				sprintf( '%s: %s', $response['response']['code'], $response['response']['message'] ),
				$response
			);
		}
		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( is_null( $body ) ) {
			return new \WP_Error( 'twitter_api_error', __( 'Failed to parse API response.', 'wp-gianism' ) );
		}
		$errors = new \WP_Error();
		if ( ! empty( $body->errors ) ) {
			foreach ( $body->errors as $error ) {
				$errors->add(
					'twitter_api_error',
					$error->message,
					[
						'code' => $error->code,
					]
				);
			}
		}
		return ! $errors->get_error_codes() ? $body : $errors;
	}
}
