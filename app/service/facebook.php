<?php

namespace Gianism\Service;

/**
 * Description of facebook_controller
 *
 * @package Gianism\Service
 * @author Takahashi Fumiki
 * @property-read string $cert_path Path to cert file.
 * @property-read \Facebook $api Facebook object
 */
class Facebook extends Common
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
	 * Facebook API Controller
     *
     * @ignore
	 * @var \Facebook
	 */
	private $_api = null;

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
    protected $option_keys = array('fb_app_id', 'fb_app_secret', 'fb_fan_gate');

    /**
     * Init action
     */
    protected function init_action(){
		//Add Hook on Fan Gate
		if($this->fb_fan_gate){
			add_action('template_redirect', array($this, 'fan_gate_helper'));
		}
    }

    /**
     * Redirect user to facebook
     *
     * @param \WP_Query $wp_query
     */
    protected function handle_connect( \WP_Query $wp_query ){
        // Set redirect URL
        $url = $this->api->getLoginUrl(array(
            'scope' => 'email',
            'redirect_uri' => $this->get_redirect_endpoint(),
        ));
        $this->session_write('redirect_to', $this->get('redirect_to'));
        $this->session_write('action', 'connect');
        wp_redirect($url);
        exit;
    }

    /**
     * Delete facebook account
     */
    protected function handle_disconnect(){
        $redirect_url = $this->get('redirect_to') ?: admin_url("profile.php");
        try{
            // Is user logged in?
            if( !is_user_logged_in() ){
                throw new \Exception($this->_('You must be logged in.'));
            }
            // Has safe book id
            if( !$this->is_connected(get_current_user_id()) ){
                throw new \Exception(sprintf($this->_('Your account is not connected with %s'), $this->verbose_service_name));
            }
            // O.K.
            delete_user_meta(get_current_user_id(), $this->umeta_id);
            delete_user_meta(get_current_user_id(), $this->umeta_mail);
            $this->add_message(sprintf($this->_("Your account is now unlinked from %s."), $this->verbose_service_name));
        }catch (\Exception $e){
            $this->add_message($e->getMessage(), true);
        }
        wp_redirect($redirect_url);
        exit;
    }


    /**
     * Communicate with Facebook API
     */
    protected function handle_default(){
        // Get common values
        $action = $this->session_get('action');
        $redirect_url = $this->session_get('redirect_to');
        // Process actions
        switch( $action ){
            case 'connect': // Connect user account to Facebook
                try{
                    // Is user logged in?
                    if( !is_user_logged_in() ){
                        throw new \Exception($this->_('To connect facebook, you must be logged in.'));
                    }
                    // Is user already connect?
                    if( $this->is_connected(get_current_user_id()) ){
                        throw new \Exception(sprintf($this->_('You account is already connected with %s'), $this->verbose_service_name));
                    }
                    // Get facebook user
                    $fb_uid = $this->api->getUser();
                    // This FB ID eixsts?
                    if( $this->id_owner($fb_uid) ){
                        throw new \Exception(sprintf($this->_('This %s account is already connected with others.'), $this->verbose_service_name));
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
                        throw new \Exception($this->_('Cannot retrieve email address.'));
                    }
                    // Check if other user has these as meta_value
                    if(  ($email_owner = $this->mail_owner($profile['email'])) && get_current_user_id() != $email_owner ){
                        throw new \Exception($this->_('E-mail address is already used by others.'));
                    }
                    // Now let's save userdata
                    update_user_meta(get_current_user_id(), $this->umeta_id, $fb_uid);
                    update_user_meta(get_current_user_id(), $this->umeta_mail, $profile['email']);
                    // Fires hook
                    $this->hook_connect(get_current_user_id(), $this->api);
                    // Save message
                    $this->add_message(sprintf($this->_('Welcome!, %s'), $profile['name']));
                }catch (\FacebookApiException $e){
                    $this->add_message( $this->_("Oops, Failed to Authenticate.").' '.sprintf($this->_('%s API returns error.'), $this->verbose_service_name), true);
                }catch(\Exception $e){
                    $this->add_message( $this->_("Oops, Failed to Authenticate.").' '.$e->getMessage(), true);
                }
                // Connection finished. Let's redirect.
                if( !$redirect_url ){
                    $redirect_url = admin_url('profile.php');
                }
                wp_redirect($redirect_url);
                exit;
                break;
            default:
                break;
        }

    }

	/**
	 * Executed on init hook.
	 * @global wpdb $wpdb
	 * @global int $user_ID
	 * @global WP_Gianism $giasnism
	 */
	public function hoge(){
		global $user_ID, $wpdb, $gianism;
		switch($this->get_action()){
			case "facebook_connect":

				break;
			case "facebook_disconnect":
				if(is_user_logged_in() && isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'facebook_disconnect')){
					delete_user_meta($user_ID, $this->umeta_id);
					delete_user_meta($user_ID, $this->umeta_mail);
					$this->message = sprintf($gianism->_("Disconected your %s account."), "Facebook");
				}
				break;
			case "facebook_login":
				if(!is_user_logged_in()){
					$this->message = $gianism->_('Oops, Failed to Authenticate.');
					$facebook_id = $this->facebook()->getUser();
					if($facebook_id){
						//Get Facebook ID, So try to find registered user.
						global $wpdb;
						$sql = <<<EOS
							SELECT user_id FROM {$wpdb->usermeta}
							WHERE meta_key = %s AND meta_value = %s
EOS;
						$user_id = $wpdb->get_var($wpdb->prepare($sql, $this->umeta_id, $facebook_id));
						if(!$user_id){
							//Cant Find user, try to find by email
							try{
								try{
									$profile = $this->facebook()->api('/me');
								}catch(FacebookApiException $e){
									$profile = $this->facebook()->api('/'.$facebook_id);
								}
								if(isset($profile['email'])){
									$email = (string)$profile['email'];
									// Try to find registered user.
									// Same email must not be found.
									$sql = <<<EOS
										SELECT user_id FROM {$wpdb->usermeta}
										WHERE meta_key = %s AND meta_value = %s
EOS;
									if(!email_exists($email && !$wpdb->get_var($wpdb->prepare($sql, $this->umeta_mail, $email)))){
										//Not found, Create New User
										require_once(ABSPATH . WPINC . '/registration.php');
										//Get Username
										if(isset($profile['username'])){
											//if set, use username. but this is optional setting.
											$user_name = $profile['username'];
										}elseif(isset($profile['name']) && !username_exists($profile['name']) && preg_match("/^[a-zA-Z0-9 ]+$/", $profile['name'])){
											//If name is alpabetical, use it.
											$user_name = $profile['name'];
										}else{
											//There is no available string for login name, so use Facebook id for login.
											$user_name = 'fb-'.$facebook_id;
										}
										//Check if username exists
										$user_id = wp_create_user(sanitize_user($user_name), wp_generate_password(), $email);
										if(!is_wp_error($user_id)){
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
											do_action('wpg_connect', $user_id, $profile, 'facebook', true);
										}else{
											$this->message .= '\n'.$user_id->get_error_message();
										}
									}else{
										$this->message .= '\n'.sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), "Facebook");
									}
								}else{
									$this->message .= '\n'.$gianism->_('Cannot get e-mail.').$gianism->_('Please try again later.');
								}
							}catch(FacebookApiException $e){
								//Can't get Profile, so, error.
								$this->message .= '\n'.$e->getMessage();
							}
						}
					}else{
						$this->message .= '\n'.$gianism->_('Cannot get Facebook ID.').$gianism->_('Please try again later.');
					}
					if($user_id && !is_wp_error($user_id)){
						$this->message = '';
						wp_set_auth_cookie($user_id, true);
						if(isset($_GET['redirect_to'])){
							header('Location: '.$_GET['redirect_to']);
							die();
						}else{
							wp_set_current_user($user_id);
						}
					}
				}
				break;
			case 'facebook_publish':
				$facebook_id = $this->api->getUser();
				if(!is_user_logged_in() || !$facebook_id){
					wp_die(get_status_header_desc(403), get_bloginfo('name'), array('response' => 403, 'back_link' => true));
				}
				try{
					$redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : admin_url('profile.php');
					//Check permission exists, save it.
					$perms = $this->facebook()->api('/me/permissions');
					if($perms && isset($perms['data'][0]['publish_actions']) && $perms['data'][0]['publish_actions']){
						//Save access token.
						update_user_meta(get_current_user_id(), $this->umeta_token, $this->facebook()->getAccessToken());
						//If action is set, do it.
						if(isset($_REQUEST['action'])){
							do_action(strval($_REQUEST['action']), $this->facebook(), $_REQUEST);
						}
					}
					header('Location: '.$redirect_to);
					exit();
				}catch(FacebookApiException $e){
					wp_die(get_status_header_desc(500).": ".$e->getMessage(), get_bloginfo('name'), array('response' => 500, 'back_link' => true));
				}
				break;
		}
	}

	/**
	 * Show Login Button on Facebook.
	 * @global WP_Gianism $gianism 
	 */
	public function login_form(){
		global $gianism;
		$redirect = $this->get_redirect_to(admin_url('profile.php'));
		$login_url = wp_login_url($redirect)."&wpg=facebook_login";	
		$url = $this->facebook()->getLoginUrl(array(
			'scope' => 'email',
			'redirect_uri' => $login_url,
		));
		$link_text = $gianism->_('Log in with Facebook');
		//Show Login Button
		$this->js = true;
		$mark_up = <<<EOS
		<a class="fb_button fb_button_medium" id="fb-login" href="{$url}">
			<span class="fb_button_text">{$link_text}</span>
		</a>
EOS;
		echo $this->filter_link($mark_up, $url, $link_text, 'facebook');
	}
	
	/**
	 * Returns login url which get additional permission
     *
	 * @param string $redirect_url
	 * @param string $action This action hook will booted.
	 * @param arrray $args Additional key-value
	 * @return string
	 */
	public function get_publish_permission_link($redirect_url = null, $action = '', $args = array()){
		if(!$redirect_url){
			$redirect_url = admin_url('profile.php');
		}
		$url = home_url('/', ($this->is_ssl_required() ? 'https' : 'http'))."?wpg=facebook_publish&redirect_to=".rawurlencode($redirect_url);
		if(!empty($action)){
			$url .= '&action='.rawurlencode($action);
		}
		if(!empty($args)){
			foreach($args as $key => $val){
				$url .= "&".rawurlencode($key)."=".rawurlencode($val);
			}
		}
		return $this->facebook()->getLoginUrl(array(
			'scope' => 'publish_actions',
			'redirect_uri' => $url,
		));
	}
	
	/**
	 * Returns if user like my page. Only available on Facebook Tab page or application.
     *
	 * @return bool
	 */
	public function is_user_like_me_on_fangate(){
		if($this->fan_gate){
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
		$this->scripts .= <<<EOS
			FB.Canvas.setAutoGrow();
EOS;
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
		$locale = 'ja_JP';
		if($this->js !== false):
		?>
		<?php if($this->js !== 'no-fb-root'):?>
		<div id="fb-root"></div>
		<?php endif;?>
		<script type="text/javascript">
		window.fbAsyncInit = function() {
			<?php if(is_ssl()):?>
			FB._https = true;
			<?php endif;?>
			FB.init({
				appId: '<?php echo $this->fb_app_id ?>',
				cookie: true, 
				xfbml: true,
				oauth: true
			});
			<?php echo $this->scripts; ?>
		};
		(function(){
			var e = document.createElement('script');
			e.async = true;
			e.src = document.location.protocol + "//connect.facebook.net/<?php echo $locale; ?>/all.js";
			document.getElementById('fb-root').appendChild(e);
		})();
		</script>
		<?php
		endif;
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
                if(is_null($this->_api)){
                    $this->_api = new \Facebook(array(
                        'appId'  => $this->fb_app_id,
                        'secret' => $this->fb_app_secret,
                        'cookie' => true
                    ));
                }
                return $this->_api;
                break;
            default:
                return parent::__get($name);
                break;
        }
    }
}
