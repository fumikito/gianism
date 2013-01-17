<?php
require_once dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."gianism_controller.php";

class Mixi_Controller extends Gianism_Controller{
	
	/**
	 * @var string
	 */
	private $consumer_key = "";
	
	/**
	 * @var string
	 */
	private $consumer_secret = "";
	
	/**
	 * @var string
	 */
	private $access_token = '';
	
	/**
	 * @var string
	 */
	private $refresh_token = '';
	
	/**
	 * @var string
	 */
	public $umeta_id = '_mixi_id';
	
	/**
	 * @var string
	 */
	public $umeta_profile_url = '_mixi_url';
	
	/**
	 * @var string
	 */
	public $umeta_refresh_token = '_mixi_refresh_token';
	
	/**
	 * @var string
	 */
	protected $pseudo_domain = 'pseudo.mixi.jp';
	
	/**
	 * @var string
	 */
	const END_POINT = 'mixi';
	
	/**
	 * Setup Option
	 * @param array $option 
	 */
	protected function set_option($option){
		$option = shortcode_atts(array(
			"mixi_consumer_key" => '',
			"mixi_consumer_secret" => '',
			"mixi_access_token" => '',
			"mixi_refresh_token" => ''
		), $option);
		$this->consumer_key = $option['mixi_consumer_key'];
		$this->consumer_secret = $option['mixi_consumer_secret'];
		$this->access_token = $option['mixi_access_token'];
		$this->refresh_token = $option['mixi_refresh_token'];
		if(!isset($_SESSION)){
			session_start();
		}
	}
	
	/**
	 * Executed on init hook
	 * @global int $user_ID
	 * @global wpdb $wpdb
	 * @global WP_Gianism $gianism 
	 */
	public function init_action(){
		global $user_ID, $wpdb, $gianism;
		switch($this->get_action()){
			case 'mixi_auth':
				if(is_user_logged_in() && current_user_can('manage_options') && isset($_GET['_wpnonce']) &&  wp_verify_nonce($_GET['_wpnonce'], 'mixi_auth')){
					$_SESSION['mixi_redirect'] = 'mixi_auth';
					$endpoint = $this->get_auth_endpoint(array('w_message'));
					header('Location: '.$endpoint);
					die();
				}
				break;
			case 'mixi_connect':
				if(is_user_logged_in() && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mixi_connect')){
					//Redirect to mixi
					$_SESSION['mixi_redirect'] = 'mixi_connect';
					$endpoint = $this->get_auth_endpoint(array('r_profile'));
					header('Location: '.$endpoint);
					die();
				}
				break;
			case 'mixi_disconnect':
				if(is_user_logged_in() && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mixi_disconnect')){
					//Remove User meta and alert.
					delete_user_meta(get_current_user_id(), $this->umeta_id);
					delete_user_meta(get_current_user_id(), $this->umeta_profile_url);
					delete_user_meta(get_current_user_id(), $this->umeta_refresh_token);
					$this->add_message($gianism->_('Disconnect now :('));
					header('Location: '.admin_url('profile.php'));
					die();
				}
				break;
			case 'mixi_register':
				if(!is_user_logged_in() && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mixi_register')){
					$_SESSION['mixi_redirect'] = 'mixi_register';
					$_SESSION['mixi_redirect_to'] = $this->get_redirect_to(admin_url('profile.php'));
					$endpoint = $this->get_auth_endpoint(array('r_profile'));
					header('Location: '.$endpoint);
					die();
				}
				break;
			default:
				if(false !== strpos($_SERVER['REQUEST_URI'], "/mixi/") && isset($_SESSION['mixi_redirect'])){
					switch($_SESSION['mixi_redirect']){
						case 'mixi_auth':
							unset($_SESSION['mixi_redirect']);
							$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : null;
							$response = $this->get_access_token($code);
							if(isset($response->access_token) && isset($response->refresh_token)){
								$gianism->option['mixi_access_token'] = $response->access_token;
								$gianism->option['mixi_refresh_token'] = $response->refresh_token;
								update_option("{$gianism->name}_option", $gianism->option);
							}
							header("Location: ".admin_url('users.php?page=gianism'));
							die();
							break;
						case 'mixi_connect':
							unset($_SESSION['mixi_redirect']);
							$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : null;
							if($code){
								//Got code.
								$message = $gianism->_('Oops, Failed to Authenticate.');
								$response = $this->get_access_token($code);
								if(isset($response->access_token)){
									$profile = $this->get_profile($response->access_token);
									if(isset($profile->entry->id)){
										//Check if other user is connected with
										$sql = <<<EOS
											SELECT umeta_id FROM {$wpdb->usermeta}
											WHERE ((meta_key = %s) AND (meta_value = %s) AND (user_id != %d))
											OR ((meta_key = %s) AND (meta_value = %s) AND (user_id != %d))

EOS;
										if($wpdb->get_row($wpdb->prepare($sql, $this->umeta_id, $profile->entry->id, $user_ID, $this->umeta_profile_url, $profile->entry->profileUrl, $user_ID))){
											$message = sprintf($gianism->_('Mm...? This %s account seems to be connected to another account.'), "mixi");
										}else{
											$message = sprintf($gianism->_('Welcom, %s!'), $profile->entry->displayName);
											update_user_meta($user_ID, $this->umeta_id, $profile->entry->id);
											update_user_meta($user_ID, $this->umeta_profile_url, $profile->entry->profileUrl);
											update_user_meta($user_ID, $this->umeta_refresh_token, $response->refresh_token);
											//If user profile url is empty, save.
											global $user_url;
											if(empty($user_url) || $user_url == 'http://'){
												$wpdb->update(
													$wpdb->users,
													array('user_url' => $profile->entry->profileUrl),
													array('ID' => $user_ID),
													array('%s'), array('%d')
												);
											}
											do_action('wpg_connect', $user_ID, $profile, 'mixi', false);
										}
									}else{
										$message .= '\n'.$gianism->_("Failed to get mixi ID.");
									}
								}else{
									$message .= '\n'.$gianism->_("Invalid access token.");
								}
								$this->add_message($message);
								header('Location: '.admin_url('profile.php'));
								die();
							}
							break;
						case 'mixi_register':
							unset($_SESSION['mixi_redirect']);
							$code = isset($_REQUEST['code']) ? $_REQUEST['code'] : null;
							$message = $gianism->_('Oops, Failed to Authenticate.');
							$redirect_to = wp_login_url();
							if($code){
								//Got code.
								$response = $this->get_access_token($code);
								if(isset($response->access_token)){
									$profile = $this->get_profile($response->access_token);
									if(isset($profile->entry->id)){
										//Got mixi ID and try to detect user.
										$sql = <<<EOS
											SELECT user_id FROM {$wpdb->usermeta}
											WHERE meta_key = %s AND meta_value = %s
EOS;
										$user_id = $wpdb->get_var($wpdb->prepare($sql, $this->umeta_id, $profile->entry->id));
										if(!$user_id){
											//Not found, thus try to create new User
											require_once(ABSPATH . WPINC . '/registration.php');
											$user_login = 'mixi-'.$profile->entry->id;
											$email = $profile->entry->id.'@'.$this->pseudo_domain;
											$user_id = wp_create_user(sanitize_user($user_login), wp_generate_password(), $email);
											if(!is_wp_error($user_id)){
												update_user_meta($user_id, $this->umeta_id, $profile->entry->id);
												update_user_meta($user_id, $this->umeta_profile_url, $profile->entry->profileUrl);
												update_user_meta($use_id, $this->umeta_refresh_token, $response->refresh_token);
												$wpdb->update(
													$wpdb->users,
													array(
														'display_name' => $profile->entry->displayName,
														'user_url' => $profile->entry->profileUrl
													),
													array('ID' => $user_id),
													array('%s', '%s'),
													array('%d')
												);
												do_action('wpg_connect', $user_id, $profile, 'mixi', true);
												$redirect_to = $_SESSION['mixi_redirect_to'];
												$message = sprintf($gianism->_('Welcome, %s!'), $profile->entry->displayName);
											}else{
												$message .= '\n'.$user_id->get_error_message();
											}
										}else{
											$redirect_to = $_SESSION['mixi_redirect_to'];
											update_user_meta($use_id, $this->umeta_refresh_token, $response->refresh_token);
											$message = '';
										}
										if($user_id && !is_wp_error($user_id)){
											wp_set_auth_cookie($user_id, true);
										}
									}else{
										$message .= '\n'.$gianism->_('Failed to get mixi ID.');
									}
								}else{
									$message .= '\n'.$gianism->_("Invalid access token.");
								}
							}else{
								$message .= '\n'.$gianism->_('Failed to get mixi ID.');
							}
							unset($_SESSION['mixi_redirect_to']);
							$this->add_message($message);
							header('Location: '.$redirect_to);
							die();
							break;
						default:
							break;
					}
				}
				break;
		}
	}
	
	/**
	 * Show login button on login form
	 * @global WP_Gianism $gianism 
	 */
	public function login_form(){
		global $gianism;
		$redirect_to = $this->get_redirect_to(admin_url('profile.php'));
		$link_text = $gianism->_('Login with mixi');
		$url = esc_url(wp_nonce_url($this->get_redirect_endpoint('mixi_register', array('redirect_to' => $redirect_to)), 'mixi_register'));
		$markup = <<<EOS
		<a class="wpg_mixi_btn" href="{$url}">
			<i></i>
			<span class="label">{$link_text}</span>
		</a>
EOS;
		echo $this->filter_link($markup, $url, $link_text, 'mixi');
	}
	
	/**
	 * Show connect button on login form
	 * 
	 * @global WP_Gianism $gianism
	 * @global int $user_ID
	 * @return void 
	 */
	public function user_profile(){
		if(!defined('IS_PROFILE_PAGE') || !IS_PROFILE_PAGE){
			return;
		}
		global $gianism;
		$extra_desc = '';
		if(is_user_connected_with('mixi')){
			$link_text = $gianism->_('Disconnect');
			$desc = sprintf($gianism->_('Your account is already connected with %1$s <a target="_blank" href="%2$s">%3$s</a> .'), 'mixi', get_user_meta(get_current_user_id(), $this->umeta_profile_url, true), " (&raquo;".$gianism->_('View Profile').')');
			//If user has pseudo mail, add caution.
			$user_info = get_userdata(get_current_user_id());
			if($this->is_pseudo_mail($user_info->user_email)){
				$extra_desc .= '<p class="desc-extra"><strong>Note:</strong> '.sprintf($gianism->_('Your e-mail address is pseudo &quot;%1$s&quot; and cannot be sent a mail for. If you disconnect %2$s account, you may not be able to log in %3$s. Please change it to available e-mail address.'), $user_info->user_email, 'mixi', get_bloginfo('name')).'</p>';
			}
			$onclick = ' onclick="if(!confirm(\''.$gianism->_('You really disconnect this account?').'\')) return false;"';;
			$url = esc_url(wp_nonce_url($this->get_redirect_endpoint('mixi_disconnect'), 'mixi_disconnect'));
			$p_class = 'description desc-connected desc-connected-mixi';
		}else{
			$link_text = $gianism->_('Connect');
			$onclick = '';
			$desc = sprintf($gianism->_('Connecting %1$s account, you can log in %2$s via %1$s account.'),"mixi", get_bloginfo('name'));
			$url = esc_url(wp_nonce_url($this->get_redirect_endpoint('mixi_connect'), "mixi_connect"));
			$p_class = 'description';
		}
		?>
		<tr>
			<th>mixi</th>
			<td>
				<a class="wpg_mixi_btn" href="<?php echo esc_url($url); ?>"<?php echo $onclick; ?>>
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
	 * Returns auth endpoint
	 * @param array $scope
	 * @return string 
	 */
	private function get_auth_endpoint($scope = array()){
		$display = $this->is_smartphone() ? 'smartphone' : 'pc';
		$scope = rawurldecode(implode(' ', $scope));
		$key = rawurlencode($this->consumer_key);
		return "https://mixi.jp/connect_authorize.pl?client_id={$key}&response_type=code&scope={$scope}&display={$display}";
	}
	
	/**
	 * Return link to 
	 * @return type 
	 */
	public function get_admin_auth_link(){
		return wp_nonce_url($this->get_redirect_endpoint('mixi_auth'), 'mixi_auth');
	}
	
	/**
	 * Get Access Token
	 * @param string $code 
	 * @return string
	 */
	private function get_access_token($code){
		$endpoint = 'https://secure.mixi-platform.com/2/token';
		$request = array(
			"grant_type" => "authorization_code",
			"client_id"  => $this->consumer_key,
			"client_secret" => $this->consumer_secret,
			"code" => $code,
			"redirect_uri" => $this->get_endpoint()
		);
		return $this->get_response($endpoint, $request);
	}
	
	/**
	 * Refresh and get new access token.
	 * @param strng $refesh_token
	 * @return string 
	 */
	private function get_new_token($refesh_token){
		$endpoint = 'https://secure.mixi-platform.com/2/token';
		$request = array(
			'grant_type' => 'refresh_token',
			'client_id' => $this->consumer_key,
			'client_secret' => $this->consumer_secret,
			'refresh_token' => $refesh_token
		);
		$response = $this->get_response($endpoint, $request);
		return isset($response->access_token) ? $response->access_token : null;
	}
	
	/**
	 * Check if valid option is set.
	 * @return boolean 
	 */
	public function has_valid_refresh_token(){
		return !empty($this->refresh_token) && $this->get_new_token($this->refresh_token);
	}
	
	
	
	/**
	 * Get Use profile
	 * @param string $token
	 * @return array 
	 */
	private function get_profile($token){
		$endpoint = 'http://api.mixi-platform.com/2/people/@me/@self';
		$request = array(
			'oauth_token' => $token,
			'format' => 'json'
		);
		return $this->get_response($endpoint, $request, 'GET');
	}
	
	/**
	 * Get Request
	 * @param string $endpoint
	 * @param array $request
	 * @param string $method
	 * @return array 
	 */
	private function get_response($endpoint, $request = array(), $method = 'POST', $json = false){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if($json){
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		}
		switch($method){
			case "POST":
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
				break;
			case "GET":
				$args = array();
				foreach($request as $key => $val){
					$args[] = $key ."=".rawurlencode($val);
				}
				if(!empty($args)){
					$endpoint .= '?'.implode('&', $args);
				}
				break;
			default:
				return;
				break;
		}
		curl_setopt($ch, CURLOPT_URL, $endpoint);
		$response = curl_exec($ch);
		curl_close($ch);
		return json_decode($response);
		
	}
	
	/**
	 * Get Endpoint URL
	 * @return string
	 */
	private function get_endpoint(){
		$endpoint = trailingslashit(home_url())."mixi/";
		if((defined('FORCE_SSL_LOGIN') && FORCE_SSL_LOGIN) || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN)){
			$endpoint = str_replace('http:', 'https:', $endpoint);
		}
		return $endpoint;
	}
	
	/**
	 * Save message in Session
	 * @param type $message 
	 */
	protected function add_message($message){
		if(isset($_SESSION)){
			$_SESSION['_wpg_mixi_message'] = $message;
		}
	}
	
	/**
	 * Echo message if exist 
	 */
	public function print_script(){
		if(isset($_SESSION) && !empty($_SESSION['_wpg_mixi_message'])){
			echo $this->generate_message_script($_SESSION['_wpg_mixi_message']);
			unset($_SESSION['_wpg_mixi_message']);
		}
	}
	/* Mail Handler for pseudo mail
	 * @global WP_Gianism $gianism
	 * @param int $user_id
	 * @param string $subject
	 * @param string $message
	 * @param array $headers
	 * @param array $attchment 
	 */
	public function wp_mail($user_id, $subject, $message, $headers, $attchment){
		global $gianism;
		wp_insert_post(array(
			'post_type' => $gianism->message_post_type,
			'post_title' => $subject,
			'post_content' => $message,
			'post_author' => $user_id,
			'post_status' => 'publish'
		));
		$this->send_message($user_id, $subject, $message);
	}
	
	/**
	 * Send message via mixi
	 * @param int $user_id
	 * @param string $subject
	 * @param string $body 
	 */
	public function send_message($user_id, $subject, $body){
		$mixi_id = get_user_meta($user_id, $this->umeta_id, true);
		$token = $this->get_new_token($this->refresh_token);
		if($mixi_id && $token){
			$endpoint = "http://api.mixi-platform.com/2/messages/@me/@self/@outbox?oauth_token={$token}&format=json";
			$request = json_encode(array(
				'title' => $subject,
				'body' => $body,
				'recipients' => array($mixi_id)
			));
			$this->get_response($endpoint, $request, 'POST', true);
		}
	}
	
}