<?php

namespace Gianism\Service;

/**
 * Description of facebook_controller
 *
 * @package Gianism\Service
 * @since 2.0.0
 * @author Takahashi Fumiki
 * @property-read string $cert_path Path to cert file.
 * @property-read \Facebook $api Facebook object
 * @property-read \Facebook|\WP_Error $admin Facebook object for Admin Use
 * @property-read int $admin_id
 * @property-read array|false $admin_account
 * @property-read array $admin_pages
 */
class Facebook extends Common\Mail
{
	/**
     * Service name to display
     *
     * @var string
     */
    public $verbose_service_name = 'Facebook';

	/**
     * Facebook application ID
     *
	 * @var string
	 */
    protected $fb_app_id = '';
	
	/**
     * Facebook application secret
     *
	 * @var string
	 */
	protected  $fb_app_secret = '';
	
	/**
	 * Page ID of Fan gate.
     *
	 * @var int
	 */
	protected  $fb_fan_gate = 0;

	/**
	 * Whether if use global setting
	 *
	 * @var bool
	 */
	protected  $fb_use_api = false;

	/**
	 * Facebook API Controller
     *
     * @ignore
	 * @var \Facebook
	 */
	private $_api = null;

	/**
	 * Facebook API for user
	 *
	 * @ignore
	 * @var \Facebook
	 */
	private $_admin_api = null;

	/**
	 * Meta key of usermeta for facebook id
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
	 * @var array
	 */
	private $_signed_request = array();

    /**
     * Key to retrieve
     *
     * @var array
     */
    protected $option_keys = array('fb_app_id', 'fb_app_secret', 'fb_fan_gate', 'fb_use_api');

    /**
     * Init action
     */
    protected function init_action(){
		//Add Hook on Fan Gate
		if( $this->fb_fan_gate ){
			add_action('template_redirect', array($this, 'fan_gate_helper'));
		}
	    // Update option
	    if( $this->fb_use_api ){
		    add_action('admin_init', array($this, 'update_facebook_admin'));
	    }
    }


    /**
     * Disconnect user from this service
     *
     * @param int $user_id
     * @return void
     */
    public function disconnect($user_id){
        delete_user_meta(get_current_user_id(), $this->umeta_id);
        delete_user_meta(get_current_user_id(), $this->umeta_mail);
    }

    /**
     * Returns API endpoint
     *
     * @param string $action
     * @return bool|false|string
     */
    protected function get_api_url($action){
        switch($action){
            case 'connect':
            case 'login':
                return $this->api->getLoginUrl(array(
                    'scope' => 'email',
                    'redirect_uri' => $this->get_redirect_endpoint(),
                ));
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Handle publish action
     *
     * @param \WP_Query $wp_query
     */
    public function handle_publish( \WP_Query $wp_query ){
        try{
            $url = $this->api->getLoginUrl(array(
                'scope' => 'publish_actions',
                'redirect_uri' => $this->get_redirect_endpoint(),
            ));
            $this->session_write('redirect_to', $this->get('redirect_to'));
            $this->session_write('action', 'publish');
            $this->session_write('hook', $this->get('hook'));
            $this->session_write('args', $_GET);
            wp_redirect($url);
            exit;
        }catch (\Exception $e){
            $this->wp_die($e->getMessage());
        }
    }

	/**
	 * @param \WP_Query $wp_query
	 */
	public function handle_admin( \WP_Query $wp_query ){
		try{
			$args = array(
				'redirect_uri' => $this->get_redirect_endpoint(),
				'scope' => 'manage_pages',
			);
			if( $this->request('publish') ){
				$args['scope'] .= ',publish_actions';
			}
			$url = $this->api->getLoginUrl($args);
			$this->session_write('redirect_to', $this->get('redirect_to'));
			$this->session_write('action', 'admin');
			wp_redirect($url);
			exit;
		}catch (\Exception $e){
			$this->wp_die($e->getMessage());
		}
	}

    /**
     * Communicate with Facebook API
     *
     * @global \wpdb $wpdb
     * @param string $action
     * @return void
     */
    protected function handle_default( $action ){
        global $wpdb;
        // Get common values
        $redirect_url = $this->session_get('redirect_to');
        // Process actions
        switch( $action ){
            case 'login': // Make user login
                try{
                    // Is logged in?
                    if( is_user_logged_in() ){
                        throw new \Exception($this->_('You are already logged in'));
                    }
                    // Get ID
                    $facebook_id = $this->api->getUser();
                    if( !$facebook_id ){
                        throw new \Exception($this->api_error_string());
                    }
                    // If user doesn't exists, try to register.
                    if( !($user_id = $this->get_meta_owner($this->umeta_id, $facebook_id)) ){
                        // Test
                        $this->test_user_can_register();
                        try{
                            $profile = $this->api->api('/me');
                        }catch(\FacebookApiException $e){
                            $profile = $this->api->api('/'.$facebook_id);
                        }
                        // Check email
                        if( !isset($profile['email']) || !is_email($profile['email'])){
                            throw new \Exception($this->mail_fail_string());
                        }
                        $email = (string) $profile['email'];
                        if( $this->mail_owner($email) ){
                            throw new \Exception($this->duplicate_account_string());
                        }
                        //Not found, Create New User
                        require_once(ABSPATH . WPINC . '/registration.php');
                        //There might be no available string for login name, so use Facebook id for login.
                        $user_name = 'fb-'.$facebook_id;
                        // Try Username
                        foreach( array('username', 'name') as $key ){
                            if( isset($profile[$user_name])){
                                $safe_name = sanitize_user($profile[$key]);
                                if( !empty($safe_name) && !username_exists($safe_name) ){
                                    // This can be used as user name.
                                    $user_name = $safe_name;
                                    break;
                                }
                            }
                        }
                        //Check if username exists
                        $user_id = wp_create_user($user_name, wp_generate_password(), $email);
                        if( is_wp_error($user_id) ){
                            throw new \Exception($this->registration_error_string());
                        }
                        // Ok, let's update usermeta
                        update_user_meta($user_id, $this->umeta_id, $facebook_id);
                        update_user_meta($user_id, $this->umeta_mail, $email);
                        $wpdb->update(
                            $wpdb->users,
                            array(
                                'display_name' => $profile['name'],
                                'user_url' => $profile['link']
                            ),
                            array('ID' => $user_id),
                            array('%s', '%s'),
                            array('%d')
                        );
                        update_user_meta($user_id, 'nickname', $profile['name']);
                        $this->user_password_unknown($user_id);
                        $this->hook_connect($user_id, $profile, true);
                        $this->welcome($profile['name']);
                    }
                    // Make user logged in
                    wp_set_auth_cookie($user_id, true);
                    $redirect_url = $this->filter_redirect($redirect_url, 'login');
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                    $redirect_url = wp_login_url($redirect_url, true);
                }
                // Redirect user
                wp_redirect($redirect_url);
                exit;
                break;
            case 'connect': // Connect user account to Facebook
                try{
                    // Get facebook user
                    $fb_uid = $this->api->getUser();
                    // This FB ID eixsts?
                    if( $this->id_owner($fb_uid) ){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    // Set session if possible
                    if( session_id() && !isset($_SESSION['uid'])){
                        $_SESSION['uid'] = $fb_uid;
                    }
                    // Get profile
                    try{
                        $profile = $this->api->api('/me');
                    }catch(\FacebookApiException $e){
                        $profile = $this->api->api('/'.$fb_uid);
                    }
                    // Check email
                    if( !isset($profile['email']) || !is_email($profile['email']) ){
                        throw new \Exception($this->mail_fail_string());
                    }
                    // Check if other user has these as meta_value
                    if(  ($email_owner = $this->mail_owner($profile['email'])) && get_current_user_id() != $email_owner ){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    // Now let's save userdata
                    update_user_meta(get_current_user_id(), $this->umeta_id, $fb_uid);
                    update_user_meta(get_current_user_id(), $this->umeta_mail, $profile['email']);
                    // Fires hook
                    $this->hook_connect(get_current_user_id(), $this->api);
                    // Save message
                    $this->welcome($profile['name']);
                }catch (\FacebookApiException $e){
                    $this->auth_fail($this->api_error_string());
                }catch(\Exception $e){
                    $this->auth_fail($e->getMessage());
                }
                // Connection finished. Let's redirect.
                if( !$redirect_url ){
                    $redirect_url = admin_url('profile.php');
                }
                // Apply filter
                $redirect_url = $this->filter_redirect($redirect_url, 'connect');
                wp_redirect($redirect_url);
                exit;
                break;
            case 'publish':
                try{
                    $hook = $this->session_get('hook');
                    $args = $this->session_get('args');
                    $facebook_id = $this->api->getUser();
                    if(!$facebook_id){
                        throw new \Exception($this->api_error_string());
                    }
                    // Check permission exists, save it.
                    $perms = $this->api->api('/me/permissions');
                    // Get hook
                    if($perms && isset($perms['data'][0]['publish_actions']) && $perms['data'][0]['publish_actions']){
                        //Save access token.
                        update_user_meta(get_current_user_id(), $this->umeta_token, $this->api->getAccessToken());
                        //If action is set, do it.
                        if( !empty($hook) ){
                            do_action(strval($hook), $this->api, $args);
                        }
                    }
                }catch(\Exception $e){
                    $this->add_message($e->getMessage(), true);
                }
                wp_redirect($redirect_url);
                exit;
                break;
	        case 'admin':
				try{
					if( !($token = $this->api->getAccessToken()) // Get normal token
					    || false ===  $this->api->setExtendedAccessToken() // Extend token
					    || !($token = $this->api->getAccessToken()) // Renew token
					){
						throw new \Exception($this->api_error_string());
					}
					// O.K. Token ready and save it.
					update_option('gianism_facebook_admin_token', $token);
					update_option('gianism_facebook_admin_refreshed', current_time('timestamp'));
					$this->add_message($this->_('Access token is saved.'));
				}catch ( \Exception $e ){
					$this->add_message($e->getMessage(), true);
				}
		        wp_redirect($redirect_url);
		        exit;
		        break;
            default:
                // No action is set, error.
                $this->wp_die(sprintf($this->_('Sorry, but wrong access. Please go back to <a href="%s">%s</a>.'), home_url('/', 'http'), get_bloginfo('name')), 500, false);
                break;
        }

    }
	
	/**
	 * Returns login url which get additional permission
     *
	 * @param string $redirect_url
	 * @param string $action This action hook will booted.
	 * @param array $args Additional key-value
	 * @return string
	 */
	public function get_publish_permission_link($redirect_url = null, $action = '', $args = array()){
		if(!$redirect_url){
			$redirect_url = admin_url('profile.php');
		}
        $arguments = array(
            'redirect_to' => $redirect_url,
        );
        if( !empty($action) ){
            $arguments['hook'] = $action;
        }
        return $this->get_redirect_endpoint('publish', $this->service_name.'_publish', array_merge($arguments, $args));
	}

	/**
	 * Get admin connect link
	 *
	 * @param bool $require_publish
	 * @return string
	 */
	public function get_admin_connect_link($require_publish = false){
		$arguments = array(
			'redirect_to' => admin_url('options-general.php?page=gianism&view=fb-api'),
		);
		if( $require_publish ){
			$arguments['publish'] = 'true';
		}
		return $this->get_redirect_endpoint('admin', $this->service_name.'_admin', $arguments);
	}

	/**
	 * Update admin account id.
	 */
	public function update_facebook_admin(){
		if( 'gianism' == $this->get('page') && wp_verify_nonce($this->post('_wpnonce'), 'gianism_fb_account') ){
			update_option('gianism_facebook_admin_id', $this->post('fb_account_id'));
			$this->add_message($this->_('Saved facebook account to use.'));
			wp_redirect(admin_url('options-general.php?page=gianism&view=fb-api'));
			exit;
		}
	}
	
	/**
	 * Returns if user like my page. Only available on Facebook Tab page or application.
     *
	 * @return bool
	 */
	public function is_user_like_me_on_fangate(){
		if($this->fb_fan_gate){
			$page = $this->signed_request('page');
			return (isset($page['liked']) && $page['liked']);
		}else{
			return false;
		}
	}
	
	/**
	 * Returns if current facebook user is wordpress registered user.
	 * 
	 * If current Facebook user is registerd on your WordPress, returns user ID on WordPress.
	 * 
	 * @global \wpdb $wpdb
	 * @return int
	 */
	public function is_registered_user_on_fangate(){
        /** @var \wpdb $wpdb */
		global $wpdb;
		if($this->fb_fan_gate){
			$uid = $this->signed_request('user_id');
			$sql = <<<EOS
				SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = %s AND meta_value = %s
EOS;
			return $wpdb->get_var($wpdb->prepare($sql, $this->umeta_id, $uid));
		}else{
			return false;
		}
	}
	
	/**
	 * Return true if current user is not logged in Facebook
     *
	 * @return boolean
	 */
	public function is_guest_on_fangate(){
        return $this->fb_fan_gate && (false == (boolean)$this->signed_request('user_id'));
	}
	
	/**
	 * Returns if current page is fan gate.
     *
	 * @return bool
	 */
	public function is_fangate(){
		if($this->fb_fan_gate){
			$page = $this->signed_request('page');
			if($page){
				return is_page($this->fb_fan_gate);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

    /**
     * Return user id of email if exists
     *
     * @param string $email
     * @return int
     */
    public function mail_owner($email){
        if( $owner = email_exists($email) ){
            return $owner;
        }
        return $this->get_meta_owner($this->umeta_mail, $email);
    }

    /**
     * Returns user id with facebook id
     *
     * @param string $fb_id
     * @return int
     */
    public function id_owner($fb_id){
        return $this->get_meta_owner($this->umeta_id, $fb_id);
    }
	
	/**
	 * Get signed request
     *
     * @param string $key
	 * @return string
	 */
	private function signed_request($key){
		if( empty($this->_signed_request) ){
			$this->_signed_request = $this->api->getSignedRequest();
		}
		return isset($this->_signed_request[$key]) ? $this->_signed_request[$key]: null;
	}
	
	/**
	 * Initialize Facebook Fangate Scripts
	 */
	public function fan_gate_helper(){
		if(is_page($this->fb_fan_gate)){
			$this->js = true;
		}
		$this->scripts .= <<<JS
			FB.Canvas.setAutoGrow();
JS;
	}
	
	/**
	 * Print JS on footer of both admin panel and public page.
	 * 
	 * Paramater 'js' should be true and if 'js' is 'no-fb-root', 
	 * div#fb-root won't be displayed.
	 * 
	 * @return void
	 */
	public function print_script(){
		if(!empty($this->message)){
			echo $this->generate_message_script($this->message);
		}
	}

    /**
     * Returns if user is connected to Facebook
     *
     * @param int $user_id
     * @return bool
     */
    public function is_connected($user_id){
        return (bool)$this->get_facebook_id($user_id);
    }

	/**
	 * Returns User's Facebook ID
     *
	 * @param int $wp_user_id
	 * @return int
	 */
	public function get_facebook_id($wp_user_id){
        return get_user_meta($wp_user_id, $this->umeta_id, true) ?: 0;
	}
	
	/**
	 * Returns Facebook mail
     *
	 * @param int $wp_user_id
	 * @return string
	 */
	public function get_user_mail($wp_user_id){
        return (string) get_user_meta($wp_user_id, $this->umeta_mail, true);
	}
	
	/**
	 * Save Facebook Mail
     *
	 * @param string $mail
	 * @param int $user_id 
	 * @return void
	 */
	public function set_user_mail($mail, $user_id){
		update_user_meta($user_id, $this->umeta_mail, $mail);
	}
	
	/**
	 * Save Facebook ID
     *
	 * @param string $fb_user_id
	 * @param int $wp_user_id
	 * @return void
	 */
	public function set_user_id($fb_user_id, $wp_user_id){
		update_user_meta($wp_user_id, $this->umeta_id, $fb_user_id);
	}

    /**
     * Getter
     *
     * @param string $name
     * @return mixed|string
     */
    public function __get($name){
        switch($name){
            case 'cert_path':
                return $this->dir.implode(DIRECTORY_SEPARATOR, array(
                    'vendor',
                    'facebook-php-sdk',
                    'src',
                   'fb_ca_chain_bundle.crt',
                ));
                break;
            case 'api':
                if( is_null($this->_api) ){
                    $this->_api = new \Facebook(array(
                        'appId'  => $this->fb_app_id,
                        'secret' => $this->fb_app_secret,
                        'cookie' => true
                    ));
                }
                return $this->_api;
                break;
	        case 'admin':
				if( is_null($this->_admin_api) ){
					if( !$this->fb_use_api || !( $token = get_option('gianism_facebook_admin_token', false)) ){
						return new \WP_Error(404, $this->_('Token is not set. Please get it.'));
					}
					// Check last updated
					$updated = get_option('gianism_facebook_admin_refreshed', 0);
					if( !$updated || current_time('timestamp') > $updated + (60 * 60 * 24 * 60) ){
						return new \WP_Error(410, $this->_('Token is outdated. Please update it.'));
					}
					try{
						$this->_admin_api = new \Facebook(array(
							'appId'  => $this->fb_app_id,
							'secret' => $this->fb_app_secret,
							'cookie' => true
						));
						$this->_admin_api->setAccessToken($token);
					}catch ( \Exception $e ){
						return new \WP_Error(500, $e->getMessage());
					}
				}
				return $this->_admin_api;
		        break;
	        case 'admin_account':
				if( is_wp_error($this->admin) ){
					return false;
				}
				try{
					return $this->admin->api('/me');
				}catch ( \Exception $e ){
					return false;
				}
		        break;
	        case 'admin_pages':
				if( is_wp_error($this->admin) ){
					return array();
				}else{
					try{
						$accounts =  $this->admin->api('/me/accounts');
						if( !isset($accounts['data']) || empty($accounts['data']) ){
							return array();
						}else{
							return $accounts['data'];
						}
					}catch ( \Exception $e ){
						return array();
					}
				}
		        break;
	        case 'admin_id':
				return get_option('gianism_facebook_admin_id', 'me');
		        break;
	        default:
                return parent::__get($name);
                break;
        }
    }
}
