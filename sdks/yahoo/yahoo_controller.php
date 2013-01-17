<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."gianism_controller.php";
/**
 * Description of twitter_controller
 *
 * @package gianism
 */
class Yahoo_Controller extends Gianism_Controller{
		
	/**
	 * @var string
	 */
	private $application_id = '';
	
	/**
	 * @var string
	 */
	private $consumer_secret = '';
		
	/**
	 * @var string
	 */
	public $umeta_id = '_wpg_yahoo_id';
	
	/**
	 * @var string
	 */
	public $umeta_access_token = '_wpg_yahoo_access_token';
	
	/**
	 * @var string
	 */
	public $umeta_refresh_token = '_wpg_yahoo_refresh_token';
	
	/**
	 * Constructor
	 * @param array $option 
	 */
	protected function set_option($option) {
		$option = shortcode_atts(array(
			'yahoo_enabled' => 0,
			"yahoo_application_id" => '',
			"yahoo_consumer_secret" => "",
		), $option);
		$this->application_id = (string)$option['yahoo_application_id'];
		$this->consumer_secret = (string)$option['yahoo_consumer_secret'];
		if(!isset($_SESSION)){
			session_start();
		}
		//Load Libraries
		if($option['yahoo_enabled']){
			set_include_path(implode(PATH_SEPARATOR, array(
				get_include_path(),
				dirname(__FILE__).'/lib',
				dirname(__FILE__).'/jwt')));
			require_once dirname(__FILE__).'/lib/YConnect.inc';
		}
	}
	
	/**
	 * Executed on init hook
	 * @global wpdb $wpdb
	 * @global WP_Gianism $gianism
	 */
	public function init_action(){
		global $wpdb, $gianism;
		switch($this->get_action()){
			case 'yahoo_connect':
				//Start Redirect if nonce is set
				if(is_user_logged_in() && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'yahoo_connect')){
					$_SESSION['yahoo_nonce'] = $_GET['_wpnonce'];
					$_SESSION['yahoo_state'] = sha1(get_current_user_id());
					$_SESSION['yahoo_redirect'] = admin_url('profile.php');
					$_SESSION['yahoo_action'] = 'connect';
					$this->get_client()->requestAuth(
						$this->get_callback_uri(),
						$_SESSION['yahoo_state'],
						$_SESSION['yahoo_nonce'],
						OAuth2ResponseType::CODE_IDTOKEN,
						array(
							OIDConnectScope::PROFILE,
							OIDConnectScope::EMAIL,
						),
						($this->is_smartphone() ? OIDConnectDisplay::SMART_PHONE : OIDConnectDisplay::DEFAULT_DISPLAY ),
						OIDConnectPrompt::LOGIN
					);
				}else{
					header('Location:'.admin_url('profile.php'));
				}
				die();
				break;
			case "yahoo_disconnect":
				if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($gianism->request('_wpnonce'), 'yahoo_disconnect') && is_user_logged_in()){
					delete_user_meta(get_current_user_id(), $this->umeta_id);
					delete_user_meta(get_current_user_id(), $this->umeta_access_token);
					delete_user_meta(get_current_user_id(), $this->umeta_refresh_token);
					$this->add_message(sprintf($gianism->_('Your accont is now disconnected from %s.'), 'Yahoo! JAPAN'));
					header('Location: '.admin_url('profile.php'));
					exit();
				}
				break;
			case "yahoo_login":
				if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'yahoo_login') && !is_user_logged_in()){
					$_SESSION['yahoo_nonce'] = $_REQUEST['_wpnonce'];
					$_SESSION['yahoo_state'] = sha1($_REQUEST['_wpnonce']);
					$_SESSION['yahoo_redirect'] = $this->get_redirect_to(admin_url('profile.php'));
					$_SESSION['yahoo_action'] = 'login';
					$this->get_client()->requestAuth(
						$this->get_callback_uri(),
						$_SESSION['yahoo_state'],
						$_SESSION['yahoo_nonce'],
						OAuth2ResponseType::CODE_IDTOKEN,
						array(
							OIDConnectScope::PROFILE,
							OIDConnectScope::EMAIL,
						),
						($this->is_smartphone() ? OIDConnectDisplay::SMART_PHONE : OIDConnectDisplay::DEFAULT_DISPLAY ),
						OIDConnectPrompt::LOGIN
					);
				}else{
					header('Location: '.wp_login_url($this->get_redirect_to(null), true));
				}
				exit();
				break;
			default:
				if(isset($_SESSION['yahoo_action'])){
					switch($_SESSION['yahoo_action']){
						case 'connect':
							try{
								$state = $_SESSION['yahoo_state'];
								$nonce = $_SESSION['yahoo_nonce'];
								$redirect = $_SESSION['yahoo_redirect'];
								$client = $this->get_client();
								$code_result = $client->getAuthorizationCode($state);
								if(!$code_result){
									//Cannot get Code
									$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
								}else{
									//Got code.
									$client->requestAccessToken($this->get_callback_uri(), $code_result);
									$client->verifyIdToken($nonce);
									$id_token = $client->getIdToken();
									if($id_token->nonce != $nonce){
										throw new Exception('Invalid nonce.');
									}
									$client->requestUserInfo($client->getAccessToken());
									$user_info = $client->getUserInfo();
									//Check if user is not exists.
									if($wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->usermeta} WHERE user_id != %d AND meta_key = %s AND meta_value = %s", get_current_user_id(), $this->umeta_id, $id_token->user_id))){
										$this->add_message($gianism->_('Ooops, another account uses same Yahoo! ID.'));
									}else{
										update_user_meta(get_current_user_id(), $this->umeta_id, $id_token->user_id);
										update_user_meta(get_current_user_id(), $this->umeta_access_token, $client->getAccessToken());
										update_user_meta(get_current_user_id(), $this->umeta_refresh_token, $client->getRefreshToken());
										$this->add_message(sprintf($gianism->_('Welcome, %s! Your account is now connected with Yahoo! JAPAN.'), $user_info->name));
										do_action('wpg_connect', get_current_user_id(), $user_info, 'yahoo', false);
									}
								}
							}catch(OAuth2ApiException $ae){
								$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
							}catch(OAuth2TokenException $te){
								$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
							}catch(Exception $e){
								$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
							}
							break;
						case 'login':
							$redirect = wp_login_url();
							try{
								$state = $_SESSION['yahoo_state'];
								$nonce = $_SESSION['yahoo_nonce'];
								$client = $this->get_client();
								$code_result = $client->getAuthorizationCode($state);
								if(!$code_result){
									//Cannot get Code
									$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
								}else{
									//Got code.
									$client->requestAccessToken($this->get_callback_uri(), $code_result);
									$client->verifyIdToken($nonce);
									$id_token = $client->getIdToken();
									if($id_token->nonce != $nonce){
										throw new Exception('Invalid nonce.');
									}
									if(($user_id = $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", $this->umeta_id, $id_token->user_id)))){
										//If user is exits, logged in as him.
										wp_set_auth_cookie($user_id, true);
										$redirect = $_SESSION['yahoo_redirect'];
									}else{
										//User doesn't exit, let's create new one.
										$client->requestUserInfo($client->getAccessToken());
										$user_info = $client->getUserInfo();
										if(email_exists($user_info->email)){
											//Search email and if exist, cannot create user.
											$this->add_message(sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), 'Yahoo! JAPAN'));
										}else{
											//Not found, Create New User
											require_once(ABSPATH . WPINC . '/registration.php');
											$user_id = wp_create_user('yahoo-'.$id_token->user_id, wp_generate_password(), $user_info->email);
											if(is_wp_error($user_id)){
												$this->add_message($gianism->_('Sorry, but cannot create account. Please try again later.'));
											}else{
												if($user_info->name){
													$wpdb->update(
														$wpdb->users,
														array('display_name' => $user_info->name),
														array('ID' => $user_id),
														array('%s'),
														array('%d')
													);
													update_user_meta($user_id, 'nickname', $user_info->name);
												}
												update_user_meta($user_id, 'first_name', $user_info->given_name);
												update_user_meta($user_id, 'last_name', $user_info->family_name);
												update_user_meta($user_id, $this->umeta_id, $id_token->user_id);
												update_user_meta($user_id, $this->umeta_access_token, $client->getAccessToken());
												update_user_meta($user_id, $this->umeta_refresh_token, $client->getRefreshToken());
												wp_set_auth_cookie($user_id, true);
												$this->add_message(sprintf($gianism->_('Welcome, %1$s! You are now logged in with %2$s.'), $user_info->name, 'Yahoo! JAPAN account'));
												do_action('wpg_connect', $user_id, $user_info, 'yahoo', true);
											}
										}
									}
								}
							}catch(OAuth2ApiException $ae){
								$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
							}catch(OAuth2TokenException $te){
								$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
							}catch(Exception $e){
								$this->add_message($gianism->_('Cannot connect with Yahoo! JAPAN. Please try again later.'));
							}
							break;
					}
					unset($_SESSION['yahoo_nonce'], $_SESSION['yahoo_state'], $_SESSION['yahoo_redirect'], $_SESSION['yahoo_action']);
					if(isset($redirect)){
						header('Location:'.$redirect);
						die();
					}
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
		global $gianism;
		if(is_user_connected_with('yahoo')){
			$link_text = $gianism->_('Disconnect');
			$desc = sprintf($gianism->_('Your account is already connected with %1$s.'), 'Yahoo! JAPAN');
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';
			$url = esc_url(wp_nonce_url($this->get_redirect_endpoint('yahoo_disconnect'), 'yahoo_disconnect'));
			$p_class = 'description desc-connected desc-connected-yahoo';
		}else{
			//Create Link
			$url = esc_url(wp_nonce_url($this->get_redirect_endpoint('yahoo_connect'), "yahoo_connect"));
			$link_text = $gianism->_('Connect');
			$onclick = '';
			$desc = sprintf($gianism->_('Connecting %1$s account, you can log in %2$s via %1$s account.'),"Yahoo! JAPAN", get_bloginfo('name'));
			$p_class = 'description';
		}
		?>
		<tr>
			<th><?php $gianism->e('Yahoo! JAPAN'); ?></th>
			<td>
				<a class="wpg_yahoo_btn" href="<?php echo $url; ?>"<?php echo $onclick; ?>>
					<i></i>
					<span class="label"><?php echo $link_text;?></span>
				</a>
				<p class="<?php echo $p_class; ?>"><?php echo $desc;?></p>
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
		$link_text = $gianism->_('Log in with Yahoo! JAPAN');
		$url = wp_nonce_url($this->get_redirect_endpoint('yahoo_login', array('redirect_to' => $this->get_redirect_to(admin_url('profile.php')))), 'yahoo_login');
		$onclick = '';
		$markup = <<<EOS
		<a class="wpg_yahoo_btn" href="{$url}"{$onclick}>
			<i></i>
			<span class="label">{$link_text}</span>
		</a>
EOS;
		echo $this->filter_link($markup, $url, $link_text, 'yahoo');
	}
	
	/**
	 * Returns YConnect Client
	 * @return \YConnectClient
	 */
	private function get_client(){
		$cred = new ClientCredential($this->application_id, $this->consumer_secret);
		return new YConnectClient($cred);
	}
	
	/**
	 * Returns callback URL
	 * @return string
	 */
	private function get_callback_uri(){
		return home_url('/yconnect/', $this->is_ssl_required() ? 'https' : 'http');
	}
	
	/**
	 * Save message on Session
	 * @param string $message
	 */
	protected function add_message($string){
		if(isset($_SESSION)){
			$_SESSION['_wpg_yahoo_message'] = $string;
		}
	}
	
	/**
	 * Echo message
	 */
	public function print_script(){
		if(isset($_SESSION) && !empty($_SESSION['_wpg_yahoo_message'])){
			echo $this->generate_message_script($_SESSION['_wpg_yahoo_message']);
			unset($_SESSION['_wpg_yahoo_message']);
		}
	}
}
