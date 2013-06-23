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
	public $screen_name = '';
	
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
	 * Endpoint root. Untrailed with slash.
	 * @var string
	 */
	protected $api_root = 'https://api.twitter.com/1.1/';
	
	/**
	 * @var TwitterOAuth
	 */
	private $oauth = null;
	
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
		if(!isset($_SESSION)){
			session_start();
		}
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
							$user_exists = $wpdb->get_row($wpdb->prepare($sql, $this->umeta_id, $access_token['user_id'], $user_ID, $this->umeta_screen_name, $access_token['screen_name'], $user_ID));
							if(!$user_exists){
								update_user_meta($user_ID, $this->umeta_id, $access_token['user_id']);
								update_user_meta($user_ID, $this->umeta_screen_name, $access_token['screen_name']);
								$this->follow_me();
								do_action('wpg_connect', $user_ID, $access_token, 'twitter', false);
								$this->add_message(sprintf($gianism->_('Welcome, %s!'), '@'.$access_token['screen_name']));
							}else{
								$this->add_message(sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), "Twitter"));
							}
						}else{
							$this->add_message($gianism->_('Oops, Failed to Authenticate.'));
						}
					}else{
						$this->add_message($gianism->_('Oops, Failed to Authenticate.'));
					}
				}else{
					$this->add_message($gianism->_('Oops, Failed to Authenticate.'));
				}
				break;
			case "twitter_disconnect":
				if(wp_verify_nonce($gianism->request('_wpnonce'), 'twitter_disconnect') && is_user_logged_in()){
					delete_user_meta($user_ID, $this->umeta_id);
					delete_user_meta($user_ID, $this->umeta_screen_name);
					$this->add_message($gianism->_('Disconnect now :('));
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
											'user_url' => 'https://twitter.com/'.$screen_name
										),
										array('ID' => $user_id),
										array('%s', '%s'),
										array('%d')
									);
									do_action('wpg_connect', $user_id, $access_token, 'twitter', true);
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
					$this->add_message($gianism->_('Oops, Failed to Authenticate.'));
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
		if(!defined("IS_PROFILE_PAGE") || !IS_PROFILE_PAGE){
			return;
		}
		global $gianism, $user_ID;
		$extra_desc = '';
		if(is_user_connected_with('twitter')){
			$url = wp_nonce_url(admin_url('profile.php?wpg=twitter_disconnect'), 'twitter_disconnect');
			$link_text = $gianism->_('Disconnect');
			$account = get_user_meta($user_ID, $this->umeta_screen_name, true);
			$desc = sprintf($gianism->_('Your account is already connected with %1$s <a target="_blank" href="%2$s">%3$s</a> .'), 'Twitter', 'https://twitter.com/'.$account, "@".$account);
			//If user has pseudo mail, add caution.
			$user_info = get_userdata(get_current_user_id());
			if($this->is_pseudo_mail($user_info->user_email)){
				$extra_desc .= '<p class="desc-extra"><strong>Note:</strong> '.sprintf($gianism->_('Your e-mail address is pseudo &quot;%1$s&quot; and cannot be sent a mail for. If you disconnect %2$s account, you may not be able to log in %3$s. Please change it to available e-mail address.'), $user_info->user_email, 'Twitter', get_bloginfo('name')).'</p>';
			}
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';
			$p_class = 'description desc-connected desc-connected-twitter';
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
			$p_class = 'description';
		}
		?>
		<tr>
			<th><?php $gianism->e('Twitter'); ?></th>
			<td>
				<a class="wpg_tw_btn" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
					<i></i>
					<span class="label"><?php echo $link_text;?></span>
				</a>
				<p class="<?php echo $p_class; ?>"><?php echo $desc;?></p>
				<?php echo $extra_desc; ?>
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
		if(!class_exists('TwitterOAuth')){
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR."twitteroauth.php";
		}
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
		$twitter_id = get_user_meta($user_id, $this->umeta_id, true);
		if($twitter_id){
			$body = sprintf($gianism->_('You have message "%1$s" on %2$s. %3$s'), $subject, get_bloginfo('name'), admin_url('profile.php'));
			return $this->request('direct_messages/new', array(
				'user_id' => $twitter_id,
				'text' => $body
			), 'POST');
		}
	}
	
	/**
	 * Tweet with Owner ID
	 * 
	 * @global WP_Gianism $gianism
	 * @param string $string
	 * @return Object Json format object.
	 */
	public function tweet($string){
		return $this->request('statuses/update', array(
			'status' => $string
		), 'POST');
	}
	
	/**
	 * Force authencated user to follow me
	 * 
	 * @param TwitterOAuth $oauth 
	 * @return Object Json format object.
	 */
	private function follow_me($oauth){
		if(!empty($this->screen_name)){
			return $this->request('friendships/create', array(
				'screen_name' => $this->screen_name,
				'follow' => true
			), 'POST', $oauth);
		}
	}
	
	/**
	 * Returns GET api request.
	 * 
	 * You should know what kind of APIs are available.
	 * Please see
	 * 
	 * @global WP_Gianism $gianism
	 * @param string $endpoint API URL. Must not be started with slash. i.e. 'statuses/user_timeline' Use 'Resource' here https://dev.twitter.com/docs/api/1.1 
	 * @param array $data
	 * @param string $method GET or POST. Default GET
	 * @param TwitterOAuth $oauth If not set, create own.
	 * @return Object Maybe JSON object.
	 */
	public function request($endpoint, $data, $method = 'GET', $oauth = null){
		if(is_null($oauth)){
			$oauth = $this->get_oauth($this->my_access_token, $this->my_access_token_secret);
		}
		return json_decode($oauth->oAuthRequest($this->api_root.$endpoint.'.json', $method, (array)$data));
	}
}
