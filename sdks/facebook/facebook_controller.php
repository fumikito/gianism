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
		//Add Hook on Footer
		add_action('admin_print_footer_scripts', array($this, 'print_script'));
		add_action('wp_footer', array($this, 'print_script'));
		//Add Ajax Hook
		add_action('wp_ajax_connect_with_facebook', array($this, 'connect_with'));
		add_action('wp_ajax_nopriv_connect_with_facebook', array($this, 'connect_with'));
		add_action('wp_ajax_diconnect_from_facebook', array($this, 'disconnect_from'));
		add_action('wp_ajax_nopriv_diconnect_from_facebook', array($this, 'disconnect_from'));
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
								jQuery('#fb-indicator, #fb-connector').fadeOut();
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
							jQuery('#fb-indicator, #fb-disconnector').fadeOut();
							jQuery('#fb-connector').fadeIn();
							FB.logout();
						}
					}
				);
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
}