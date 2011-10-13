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
	private $api = null;
	
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
	 * Constructor
	 * @param string $app_id
	 * @param string $app_secret 
	 * @return void
	 */
	public function __construct($app_id, $app_secret) {
		$this->app_id = $app_id;
		$this->app_secret = $app_secret;
		$this->cert_path = dirname(__FILE__).DIRECTORY_SEPARATOR."fb_ca_chain_bundle.crt";
		//BASECLASS
		require_once dirname(__FILE__).DIRECTORY_SEPARATOR."facebook.php";
		$this->api = new Facebook(array(
			'appId'  => $app_id,
			'secret' => $app_secret,
		));
		//Add Hook on Profile page
		add_action('gianism_user_profile', array($this, 'show_facebook_interface'));
		//Add Hook on Login Page
		add_action('gianism_login_form', array($this, 'show_login_button'));
		//Add Hook on Regsiter Page
		add_action('gianism_regsiter_form', array($this, 'show_regsiter_button'));
		//Add Hook on Footer
		add_action('admin_print_footer_scripts', array($this, 'print_script'));
		add_action('wp_footer', array($this, 'print_script'));
		//Add Ajax Hook
		//Connect
		add_action('wp_ajax_connect_with_facebook', array($this, 'connect_with'));
		add_action('wp_ajax_nopriv_connect_with_facebook', array($this, 'connect_with'));
		//Disconnect
		add_action('wp_ajax_diconnect_from_facebook', array($this, 'disconnect_from'));
		add_action('wp_ajax_nopriv_diconnect_from_facebook', array($this, 'disconnect_from'));
		//Login
		add_action('wp_ajax_nopriv_login_with_facebook', array($this, 'login_with'));
		//Register
		add_action('wp_ajax_nopriv_register_with_facebook', array($this, 'register_with'));
	}
	
	/**
	 * Add User form
	 * @param WP_User $current_user 
	 */
	public function show_facebook_interface($current_user){
		global $gianism;
		//Show Login Button
		$this->js = true;
		?>
		<tr>
			<th><?php $gianism->e('Facebook'); ?></th>
			<td>
				<div id="fb-connector"<?php if(is_user_connected_with('facebook')) echo ' style="display:none;"';?>>
					<p>
						<fb:login-button show-faces="false"><?php $gianism->e('Connect with Facebook');?></fb:login-button>
					</p>
					<p class="description">
						ログインする
					</p>
				</div>
				<!-- #fb-connector -->
				
				<p id="fb-disconnector"<?php if(!is_user_connected_with('facebook')) echo ' style="display:none;"';?>>
					<?php $gianism->e('Your account is connected with Facebook.');?>
					<a class="fb_button fb_button_medium" href="#">
						<span class="fb_button_text"><?php $gianism->e('Disconnect from Facebook?');?></span>
					</a>
				</p>
				<!-- #fb-disconnector -->
				
				<img style="display:none;" id="fb-indicator" alt="Loading" src="<?php echo plugins_url('', __FILE__);?>/ajax-loader.gif" width="16" height="11" />
			</td>
		</tr>
		<?php
		//Echo Javascript
		$endpoint = admin_url('admin-ajax.php');
		$nonce_connect = wp_create_nonce('connect_with_facebook');
		$nonce_disconnect = wp_create_nonce('disconnect_from_facebook');
		$this->scripts .= <<<EOS
			FB.Event.subscribe('auth.login', function(response){
				if(response.authResponse){
					jQuery('#fb-indicator').fadeIn();
					var userID = response.authResponse.userID;
					jQuery.post(
						'{$endpoint}',
						{
							action: 'connect_with_facebook',
							nonce: '{$nonce_connect}',
							userID: userID
						},
						function(result){
							if(result.status == 'success'){
								jQuery('#fb-indicator, #fb-connector').css('display', 'none');
								jQuery('#fb-disconnector').fadeIn();
							}
						}
					);
				}
			});
			jQuery('#fb-disconnector a.fb_button').click(function(e){
				e.preventDefault();
				jQuery('#fb-indicator').fadeIn();
				jQuery.post(
					'{$endpoint}',
					{
						action: 'diconnect_from_facebook',
						nonce: '{$nonce_disconnect}',
					},
					function(result){
						if(result.status == 'success'){
							jQuery('#fb-indicator, #fb-disconnector').css('display', 'none');
							jQuery('#fb-connector').fadeIn();
							FB.logout();
						}
					}
				);
			});
EOS;
	}
	
	/**
	 * Show Login Button on Facebook.
	 * @global WP_Gianism $gianism 
	 */
	function show_login_button(){
		global $gianism;
		$facebook_id = $this->api->getUser();
		//Show Login Button
		$this->js = true;
		if($facebook_id): ?>
			<a class="fb_button fb_button_medium" id="fb-login" href="#">
				<span class="fb_button_text"><?php $gianism->e('Log in with Facebook');?></span>
			</a>
		<?php else: ?>
			<fb:login-button><?php $gianism->e('Log in with Facebook');?></fb:login-button>
		<?php endif; 
			$endpoint = admin_url('admin-ajax.php');
			$nonce = wp_create_nonce('login_with_facebook');
			$redirect_to = $gianism->sanitize_redirect_to(admin_url('profile.php'), $gianism->request('redirect_to'));
			$this->scripts .= <<<EOS
				var login = function(userID){
					jQuery('#fb-indicator').fadeIn();
					jQuery.post(
						'{$endpoint}',
						{
							action: 'login_with_facebook',
							nonce: '{$nonce}',
							userID: userID
						},
						function(result){
							if(result.status == 'success'){
								window.location.href = '{$redirect_to}';
							}
						}
					);
				};
				jQuery('#fb-login').click(function(e){
					e.preventDefault();
					login('{$facebook_id}');
				});
				FB.Event.subscribe('auth.login', function(response){
					if(response.authResponse){
						var userID = response.authResponse.userID;
						login(userID);
					}
				});
EOS;
	}
	
	/**
	 * Display register button on register form
	 * @global WP_Gianism $gianism 
	 * @return void
	 */
	public function show_regsiter_button(){
		global $gianism;
		$facebook_id = $this->api->getUser();
		//Show Login Button
		$this->js = true;
		if($facebook_id):
			if(get_user_by_service('facebook', $facebook_id)): ?>
				<?php printf($gianism->_('You seemed to have your account already. Please login from <a href="%s">here</a>.'), wp_login_url($gianism->sanitize_redirect_to('', $gianism->request('redirect_to')))); ?>
			<?php else:?>
				<a class="fb_button fb_button_medium" id="fb-login" href="#">
					<span class="fb_button_text"><?php $gianism->e('Create account with Facebook');?></span>
				</a>
			<?php endif;?>
		<?php else: ?>
			<fb:login-button show-faces="false" scope="email"><?php $gianism->e('Create account with Facebook');?></fb:login-button>
		<?php endif; 
		$endpoint = admin_url('admin-ajax.php');
		$redirect_to = $gianism->sanitize_redirect_to(admin_url('profile.php'), $gianism->request('redirect_to'));
		$nonce = wp_create_nonce('register_with_facebook');
		$this->scripts .= <<<EOS
			FB.Event.subscribe('auth.login', function(response){
				if(response.authResponse){
					FB.api('/me', function(res){
						jQuery.post(
							'{$endpoint}',
							{
								action: 'register_with_facebook',
								nonce: '{$nonce}',
								userID: res.id,
								email: res.email,
								redirect: '{$redirect_to}'
							},
							function(result){
								if(result.status == 'success'){
									window.location.href = '{$redirect_to}';
								}else{

								}
							}
						);
					});
				}else{
					console.log('Failed to login');
				}
			});
EOS;
	}
	
	/**
	 * Print JS on footer of both admin panel and public page.
	 * 
	 * Paramater 'js' shold be true and if 'js' is 'no-fb-root', 
	 * div won't be displayed.
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
	function connect_with(){
		global $gianism, $user_ID;
		if(is_user_logged_in() && wp_verify_nonce($_REQUEST['nonce'], 'connect_with_facebook') && isset($_REQUEST['userID'])){
			update_user_meta($user_ID, 'wpg_facebook_id', $_REQUEST['userID']);
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
	function disconnect_from(){
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
	function register_with(){
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
}