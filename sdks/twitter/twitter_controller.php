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
	private $screen_name = '';
	
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
	protected $pseudo_domain = 'pseudo.twitter.com';
	
	/**
	 * Constructor
	 * @param array $option 
	 */
	protected function set_option($option) {
		$option = shortcode_atts(array(
			"tw_screen_name" => '',
			"tw_consumer_key" => "",
			"tw_consumer_secret" => "",
			"tw_access_token" => "",
			"tw_access_token_secret" => ""
		), $option);
		$this->screen_name = (string)$option['tw_screen_name'];
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
	 * @global WP_Gianism $gianism
	 */
	public function init_action(){
		global $user_ID, $wpdb, $gianism;
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
								WHERE ((meta_key = %s) AND (meta_value = %s) AND (user_id != %d))
								   OR ((meta_key = %s) AND (meta_value = %s) AND (user_id != %d))
EOS;
							if(!$wpdb->get_row($sql, $this->umeta_id, $access_token['user_id'], $user_ID, $this->umeta_screen_name, $access_token['screen_name'], $user_ID)){
								update_user_meta($user_ID, $this->umeta_id, $access_token['user_id']);
								update_user_meta($user_ID, $this->umeta_screen_name, $access_token['screen_name']);
								$this->follow_me($oauth);
								$this->add_alert(sprintf($gianism->_('Welcome, @%s!'), $access_token['screen_name']));
							}else{
								$this->add_alert(sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), "Twitter"));
							}
						}else{
							$this->add_alert($gianism->_('Oops, Failed to Authenticate.'));
						}
					}else{
						$this->add_alert($gianism->_('Oops, Failed to Authenticate.'));
					}
				}else{
					$this->add_alert($gianism->_('Oops, Failed to Authenticate.'));
				}
				break;
			case "twitter_disconnect":
				if(wp_verify_nonce($gianism->request('_wpnonce'), 'twitter_disconnect') && is_user_logged_in()){
					delete_user_meta($user_ID, $this->umeta_id);
					delete_user_meta($user_ID, $this->umeta_screen_name);
					$this->add_alert($gianism->_('Disconnect now :('));
				}
				break;
			case "twitter_login":
				$user_id = false;
				if(isset($_GET['oauth_verifier'])){
					$token = $this->get_token();
					$token_secret = $this->get_token(true);
					if(!empty($token) && !empty($token_secret)){
						$oauth = $this->get_oauth($token, $token_secret);
						$access_token = $oauth->getAccessToken($_GET['oauth_verifier']);
						if(isset($access_token['user_id'], $access_token['screen_name'])){
							$twitter_id = $access_token['user_id'];
							$screen_name = $access_token['screen_name'];
							//Get User ID.
							$sql = <<<EOS
								SELECT user_id FROM {$wpdb->usermeta}
								WHERE meta_key = %s AND meta_value = %s
EOS;
							$user_id = $wpdb->get_var($wpdb->prepare($sql, $this->umeta_id, $twitter_id));
							if(!$user_id){
								//Not found, Create New User
								require_once(ABSPATH . WPINC . '/registration.php');
								//Check if username exists
								$email = $screen_name."@".$this->pseudo_domain;
								$user_name = (!username_exists('@'.$screen_name)) ? '@'.$screen_name :  $email;
								$user_id = wp_create_user($user_name, wp_generate_password(), $email);
								
								if(!is_wp_error($user_id)){
									update_user_meta($user_id, $this->umeta_id, $twitter_id);
									update_user_meta($user_id, $this->umeta_screen_name, $screen_name);
									$wpdb->update(
										$wpdb->users,
										array(
											'display_name' => "@{$screen_name}",
											'user_url' => 'https://twitter.com/#!/'.$screen_name
										),
										array('ID' => $user_id),
										array('%s', '%s'),
										array('%d')
									);
									$this->follow_me($oauth);
								}
							}
						}
					}
				}
				if($user_id && !is_wp_error($user_id)){
					wp_set_auth_cookie($user_id, true);
					$redirect = $this->get_redirect_to(admin_url('profile.php'));
					header('Location: '.$redirect);
					die();
				}else{
					$this->add_alert($gianism->_('Oops, Failed to Authenticate.'));
				}
				break;
		}
	}
	
	/**
	 * Show connect button on login form
	 * 
	 * @global WP_Gianism $gianism
	 * @return void
	 */
	public function user_profile(){
		if(!defined("IS_PROFILE_PAGE")){
			return;
		}
		global $gianism, $user_ID;
		if($this->is_connected()){
			$url = wp_nonce_url(admin_url('profile.php?wpg=twitter_disconnect'), 'twitter_disconnect');
			$link_text = $gianism->_('Disconnect');
			$account = get_user_meta($user_ID, $this->umeta_screen_name, true);
			$desc = '<img src="'.$gianism->url.'/assets/icon-checked.png" alt="Connected" width="16" height="16" />'
			        .sprintf($gianism->_('Your account is already connected with Twitter <a target="_blank" href="%1$s">%2$s</a> .'), 'https://twitter.com/#!/'.$account, "@".$account);
			//If user has pseudo mail, add caution.
			global $user_email;
			if($this->is_pseudo_mail($user_email)){
				$desc .= '<br /><strong>Note:</strong> '.sprintf($gianism->_('Your e-mail address is pseudo &quot;%1$s&quot; and cannot be sent a mail for. If you disconnect twitter account, you may not be able to log in %2$s. Please change it to available e-mail address.'), $user_email, get_bloginfo('name'));
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
			$desc = sprintf($gianism->_('Connecting %1$s account, you can log in %2$s via %1$s account.'),"Twitter", get_bloginfo('name'));
			$onclick = '';
		}
		?>
		<tr>
			<th><?php $gianism->e('Twitter'); ?></th>
			<td>
				<a class="wpg_tw_btn" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
					<i></i>
					<span class="label"><?php echo $link_text;?></span>
				</a>
				<p class="description"><?php echo $desc;?></p>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Show login button on login form
	 * @global WP_Giasnism $gianism
	 */
	public function login_form(){
		global $gianism;
		$redirect_to = $this->get_redirect_to(admin_url('profile.php'));
		$login_url = wp_login_url($redirect_to);
		$login_url .= (false !== strpos($login_url, '?')) ? "&" : '?';
		$login_url .= 'wpg=twitter_login';
		$oauth = $this->get_oauth();
		$token = $oauth->getRequestToken($login_url);
		$this->save_token($token);
		$url = $oauth->getAuthorizeURL($token);
		$link_text = $gianism->_('Login with Twitter');
		$onclick = '';
		$markup = <<<EOS
		<a class="wpg_tw_btn" href="{$url}"{$onclick}>
			<i></i>
			<span class="label">{$link_text}</span>
		</a>
EOS;
		echo $this->filter_link($markup, $url, $link_text, 'twitter');
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
	
	/**
	 * Mail Handler for pseudo mail
	 * @global WP_Gianism $gianism
	 * @param int $user_id
	 * @param string $subject
	 * @param string $message
	 * @param array $headers
	 * @param array $attchment 
	 */
	public function wp_mail($user_id, $subject, $message, $headers, $attchment){
		global $gianism;
		//Save Message
		wp_insert_post(array(
			'post_type' => $gianism->message_post_type,
			'post_title' => $subject,
			'post_content' => $message,
			'post_author' => $user_id,
			'post_status' => 'publish'
		));
		//Send DM
		$this->send_dm($user_id, $subject);
	}
	
	/**
	 * Send direct message on twitter.
	 * @global WP_Gianism $gianism
	 * @param int $user_id
	 * @param string $text 
	 */
	public function send_dm($user_id, $subject){
		global $gianism;
		$oauth = $this->get_oauth($this->my_access_token, $this->my_access_token_secret);
		$twitter_id = get_user_meta($user_id, $this->umeta_id, true);
		if($twitter_id){
			$endpoint = "https://api.twitter.com/1/direct_messages/new.json";
			$body = sprintf($gianism->_('You have message "%1$s" on %2$s. %3$s'), $subject, get_bloginfo('name'), admin_url('profile.php'));
			$result = $oauth->oAuthRequest($endpoint, 'POST', array(
				'user_id' => $twitter_id,
				'text' => $body
			));
		}
	}
	
	/**
	 * Tweet with Owner ID
	 * @global WP_Gianism $gianism
	 * @param string $string 
	 */
	public function tweet($string){
		global $gianism;
		$oauth = $this->get_oauth($this->my_access_token, $this->my_access_token_secret);
		$endpoint = 'https://api.twitter.com/1/statuses/update.json';
		$result = $oauth->oAuthRequest($endpoint, 'POST', array(
			'status' => $string
		));
	}
	
	/**
	 * Force authencated user to follow me
	 * @global WP_Gianism $gianism
	 * @param TwitterOAuth $oauth 
	 */
	private function follow_me($oauth){
		global $gianism;
		if(!empty($this->screen_name)){
			$endpoint = 'http://api.twitter.com/1/friendships/create.json';
			$result = $oauth->oAuthRequest($endpoint, 'POST', array(
				'screen_name' => $this->screen_name,
				'follow' => true
			));
		}
	}
}
