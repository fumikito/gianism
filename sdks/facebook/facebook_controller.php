<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."gianism_controller.php";

/**
 * Description of facebook_controller
 *
 * @package gianism
 */
class Facebook_Controller extends Gianism_Controller{
	
	/**
	 * @var string
	 */
	private $app_id = '';
	
	/**
	 * @var string
	 */
	private $app_secret = '';
	
	/**
	 * Facebook API Controller
	 * @var Facebook
	 */
	public $_api = null;
	
	/**
	 * Page ID of Fan gate.
	 * @var int
	 */
	private $fan_gate = 0;
	
	/**
	 * Path to cert file.
	 * @var string
	 */
	private $cert_path = '';
	
	/**
	 * Meta key of usermeta for facebook id
	 * @var string
	 */
	public $umeta_id = '_wpg_facebook_id';
	
	/**
	 * Meta key of usermeta for facebook mail
	 * @var string
	 */
	public $umeta_mail = '_wpg_facebook_mail';
	
	/**
	 * Meta key of usermeta for Facebook access token
	 * @var type 
	 */
	public $umeta_token = '_wpg_facebook_access_token';
	
	/**
	 * @var array
	 */
	private $_signed_request = array();
	
	/**
	 * Setup Everything
	 * @param array $option
	 */
	protected function set_option($option) {
		$option = shortcode_atts(array(
			'fb_app_id' => '',
			'fb_app_secret' => '',
			'fb_fan_gate' => 0
		), $option);
		$this->app_id = (string)$option['fb_app_id'];
		$this->app_secret = (string)$option['fb_app_secret'];
		$this->fan_gate = (int) $option['fb_fan_gate'];
		$this->cert_path = dirname(__FILE__).DIRECTORY_SEPARATOR."fb_ca_chain_bundle.crt";
		//Add Hook on Fan Gate
		if($this->fan_gate){
			add_action('template_redirect', array($this, 'fan_gate_helper'));
		}
		//Start Session
		if(!isset($_SESSION)){
			session_start();
		}
	}
	
	
	/**
	 * Executed on init hook.
	 * @global wpdb $wpdb
	 * @global int $user_ID
	 * @global WP_Gianism $giasnism
	 */
	public function init_action(){
		global $user_ID, $wpdb, $gianism;
		switch($this->get_action()){
			case "facebook_connect":
				$this->message = $gianism->_("Oops, Failed to Authenticate.");
				try{
					$uid = $this->facebook()->getUser();
				}catch(FacebookApiException $e){
					$this->message .= '\n'.$e->getMessage();
				}
				if($uid && is_user_logged_in()){
					try{
						if(!isset($_SESSION['uid'])){
							$_SESSION['uid'] = $uid;
						}
						try{
							$profile = $this->facebook()->api('/me');
						}catch(FacebookApiException $e){
							$profile = $this->facebook()->api('/'.$uid);
						}
						if(isset($profile['email'])){
							//Check if other user has these as meta_value
							$email = $profile['email'];
							$sql = <<<EOS
								SELECT user_id FROM {$wpdb->usermeta}
								WHERE ((meta_key = %s) AND (meta_value = %s) AND (user_id != %d))
								   OR ((meta_key = %s) AND (meta_value = %s) AND (user_id != %d))
EOS;
							$others = $wpdb->get_row($wpdb->prepare($sql, $this->umeta_id, $uid, $user_ID, $this->umeta_mail, $email, $user_ID));
							$email_exitance = email_exists($email);
							if(!$others && (!$email_exitance || $user_ID == $email_exitance)){
								update_user_meta($user_ID, $this->umeta_id, $uid);
								update_user_meta($user_ID, $this->umeta_mail, $email);
								do_action('wpg_connect', $user_ID, $profile, 'facebook', false);
								$this->message = sprintf($gianism->_('Welcome!, %s'), $profile['name']);
							}else{
								$this->message = sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), "Facebook");
							}
						}
					}catch(FacebookApiException $e){
						$this->message = $gianism->_("Oops, Failed to Authenticate.").'\n'.$e->getMessage();
					}
				}else{
					$this->message .= '\n'.$gianism->_('Cannot get Facebook ID.');
				}
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
				$facebook_id = $this->facebook()->getUser();
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
	 * Add User form
	 * @global WP_Gianism $gianism
	 * @param WP_User $current_user 
	 */
	public function user_profile($current_user){
		if(!defined("IS_PROFILE_PAGE")){
			return;
		}
		global $gianism;
		//Show Login button
		if(!$this->get_user_id()){
			$link_text = $gianism->_('Connect');
			$desc = sprintf($gianism->_('Connecting with Facebook account, you can log in %s via Facebook account.'), get_bloginfo('name'));
			$onclick = '';
			$url = $this->facebook()->getLoginUrl(array(
				'scope' => 'email',
				'redirect_uri' => admin_url('profile.php?wpg=facebook_connect')
			));
			$p_class = 'description';
		}else{
			$link_text = $gianism->_('Disconnect');
			$desc = $gianism->_('Your account is already connected with Facebook account.');
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';
			$url = wp_nonce_url(admin_url('profile.php?wpg=facebook_disconnect'), 'facebook_disconnect');
			$p_class = 'description desc-connected desc-connected-google';
		}
		?>
		<tr>
			<th><?php $gianism->e('Facebook'); ?></th>
			<td>
				<div id="fb-connector">
					<a class="fb_button fb_button_medium" id="fb-login" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
						<span class="fb_button_text"><?php echo $link_text;?></span>
					</a>
					<p class="<?php echo $p_class; ?>"><?php echo $desc;?></p>
				</div>
				<!-- #fb-connector -->
			</td>
		</tr>
		<?php
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
	 * @return boolean
	 */
	public function is_user_like_me_on_fangate(){
		if($this->fan_gate > 0){
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
	 * @global wpdb $wpdb
	 * @return int
	 */
	public function is_registered_user_on_fangate(){
		global $wpdb;
		if($this->fan_gate > 0){
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
	 * @return boolean
	 */
	public function is_guest_on_fangate(){
		if($this->fan_gate > 0){
			return (false == (boolean)$this->signed_request('user_id'));
		}else{
			return false;
		}
	}
	
	/**
	 * Returns if current page is fan gate.
	 * @return string
	 */
	public function is_fangate(){
		if($this->fan_gate > 0){
			$page = $this->signed_request('page');
			if($page){
				return is_page($this->fan_gate);
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * Get signed request
	 * @return string
	 */
	private function signed_request($key){
		if(empty($this->_signed_request)){
			$this->_signed_request = $this->facebook()->getSignedRequest();
		}
		return isset($this->_signed_request[$key]) ? $this->_signed_request[$key]: null;
	}
	
	/**
	 * Initialize Facebook Fangate Scripts
	 */
	public function fan_gate_helper(){
		if(is_page($this->fan_gate)){
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
				appId: '<?php echo $this->app_id ?>',
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
	 * Returns User's Facebook ID
	 * @global int $user_ID
	 * @param int $user_id
	 * @return int
	 */
	public function get_user_id($user_id = null){
		$user_id = $this->wp_user_id($user_id);
		if($user_id){
			$facebook_id = get_user_meta($user_id, '_wpg_facebook_id', true);
			return $facebook_id ? $facebook_id : 0;
		}else{
			return 0;
		}
	}
	
	/**
	 * Returns Facebook mail
	 * @param int $user_id
	 * @return string
	 */
	public function get_user_mail($user_id = null){
		$user_id = $this->wp_user_id($user_id);
		if($user_id){
			$facebook_mail = get_user_meta($user_id, '_wpg_facebook_mail', true);
			return $facebook_mail ? $facebook_mail : null;
		}else{
			return null;
		}
	}
	
	/**
	 * Save Facebook Mail
	 * @param string $mail
	 * @param int $user_id 
	 * @return void
	 */
	public function set_user_mail($mail, $user_id = null){
		$user_id = $this->wp_user_id($user_id);
		update_user_meta($user_id, '_wpg_facebook_mail');
	}
	
	/**
	 * Save Facebook ID
	 * @param string $fb_user_id
	 * @param int $user_id 
	 * @return void
	 */
	public function set_user_id($fb_user_id, $user_id = null){
		$user_id = $this->wp_user_id($user_id);
		update_user_meta($user_id, '_wpg_facebook_id', $fb_user_id);
	}
	
	/**
	 * Returns WordPress's user ID
	 * @global int $user_ID
	 * @param int $user_id
	 * @return int
	 */
	private function wp_user_id($user_id = null){
		if(is_null($user_id)){
			global $user_ID;
			$user_id = $user_ID;
		}
		return (int)$user_id;
	}
	
	/**
	 * Returns facebook Controller
	 * @return Facebook
	 */
	private function facebook(){
		if(is_null($this->_api)){
			if(!class_exists('Facebook')){
				require_once dirname(__FILE__).DIRECTORY_SEPARATOR."facebook.php";
			}
			$this->_api = new Facebook(array(
				'appId'  => $this->app_id,
				'secret' => $this->app_secret,
				'cookie' => true
			));
		}
		return $this->_api;
	}
}