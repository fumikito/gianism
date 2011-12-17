<?php
/**
 * Description of facebook_controller
 *
 * @author Hametuha inc.
 */
class Facebook_Controller {
	
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
	public $api = null;
	
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
	 * load javascript if true.
	 * @var boolean
	 */
	private $js = false;
	
	/**
	 * Javascript to be ouput
	 * @var string
	 */
	private $scripts = '';
	
	/**
	 * Meta key of postmeta for facebook id
	 * @var string
	 */
	public $umeta_id = '_wpg_facebook_id';
	
	/**
	 * Meta key of postmeta for facebook mail
	 * @var string
	 */
	public $umeta_mail = '_wpg_facebook_mail';
	
	/**
	 * Constructor
	 * @param string $app_id
	 * @param string $app_secret 
	 * @param int $fan_gate
	 * @return void
	 */
	public function __construct($app_id, $app_secret, $fan_gate) {
		$this->app_id = $app_id;
		$this->app_secret = $app_secret;
		$this->fan_gate = (int) $fan_gate;
		$this->cert_path = dirname(__FILE__).DIRECTORY_SEPARATOR."fb_ca_chain_bundle.crt";
		//BASECLASS
		require_once dirname(__FILE__).DIRECTORY_SEPARATOR."facebook.php";
		$this->api = new Facebook(array(
			'appId'  => $app_id,
			'secret' => $app_secret,
		));
		$this->init_action();
		//Add Hook on Profile page
		add_action('gianism_user_profile', array($this, 'show_facebook_interface'));
		//Add Hook on Login Page
		add_action('gianism_login_form', array($this, 'show_login_button'));
		//Add Hook on Regsiter Page
		add_action('gianism_regsiter_form', array($this, 'show_login_button'));
		//Add Hook on Fan Gate
		if($fan_gate){
			add_action('template_redirect', array($this, 'fan_gate_helper'));
		}
		//Add Hook on Footer
		add_action('admin_print_footer_scripts', array($this, 'print_script'));
		add_action('wp_footer', array($this, 'print_script'));
		//Add Ajax Hook
		//Connect
		//add_action('wp_ajax_connect_with_facebook', array($this, 'connect_with'));
		//add_action('wp_ajax_nopriv_connect_with_facebook', array($this, 'connect_with'));
		//Disconnect
		//add_action('wp_ajax_diconnect_from_facebook', array($this, 'disconnect_from'));
		//add_action('wp_ajax_nopriv_diconnect_from_facebook', array($this, 'disconnect_from'));
		//Login
		//add_action('wp_ajax_nopriv_login_with_facebook', array($this, 'login_with'));
		//Register
		//add_action('wp_ajax_nopriv_register_with_facebook', array($this, 'register_with'));
	}
	
	
	/**
	 * Executed on init hook.
	 * @global wpdb $wpdb
	 */
	public function init_action(){
		if(isset($_GET['wpg'])){
			switch($_GET['wpg']){
				case "facebook_connect":
					$uid = $this->api->getUser();
					if($uid && is_user_logged_in()){
						global $user_ID;
						try{
							$profile = $this->api->api('/me', 'GET');
							update_user_meta($user_ID, $this->umeta_id, $uid);
							if(isset($profile['email'])){
								update_user_meta($user_ID, $this->umeta_mail, $profile['email']);
							}
						}catch(FacebookApiException $e){
							
						}
					}
					break;
				case "facebook_disconnect":
					if(is_user_logged_in() && isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'facebook_disconnect')){
						global $user_ID;
						delete_user_meta($user_ID, $this->umeta_id);
						delete_user_meta($user_ID, $this->umeta_mail);
					}
					break;
				case "facebook_login":
					if(!is_user_logged_in()){
						$redirect = false;
						$facebook_id = $this->api->getUser();
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
									$profile = $this->api->api('/me', 'GET');
									if(isset($profile['email'])){
										$email = (string)$profile['email'];
										//Try to find registered user
										$user_id = email_exists($email);
										if(!$user_id){
											//Not found, thus seek usermeta
											$sql = <<<EOS
												SELECT user_id FROM {$wpdb->usermeta}
												WHERE meta_key = %s AND meta_value = %s
EOS;
											$user_id = $wpdb->get_var($wpdb->prepare($sql, $this->umeta_mail, $email));
											if(!$user_id){
												//Not found, Create New User
												require_once(ABSPATH . WPINC . '/registration.php');
												//Check if username exists
												$user_name = (!username_exists($profile['username'])) ? $profile['username'] :  $email;
												$user_id = wp_create_user($user_name, wp_generate_password(), $email);
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
												}else{
													$redirect = false;
												}

											}
										}
									}else{
										$redirect = true;
									}
								}catch(FacebookApiException $e){
									//Can't get email, so, error.
									$redirect = true;
								}
							}
						}else{
							$redirect = true;
						}
						
						if($user_id && !is_wp_error($user_id)){
							wp_set_auth_cookie($user_id, true);
							if(isset($_GET['redirect_to'])){
								header('Location: '.$_GET['redirect_to']);
								die();
							}
						}
					}
					break;
			}
		}
	}
	
	
	/**
	 * Add User form
	 * @global WP_Gianism $gianism
	 * @param WP_User $current_user 
	 */
	public function show_facebook_interface($current_user){
		if(!defined("IS_PROFILE_PAGE")){
			return;
		}
		global $gianism;
		//Show Login Button
		$this->js = true;
		if(!$this->get_user_id()){
			$link_text = $gianism->_('Connect');
			$desc = sprintf($gianism->_('Connecting with Facebook account, you can log in %s via Facebook account.'), get_bloginfo('name'));
			$onclick = '';
			$url = $this->api->getLoginUrl(array(
				'scope' => 'email',
				'redirect_uri' => admin_url('profile.php?wpg=facebook_connect')
			));
		}else{
			$link_text = $gianism->_('Disconnect');
			$desc = '<img src="'.$gianism->url.'/assets/icon-checked.png" alt="Connected" width="16" height="16" />'
					.$gianism->_('Your account is already connected with Facebook account.');
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';
			$url = wp_nonce_url(admin_url('profile.php?wpg=facebook_disconnect'), 'facebook_disconnect');
		}
		?>
		<tr>
			<th><?php $gianism->e('Facebook'); ?></th>
			<td>
				<div id="fb-connector">
					<a class="fb_button fb_button_medium" id="fb-login" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
						<span class="fb_button_text"><?php echo $link_text;?></span>
					</a>
					<p class="description"><?php echo $desc;?></p>
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
	function show_login_button(){
		global $gianism;
		$redirect = isset($_REQUEST['redirect_to']) ? $gianism->request('redirect_to') : admin_url('profile.php');
		$login_url = wp_login_url($redirect)."&wpg=facebook_login";	
		$url = $this->api->getLoginUrl(array(
			'scope' => 'email',
			'redirect_uri' => $login_url,
		));
		//Show Login Button
		$this->js = true;
		?>
		<a class="fb_button fb_button_medium" id="fb-login" href="<?php echo $url; ?>">
			<span class="fb_button_text"><?php $gianism->e('Log in with Facebook');?></span>
		</a>
		<?php
	}
	
	
	/**
	 * Initialize Facebook Fangate Scripts
	 * @global WP_Gianism $gianism 
	 */
	public function fan_gate_helper(){
		global $gianism;
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
	}
	
	/**
	 * Connect user account with Facebook account via Ajax.
	 * @global WP_Gianism $gianism
	 * @global int $user_ID 
	 * @return void
	 */
	public function connect_with(){
		global $gianism, $user_ID;
		if(is_user_logged_in() && wp_verify_nonce($gianism->request('nonce'), 'connect_with_facebook') && isset($_REQUEST['userID'])){
			update_user_meta($user_ID, '_wpg_facebook_id', $_REQUEST['userID']);
			$status = 'success';
			$message = $gianism->_('Your account was successfully connected with Facebook');
		}else{
			$status = 'error';
			$message = sprintf($gianism->_('You need to log in %s'), get_bloginfo('name'));
		}
		header('Content-Type: application/json');
		echo json_encode(array(
			'status' => $status,
			'message' => $message
		));
		die();
	}
	
	/**
	 * Disconnect Facebook account from User account
	 * @global int $user_ID
	 * @return void
	 */
	public function disconnect_from(){
		global $user_ID;
		if(is_user_logged_in() && wp_verify_nonce($_REQUEST['nonce'], 'disconnect_from_facebook')){
			delete_user_meta($user_ID, 'wpg_facebook_id');
			$status = 'success';
		}else{
			$status = 'error';
		}
		header('Content-Type: application/json');
		echo json_encode(array(
			'status' => $status
		));
		die();
	}
	
	/**
	 * Login with facebook id.
	 * @return void
	 */
	function login_with(){
		if(wp_verify_nonce($_REQUEST['nonce'], 'login_with_facebook') && isset($_REQUEST['userID'])){
			$user = get_user_by_service('facebook', $_POST['userID']);
			if($user){
				wp_set_auth_cookie($user->ID, true, is_ssl());
				$status = 'success';
			}else{
				$status = 'error';
			}
			header('Content-Type: application/json');
			echo json_encode(array(
				'status' => $status
			));
			die();
		}
	}
	
	/**
	 * Register user with Facebook account
	 * @global WP_Gianism $gianism 
	 * @return void
	 */
	public function register_with(){
		global $gianism;
		if(wp_verify_nonce($gianism->request('nonce'), 'register_with_facebook')){
			$status = 'success';
			$message = '';
			//check if email exists
			if(email_exists($gianism->post('email'))){
				$status = 'error';
				$message = sprintf($gianism->_('This email address is registered. You can log in <a href="%s">here</a>.'), wp_login_url($gianism->post('redirect')));
			}else{
				//Try create user
				require_once(ABSPATH . WPINC . '/registration.php');
				$user_id = wp_create_user($gianism->post('email'), wp_generate_password(), $gianism->post('email'));
				if($user_id){
					update_user_meta($user_id, 'wpg_facebook_id', $gianism->post('userID'));
					wp_set_auth_cookie($user_id, true, is_ssl());
				}else{
					$status = 'error';
					$message = $gianism->_('Failed to create account. Try to register with mail address and password.');
				}
			}
			header('Content-Type: application/json');
			echo json_encode(array(
				'status' => $status,
				'message' => $message
			));
			die();
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
}