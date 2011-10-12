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
		//Add Hook on Footer
		add_action('admin_print_footer_scripts', array($this, 'print_script'));
		add_action('wp_footer', array($this, 'print_script'));
		//Add Ajax Hook
		add_action('wp_ajax_connect_with_facebook', array($this, 'connect_with'));
		add_action('wp_ajax_nopriv_connect_with_facebook', array($this, 'connect_with'));
		add_action('wp_ajax_diconnect_from_facebook', array($this, 'disconnect_from'));
		add_action('wp_ajax_nopriv_diconnect_from_facebook', array($this, 'disconnect_from'));
		add_action('wp_ajax_nopriv_login_with_facebook', array($this, 'login_with'));
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
					<?php $gianism->e('Your account is connected with Facebook.');?><br />
					<a class="button" href="#"><?php $gianism->e('Disconnect from Facebook?');?></a>
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
			jQuery('#fb-disconnector a.button').click(function(e){
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
			<a class="button" id="fb-login" href="#"><?php $gianism->e('Log in with Facebook');?></a>
		<?php else: ?>
			<fb:login-button><?php $gianism->e('Log in with Facebook');?></fb:login-button>
		<?php endif; 
			$endpoint = admin_url('admin-ajax.php');
			$nonce = wp_create_nonce('login_with_facebook');
			$redirect_to = admin_url('profile.php');
			if(isset($_REQUEST['redirect_to'])){
				$url = (string)$_REQUEST['redirect_to'];
				//Check if it has schema
				$url_splited = explode('://');
				if(count($url_splited) > 1){
					$server_name = str_replace(".", '\.', $_SERVER['SERVER_NAME']);
					//has schema
					if(preg_match("/^https?(:\/\/{$server_name}[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/", $url)){
						$redirect_to = $url;
					}
				}else{
					//no schema
					if(!preg_match("/^\/\//", $url) && preg_match("/^([-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/", $url)){
						$redirect_to = $url;
					}
				}
			}
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
					jQuery('#fb-indicator').fadeIn();
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
}