<?php

namespace Gianism\Plugins;


use Gianism\Pattern\Application;
use Gianism\Service\Google;

/**
 * Analytics
 *
 * @package  Gianism\Plugins
 * @property Google                    $google
 * @property string                    $ga_table
 * @property \Google_Client            $ga_client
 * @property \Google_Service_Analytics $ga
 * @property string                    $ga_token
 * @property array                     $ga_profile
 * @property array                     $ga_accounts
 */
class Analytics extends PluginBase {



	/**
	 * Analytics service
	 *
	 * @var \Google_Service_Analytics
	 */
	private $_ga = null;

	/**
	 * Analytics client
	 *
	 * @var \Google_Client
	 */
	private $_ga_client = null;


	/**
	 * Action name
	 */
	const AJAX_ACTION = 'wpg_ga_account';

	/**
	 * Create table action
	 */
	const AJAX_TABLE = 'wpg_ga_table';

	/**
	 * Cron checker
	 */
	const AJAX_CRON = 'wpg_ga_cron';


	/**
	 * Cron ready classes
	 *
	 * @var array
	 */
	public $crons = [];

	/**
	 * Ajax class names
	 *
	 * @var array
	 */
	public $ajaxes = [];

	/**
	 * Is this plugin enabled.
	 *
	 * @return bool
	 */
	public function plugin_enabled() {
		return $this->google->enabled;
	}

	/**
	 * Plugin's short description
	 *
	 * @return string
	 */
	public function plugin_description() {
		return $this->_( 'Interact with Google Analytics data.' );
	}

	/**
	 * Analytics constructor.
	 *
	 * @param array $argument
	 */
	public function __construct( array $argument = [] ) {
		if ( ! $this->plugin_enabled() ) {
			// Do nothing if Google is not enabled.
			return;
		}
		parent::__construct( $argument );
		// Add request handler
		add_action( 'gianism_extra_action', [ $this, 'handle_default' ], 10, 3 );
		// Register Ajax
		add_action( 'init', array( $this, 'boot_auto_cron' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ga_ajax' ) );
		add_action( 'wp_ajax_' . self::AJAX_CRON, array( $this, 'ga_cron' ) );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter(
			'gianism_setting_screen_views',
			function ( $views, $slug ) {
				if ( 'gianism' === $slug ) {
					$views['analytics'] = sprintf( '<i class="lsf lsf-graph"></i> %s', $this->_( 'Google Analytics' ) );
				}
				return $views;
			},
			10,
			2
		);
	}

	/**
	 * Handle action
	 *
	 * @param string $service
	 * @param string $action
	 * @param array  $args
	 */
	public function handle_default( $service, $action, $args ) {
		if ( 'google' !== $service ) {
			return;
		}
		switch ( $action ) {
			case 'analytics':
				$redirect_to = $this->input->get( 'redirect_to' );
				try {
					if ( $this->input->get( 'delete' ) ) {
						$this->save_token( '', true );
						$this->add_message( $this->_( 'Token deleted.' ) );
						wp_redirect( $redirect_to );
						exit;
					} else {
						$this->session->write( 'action', 'analytics-token' );
						$this->session->write( 'redirect_to', $this->input->get( 'redirect_to' ) );
						$this->ga_client->setApprovalPrompt( 'force' );
						$url = $this->ga_client->createAuthUrl();
						wp_redirect( $url );
						exit;
					}
				} catch ( \Exception $e ) {
					$this->add_message( $e->getMessage(), true );
					wp_redirect( $redirect_to );
					exit;
				}
				break;
			case 'analytics-token':
				try {
					$code = isset( $args['code'] ) ? $args['code'] : '';
					if ( $code && $this->ga_client->fetchAccessTokenWithAuthCode( $code ) ) {
						// O.K. save access token
						$token = $this->ga_client->getAccessToken();
						$this->save_token( $this->ga_client->getAccessToken() );
						// Add message
						$this->add_message( $this->_( 'Now you got token!' ) );
						// Redirect
						wp_redirect( $args['redirect_to'] );
						exit;
					} else {
						throw new \Exception( $this->_( 'Failed to authenticate with Google API.' ) );
					}
				} catch ( \Exception $e ) {
					$this->input->wp_die( $e->getMessage() );
				}
				break;
			case 'save-analytics':
				try {
					if ( ! current_user_can( 'manage_options' ) ) {
						throw new \Exception( $this->_( 'You have no permission.' ), 403 );
					}
					if ( update_option(
						'wpg_analytics_profile',
						array(
							'account' => $this->input->post( 'ga-account' ),
							'profile' => $this->input->post( 'ga-profile' ),
							'view'    => $this->input->post( 'ga-view' ),
						)
					) ) {
						$this->add_message( $this->_( 'Options updated.' ) );
						wp_redirect( $this->input->post( 'redirect_to' ) );
						exit;
					} else {
						throw new \Exception( $this->_( 'Nothing changed.' ), 500 );
					}
				} catch ( \Exception $e ) {
					$this->add_message( $e->getMessage(), true );
					wp_redirect( $this->input->request( 'redirect_to' ) );
					exit;
				}
				break;
			case 'create-table':
				try {
					if ( ! current_user_can( 'manage_options' ) ) {
						throw new \Exception( $this->_( 'You have no permission.' ), 403 );
					}
					if ( $this->table_exists() ) {
						throw new \Exception( $this->_( 'Table is already exists.' ), 500 );
					}
					// O.K. Let's create table
					$query = <<<SQL
CREATE TABLE `{$this->ga_table}` (
    ID BIGINT NOT NULL AUTO_INCREMENT,
    category VARCHAR(64) NOT NULL,
    object_id BIGINT UNSIGNED NOT NULL,
    object_value BIGINT NOT NULL,
    calc_date DATE NOT NULL,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    INDEX category_index (category, calc_date),
    INDEX object_index (category, object_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8
SQL;
					if ( ! function_exists( 'dbDelta' ) ) {
						require_once ABSPATH . 'wp-admin/includes/upgrade.php';
					}
					dbDelta( $query );
					if ( ! $this->table_exists() ) {
						throw new \Exception( $this->_( 'Sorry, but failed to create table.' ), 500 );
					}
					$this->add_message( $this->_( 'Table created.' ) );
					wp_redirect( $this->input->get( 'redirect_to' ) );
					exit;
				} catch ( \Exception $e ) {
					$this->add_message( $e->getMessage(), true );
					wp_redirect( $this->input->get( 'redirect_to' ) );
					exit;
				}
				break;
			default:
				// Do nothing.
				break;
		}
	}


	/**
	 * Save token
	 *
	 * @param string $token
	 * @param bool $delete Default false.
	 *
	 * @return bool
	 */
	public function save_token( $token, $delete = false ) {
		if ( $delete ) {
			return delete_option( 'wpg_analytics_token' );
		} else {
			return update_option( 'wpg_analytics_token', $token );
		}
	}


	/**
	 * Save profile
	 *
	 * @param string $profile
	 *
	 * @return bool
	 */
	public function save_profile( $profile ) {
		return update_option( 'wpg_analytics_profile', $profile );
	}

	/**
	 * Detect if table exists
	 *
	 * @return bool
	 */
	public function table_exists() {
		$query = 'SHOW TABLES LIKE %s';

		return (bool) $this->db->get_row( $this->db->prepare( $query, $this->ga_table ) );
	}

	/**
	 * Get redirect url to get analytics token
	 *
	 * @param string $redirect
	 * @param bool $delete Default false
	 *
	 * @return string
	 */
	public function token_url( $redirect, $delete = false ) {
		return $this->google->get_redirect_endpoint(
			'analytics',
			'google_analytics',
			array(
				'redirect_to' => $redirect,
				'delete'      => $delete,
			)
		);
	}

	/**
	 * Get redirect url to save analytics token
	 *
	 * @return string
	 */
	public function token_save_url() {
		return $this->google->get_redirect_endpoint( 'save-analytics', '', [] );
	}

	/**
	 * Table create
	 *
	 * @param string $redirect
	 *
	 * @return string
	 */
	public function table_create_url( $redirect ) {
		return $this->google->get_redirect_endpoint(
			'create-table',
			'',
			array(
				'redirect_to' => $redirect,
			)
		);
	}

	/**
	 * Enqueue script for Analytics
	 *
	 * @param $hook_suffix
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'settings_page_gianism' === $hook_suffix ) {
			// Script
			wp_enqueue_script( 'gianism-analytics-helper', $this->url . 'assets/js/admin-analytics-helper.js', [ 'jquery-form' ], $this->version, true );
			wp_localize_script(
				'gianism-analytics-helper',
				'Gianalytics',
				array(
					'endpoint' => admin_url( 'admin-ajax.php' ),
					'action'   => self::AJAX_ACTION,
					'nonce'    => wp_create_nonce( self::AJAX_ACTION ),
				)
			);
		}
	}

	/**
	 * Ajax action
	 */
	public function ga_ajax() {
		header( 'Content-Type', 'application/json' );
		try {
			// Check nonce
			if ( ! wp_verify_nonce( $this->input->get( 'nonce' ), self::AJAX_ACTION ) || ! current_user_can( 'manage_options' ) ) {
				throw new \Exception( $this->_( 'You have no permission.' ), 403 );
			}
			// Check data to retrieve
			$result = null;
			switch ( $this->input->get( 'target' ) ) {
				case 'account':
					$properties = $this->ga
						->management_webproperties
						->listManagementWebproperties( $this->input->get( 'account_id' ) );
					$json       = array(
						'success' => true,
						'items'   => $properties->getItems(),
					);
					break;
				case 'profile':
					$views = $this->ga
						->management_profiles
						->listManagementProfiles( $this->input->get( 'account_id' ), $this->input->get( 'profile_id' ) );
					$json  = array(
						'success' => true,
						'items'   => $views->getItems(),
					);
					break;
				default:
					throw new \Exception( $this->_( 'Invalid action.' ), 500 );
			}
		} catch ( \Exception $e ) {
			$json = array(
				'success'    => false,
				'error_code' => $e->getCode(),
				'message'    => $e->getMessage(),
			);
		}
		wp_send_json( $json );
		exit;
	}

	/**
	 * Register cron automatically
	 */
	public function boot_auto_cron() {
		$template_dir   = get_template_directory() . '/app/gianism';
		$stylesheet_dir = get_stylesheet_directory() . '/app/gianism';
		$scan           = function ( $dir ) {
			$files = [];
			if ( is_dir( $dir ) ) {
				foreach ( scandir( $dir ) as $file ) {
					if ( preg_match( '/\.php/', $file ) ) {
						$class_name           = str_replace( '.php', '', $file );
						$files[ $class_name ] = $dir . '/' . $file;
					}
				}
			}

			return $files;
		};
		/**
		 * Filter class for auto loader
		 *
		 * @filter gianism_analytics_auto_loader_class
		 * @param array $class_names
		 * @param string $scope ajax or cron
		 */
		$ajax_classes = apply_filters( 'gianism_analytics_auto_loader_class', [], 'ajax' );
		$this->crons  = apply_filters( 'gianism_analytics_auto_loader_class', [], 'cron' );
		// Parse directory
		$classes = $scan( $template_dir );
		if ( $template_dir !== $stylesheet_dir ) {
			$classes = array_merge( $classes, $scan( $stylesheet_dir ) );
		}
		if ( ! empty( $classes ) ) {
			foreach ( $classes as $class_name => $file ) {
				if ( ! file_exists( $file ) ) {
					continue;
				}
				require $file;
				if ( class_exists( $class_name ) ) {
					$reflection = new \ReflectionClass( $class_name );
					if ( $reflection->isAbstract() ) {
						continue;
					}
					if ( $reflection->isSubclassOf( 'Gianism\\Cron\\Daily' ) ) {
						$this->crons[] = $class_name;
					} elseif ( $reflection->isSubclassOf( 'Gianism\\Api\\Ga' ) ) {
						$ajax_classes[] = $class_name;
					}
				}
			}
		}
		foreach ( $this->crons as $class_name ) {
			$class_name::get_instance();
		}
		if ( ! empty( $ajax_classes ) ) {
			$this->ajaxes = $ajax_classes;
			add_action(
				'admin_init',
				function () use ( $ajax_classes ) {
					if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
						foreach ( $ajax_classes as $ajax_class ) {
							$ajax_class::get_instance();
						}
					}
				}
			);
		}
	}

	/**
	 * Google analytics cron check
	 */
	public function ga_cron() {
		header( 'Content-Type', 'application/json' );
		try {
			if ( ! current_user_can( 'manage_options' ) ) {
				throw new \Exception( $this->_( 'You have no permission.' ), 403 );
			}
			if ( ! wp_verify_nonce( $this->input->post( '_wpnonce' ), self::AJAX_CRON ) || ! isset( $this->crons[ $this->input->post( 'cron' ) ] ) ) {
				throw new \Exception( $this->_( 'Such action does not exist.' ), 403 );
			}
			$class_name = $this->crons[ $this->input->post( 'cron' ) ];
			/** @var \Gianism\Cron\Daily $instance */
			$instance = $class_name::get_instance();
			$json     = array(
				'success' => true,
				'items'   => $instance->get_results(),
			);
		} catch ( \Exception $e ) {
			$json = array(
				'success'    => false,
				'error_code' => $e->getCode(),
				'message'    => $e->getMessage(),
			);
		}
		echo json_encode( $json );
		exit;
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
			case 'google':
				return $this->service->get( 'google' );
				break;
			case 'ga_table':
				return $this->db->prefix . 'wpg_ga_ranking';
				break;
			case 'ga_profile':
				return $this->option->get(
					'wpg_analytics_profile',
					[
						'account' => 0,
						'profile' => 0,
						'view'    => 0,
					]
				);
				break;
			case 'ga_token':
				return $this->option->get( 'wpg_analytics_token', '' );
				break;
			case 'ga':
				if ( $this->ga_token && is_null( $this->_ga ) ) {
					$this->ga_client->setAccessToken( $this->ga_token );
					if ( $this->ga_client->isAccessTokenExpired() ) {
						// Refresh token if expired.
						$refresh_token           = $this->ga_client->getRefreshToken();
						$token                   = $this->ga_client->fetchAccessTokenWithRefreshToken( $refresh_token );
						$token ['refresh_token'] = $refresh_token;
						$this->save_token( $token );
					}
					$this->_ga = new \Google_Service_Analytics( $this->ga_client );
				}
				return $this->_ga;
				break;
			case 'ga_client':
				// Init library
				if ( is_null( $this->_ga_client ) ) {
					$this->_ga_client = new \Google_Client();
					$this->_ga_client->setClientId( $this->google->ggl_consumer_key );
					$this->_ga_client->setClientSecret( $this->google->ggl_consumer_secret );
					$this->_ga_client->setRedirectUri( $this->google->get_redirect_endpoint() );
					$this->_ga_client->setApplicationName( 'Gianism Analytics' );
					$this->_ga_client->setScopes(
						array(
							'https://www.googleapis.com/auth/analytics.readonly',
						)
					);
					$this->_ga_client->setAccessType( 'offline' );
				}

				return $this->_ga_client;
				break;
			case 'ga_accounts':
				static $ga_accounts = null;
				if ( ! is_null( $ga_accounts ) ) {
					return $ga_accounts;
				}
				$ga_accounts = [];
				if ( $this->ga_token ) {
					try {
						$accounts = $this->ga->management_accounts->listManagementAccounts();
						if ( count( $accounts->getItems() ) > 0 ) {
							$ga_accounts = $accounts;
						}
					} catch ( \Exception $e ) {
						// Do nothing.
						error_log( $e->getMessage(), $e->getCode() );
					}
				}

				return $ga_accounts;
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}
}
