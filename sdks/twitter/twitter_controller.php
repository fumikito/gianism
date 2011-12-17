<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."gianism_controller.php";
/**
 * Description of twitter_controller
 *
 * @package gianism
 */
class Twitter_Controller extends Gianism_Controller{
	
	/**
	 * @var string
	 */
	private $consumer_key = '';
	
	/**
	 * @var string
	 */
	private $consumer_secret = '';
	
	/**
	 * @var string
	 */
	private $my_access_token = '';
	
	/**
	 * @var string
	 */
	private $my_access_token_secret = '';
	
	/**
	 * @var string
	 */
	public $umeta_id = '_wpg_twitter_id';
	
	/**
	 * @var string
	 */
	public $umeta_screen_name = '_wpg_twitter_screen_name';
	
	/**
	 * @var string
	 */
	private $pseudo_domain = 'pseudo.twitter.com';
	
	/**
	 * Constructor
	 * @param array $option 
	 */
	private function set_option($option) {
		$option = shortcode_atts(array(
			"tw_consumer_key" => "",
			"tw_consumer_secret" => "",
			"tw_access_token" => "",
			"tw_access_token_secret" => ""
		), $option);
		$this->consumer_key = (string)$option['tw_consumer_key'];
		$this->consumer_secret = (string)$option['tw_consumer_secret'];
		$this->my_access_token = (string)$option['tw_access_token'];
		$this->my_access_token_secret = (string)$option['tw_access_token_secret'];
		session_start();
	}
	
	/**
	 * Executed on init hook
	 * @global wpdb $wpdb
	 * @global int $user_ID
	 */
	public function init_action(){
		global $user_ID, $wpdb;
		switch($this->get_action()){
			case 'twitter_connect':
				if(isset($_GET['oauth_verifier']) && is_user_logged_in()){
					$token = $this->get_token();
					$token_secret = $this->get_token(true);
					if(!empty($token) && !empty($token_secret)){
						$oauth = $this->get_oauth($token, $token_secret);
						$access_token = $oauth->getAccessToken($_GET['oauth_verifier']);
						if(isset($access_token['user_id'], $access_token['screen_name'])){
							//Check if other user has registered.
							$sql = <<<EOS
								SELECT umeta_id FROM {$wpdb->usermeta}
								WHERE ((meta_key = %s) AND (meta_value = %s))
								   OR ((meta_key = %s) AND (meta_value = %s))
EOS;
							if(!$wpdb->get_row($sql, $this->umeta_id, $access_token['user_id'], $this->umeta_screen_name, $access_token['screen_name'])){
								update_user_meta($user_ID, $this->umeta_id, $access_token['user_id']);
								update_user_meta($user_ID, $this->umeta_screen_name, $access_token['screen_name']);
							}
						}
					}
				}
				break;
		}
	}
	
	/**
	 * Show connect button on login form
	 * @global WP_Gianism $gianism
	 * @return void
	 */
	public function show_profile(){
		if(!defined("IS_PROFILE_PAGE")){
			return;
		}
		global $gianism, $user_ID;
		if($this->is_connected()){
			$url = wp_nonce_url(admin_url('profile.php?wpg=twitter_disconnect'), 'twitter_disconnect');
			$link_text = $gianism->_('Disconnect');
			$account = get_user_meta($user_ID, $this->umeta_screen_name, true);
			$desc = sprintf($gianism->_('Your account is already connected with Twitter <a target="_blank" href="%1$s">%2$s</s> .'), 'https://twitter.com/#!/'.$account, "@".$account);
			//If user has pseudo mail, add caution.
			global $user_email;
			if($this->is_pseudo_mail($user_email)){
				$desc .= '<br /><strong>Note:</strong> '.sprintf($gianism->_('Your mail address is pseudo &quot;%1$s&quot;. If you disconnect twitter account, you may not be able to log in %2$s. Please change it to available e-mail address.'), $user_email, get_bloginfo('name'));
			}
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';
		}else{
			//Create Link
			$callback_url = admin_url('profile.php?wpg=twitter_connect');
			$oauth = $this->get_oauth();
			$token = $oauth->getRequestToken($callback_url);
			$this->save_token($token);
			$url = $oauth->getAuthorizeURL($token);
			$link_text = $gianism->_('Connect');
			$desc = sprintf($gianism->_("Connecting Twitter account, you can log in %s via Twitter account."), get_bloginfo('name'));
			$onclick = '';
		}
		?>
		<tr>
			<th><?php $gianism->e('Twitter'); ?></th>
			<td class="xl">
				<div class="btn-o">
					<a class="btn" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
						<i></i>
						<span class="label"><?php echo $link_text;?></span>
					</a>
				</div>
				<p class="description"><?php echo $desc;?></p>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Returns whether user has twitter account
	 * @global int $user_ID
	 * @param int $user_ID
	 * @return boolean
	 */
	private function is_connected($user_ID = null){
		if(is_null($user_ID)){
			global $user_ID;
		}
		return (boolean)get_user_meta($user_ID, $this->umeta_id, true);
	}
	
	/**
	 * Get API wrapper
	 * @param string $oauth_token
	 * @param string $oauth_token_secret 
	 * @return TwitterOAuth
	 */
	private function get_oauth($oauth_token = NULL, $oauth_token_secret = NULL){
		require_once dirname(__FILE__).DIRECTORY_SEPARATOR."twitteroauth.php";
		return new TwitterOAuth($this->consumer_key, $this->consumer_secret, $oauth_token, $oauth_token_secret);
	}
	
	/**
	 * Save token. if failed, return false.
	 * @param array $token
	 * @return booelan 
	 */
	private function save_token($token){
		if(isset($token['oauth_token'], $token['oauth_token_secret'])){
			$_SESSION['_wpg_twitter_token'] = $token;
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Returns session stored token.
	 * @param boolean $secret
	 * @return string 
	 */
	private function get_token($secret = false){
		$key = $secret ? 'oauth_token_secret' : 'oauth_token';
		if(isset($_SESSION['_wpg_twitter_token'][$key])){
			return $_SESSION['_wpg_twitter_token'][$key];
		}else{
			return false;
		}
	}
	
	/**
	 * Add alert message throw Javascript
	 * @param string $text 
	 */
	public function add_alert($text){
		$this->js = true;
		if(!empty($this->scripts)){
			$this->scripts .= "\n";
		}
		$this->scripts .= $text;
	}
	
	/**
	 * Print Javascript on footer
	 */
	public function print_script(){
		if($this->js && !empty($this->scripts)){
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					alert("<?php echo esc_attr($this->scripts);  ?>");
				});
			</script>
			<?php
		}
	}
		
}
