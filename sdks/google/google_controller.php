<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."gianism_controller.php";

/**
 * Description of google_controller
 *
 * @author guy
 */
class Google_Controller extends Gianism_Controller{
	
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
	private $redirect_uri = '';
	
	/**
	 * @var string
	 */
	public $umeta_account = '_wpg_google_account';
	
	/**
	 * @var string
	 */
	public $umeta_plus = "_wpg_google_plus_id";
	
	/**
	 * @var apiClient
	 */
	private $_oauth = null;
	
	/**
	 * @var apiPlusService
	 */
	private $plus = null;
	
	
	/**
	 * Set up option
	 * @param array $option 
	 */
	protected function set_option($option) {
		$option = shortcode_atts(array(
			"ggl_consumer_key" => "",
			"ggl_consumer_secret" => "",
			"ggl_redirect_uri" => ""
		), $option);
		$this->consumer_key = $option['ggl_consumer_key'];
		$this->consumer_secret = $option['ggl_consumer_secret'];
		$this->redirect_uri = $option['ggl_redirect_uri'];
		if(!isset($_SESSION)){
			session_start();
		}
	}
	
	/**
	 * Executed on init hoook
	 * @global int $user_ID
	 * @global wpdb $wpdb
	 * @global WP_Gianism $gianism 
	 */
	public function init_action(){
		if(!$this->is_endpoint()){
			return;
		}
		global $user_ID, $wpdb, $gianism;
		switch($this->get_action()){
			case "google_connect":
				if(isset($_GET['code']) && is_user_logged_in()){
					$this->add_message($gianism->_('Oops, Failed to Authenticate.'));
					$this->api()->authenticate();
					if($this->api()->getAccessToken()){
						//Can authenticate
						$profile = $this->get_profile();
						if(!empty($profile)){
							$mail = $profile['email'];
							$plus_id = isset($profile['id']) ? $profile['id'] : 0;
							//Check if other user has mail address
							$existance = email_exists($mail);
							$sql = <<<EOS
								SELECT user_id FROM {$wpdb->usermeta}
								WHERE meta_key = %s AND user_id != %d AND meta_value = %s
EOS;
							$mail_owner = $wpdb->get_var($wpdb->prepare($sql, $this->umeta_account, $user_ID, $mail));
							$valid = (!$existance || $existance == $user_ID) && !$mail_owner;
							//Check if other user has plus ID
							if($plus_id && $wpdb->get_var($wpdb->prepare($sql, $this->umeta_plus, $user_ID, $plus_id))){
								$valid = false;
							}
							if($valid){
								update_user_meta($user_ID, $this->umeta_account, $mail);
								if($plus_id){
									update_user_meta($user_ID, $this->umeta_plus, $plus_id);
								}
								$this->add_message(sprintf($gianism->_('Welcom, %s!'), $profile['name']));
								do_action('wpg_connect', $user_ID, $profile, 'google', false);
							}else{
								$this->add_message(sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), "Google"));
							}
						}
					}
					$this->do_redirect();
				}
				break;
			case "google_disconnect":
				if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'google_disconnect')){
					delete_user_meta($user_ID, $this->umeta_account);
					delete_user_meta($user_ID, $this->umeta_plus);
					$this->add_message(sprintf($gianism->_("Disconected your %s account."), "Google"));
					$this->do_redirect();
				}
				break;
			case "google_login":
				if(isset($_GET['code']) && !is_user_logged_in()){
					$this->api()->authenticate();
					if($this->api()->getAccessToken()){
						$profile = $this->get_profile();
						if(!empty($profile)){
							$email = $profile['email'];
							$plus_id = isset($profile['id']) ? $profile['id'] : 0;
							$user_id = email_exists($email);
							if(!$user_id){
								$query = <<<EOS
									SELECT user_id FROM {$wpdb->usermeta}
									WHERE meta_key = %s AND meta_value = %s
EOS;
								$user_id = $wpdb->get_var($wpdb->prepare($query, $this->umeta_account, $email));
								if(!$user_id){
									//Not found, Create New User
									require_once(ABSPATH . WPINC . '/registration.php');
									//Check if username exists
									$user_id = wp_create_user($email, wp_generate_password(), $email);
									if(!is_wp_error($user_id)){
										update_user_meta($user_id, $this->umeta_account, $email);
										if($plus_id){
											update_user_meta($user_id, $this->umeta_plus, $plus_id);
										}
										$wpdb->update(
											$wpdb->users,
											array(
												'display_name' => $profile['name']
											),
											array('ID' => $user_id),
											array('%s'),
											array('%d')
										);
										$this->add_message(sprintf($gianism->_('Welcome, %1$s! You are now logged in with %2$s.'), $profile['name'], 'Google'));
										do_action('wpg_connect', $user_id, $profile, 'google', true);
									}
								}
							}
						}
					}
					if(!$user_id || is_wp_error($user_id)){
						$this->add_message('Oops, Failed to Authenticate.');
						$this->set_redirect(wp_login_url($this->get_redirect()));
					}else{
						wp_set_auth_cookie($user_id, true);
					}
					$this->do_redirect();
				}
				break;
		}
	}
	
	/**
	 * Output google connect
	 * @global WP_Gianism $gianism
	 * @global int $user_ID
	 * @return void
	 */
	public function user_profile(){
		if(!defined("IS_PROFILE_PAGE")){
			return;
		}
		global $gianism, $user_ID;
		$google_account = get_user_meta($user_ID, $this->umeta_account, true);
		if($google_account){
			$url = wp_nonce_url($this->redirect_uri, 'google_disconnect');
			$this->set_redirect(admin_url('profile.php'));
			$this->set_action('google_disconnect');
			$link_text = $gianism->_('Disconnect');
			$desc = sprintf($gianism->_('Your account is already connected with Google &lt;%s&gt;.'), esc_html($google_account));
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';
			$p_class = 'description desc-connected desc-connected-google';
		}else{
			$this->set_redirect(admin_url('profile.php'));
			$this->set_action("google_connect");
			$url = $this->api()->createAuthUrl();
			$link_text = $gianism->_('Connect');
			$desc = sprintf($gianism->_('Connecting %1$s account, you can log in %2$s via %1$s account.'),"Google", get_bloginfo('name'));
			$onclick = '';
			$p_class = 'description';
		}
		?>
		<tr>
			<th><?php $gianism->e('Google'); ?></th>
			<td>
				<a class="wpg_ggl_btn" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
					<i></i>
					<?php echo $link_text;?>
				</a>
				<p class="<?php echo $p_class; ?>"><?php echo $desc;?></p>
			</td>
		</tr>
		<?php
	}
	
	/**
	 * Echo login form
	 * @global WP_Gianism $gianism 
	 */
	public function login_form(){
		global $gianism;
		$this->set_redirect($this->get_redirect_to(admin_url('profile.php')));
		$this->set_action('google_login');
		$url = $this->api()->createAuthUrl();
		$link_text = $gianism->_('Login with Google');
		$onclick = '';
		$mark_up = <<<EOS
			<a class="wpg_ggl_btn" href="{$url}"{$onclick}>
				<i></i>
				{$link_text}
			</a>
EOS;
		echo $this->filter_link($mark_up, $url, $link_text, 'google');
}
	
	/**
	 *
	 * @param string $redirect
	 * @return apiClient
	 */
	private function api(){
		if(!$this->_oauth){
			if(!class_exists('apiClient')){
				require_once dirname(__FILE__).DIRECTORY_SEPARATOR."apiClient.php";
			}
			$this->_oauth = new apiClient();
			$this->_oauth->setClientId($this->consumer_key);
			$this->_oauth->setClientSecret($this->consumer_secret);
			$this->_oauth->setRedirectUri($this->redirect_uri);
			$this->_oauth->setApprovalPrompt('auto');
			$this->_oauth->setAccessType('online');
			$this->_oauth->setScopes(array(
				'https://www.googleapis.com/auth/userinfo.profile',
				'https://www.googleapis.com/auth/userinfo.email',
				'https://www.googleapis.com/auth/plus.me'
			));
			if(!class_exists('apiPlusService')){
				require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'contrib/apiPlusService.php';
			}
			$this->plus = new apiPlusService($this->_oauth);
		}
		return $this->_oauth;
	}
	
	/**
	 * Returns Profile
	 * @return array
	 */
	private function get_profile(){
		$req = new apiHttpRequest("https://www.googleapis.com/oauth2/v1/userinfo");
		$req = apiClient::$auth->sign($req);
		$resp = apiClient::$io->makeRequest($req)->getResponseBody();
		return json_decode($resp, 1);
	}
	
	/**
	 * Save redirect url to session
	 * @param string $redirect 
	 */
	private function set_redirect($redirect){
		if($_SESSION){
			$_SESSION['_wpg_ggl_redirect'] = $redirect;
		}
	}
	
	/**
	 * Returns currentlly saved url.
	 * @return string
	 */
	private function get_redirect(){
		if(isset($_SESSION['_wpg_ggl_redirect']) && !empty($_SESSION['_wpg_ggl_redirect'])){
			$url = (string)$_SESSION['_wpg_ggl_redirect'];
			unset($_SESSION['_wpg_ggl_redirect']);
			$redirect = $url;
		}else{
			$redirect = admin_url('profile.php');
		}
		return apply_filters('gianism_redirect_to', $redirect);
	}
	
	/**
	 * Do redirect on session information
	 */
	private function do_redirect(){
		$url = $this->get_redirect();
		if($url && preg_match("/^".str_replace("http://", 'https?:\/\/', str_replace('https:', 'http:', home_url()))."/u", $url)){
			header("Location: ".$url);
			die();
		}
	}
	
	/**
	 * Save action name on session.
	 * @param string $action_name 
	 */
	private function set_action($action_name){
		if(isset($_SESSION)){
			$_SESSION['_wpg_ggl_action'] = (string)$action_name;
		}
	}
	
	/**
	 * Returns action name from string
	 * @return string
	 */
	protected function get_action(){
		if(isset($_SESSION['_wpg_ggl_action']) && !empty($_SESSION['_wpg_ggl_action'])){
			$action = (string)$_SESSION['_wpg_ggl_action'];
			unset($_SESSION['_wpg_ggl_action']);
			return $action;
		}else{
			return "";
		}
	}
	
	/**
	 * Save message
	 * @param string $string 
	 */
	protected function add_message($string){
		if(isset($_SESSION)){
			$_SESSION['_wpg_ggl_message'] = $string;
		}
	}
	
	/**
	 * Return if current url is endpoint.
	 * @return boolean
	 */
	private function is_endpoint(){
		$protocol = is_ssl() ? "https://" : "http://";
		$current_url = $protocol.$_SERVER["SERVER_NAME"];
		$path = explode('?', $_SERVER['REQUEST_URI']);
		$current_url .= $path[0];
		return (untrailingslashit($current_url) == untrailingslashit($this->redirect_uri));
	}
	
	/**
	 * Echo message
	 */
	public function print_script(){
		if(isset($_SESSION) && !empty($_SESSION['_wpg_ggl_message'])){
			echo $this->generate_message_script($_SESSION['_wpg_ggl_message']);
			unset($_SESSION['_wpg_ggl_message']);
		}
	}
}
