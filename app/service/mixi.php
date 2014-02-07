<?php

namespace Gianism\Service;

use Gianism\Option;

/**
 * mixi Controller
 *
 * @package Gianism\Service
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Mixi extends Common\Nomail
{
	
	/**
	 * @var string
	 */
	public $mixi_consumer_key = "";
	
	/**
	 * @var string
	 */
    public $mixi_consumer_secret = "";
	
	/**
	 * @var string
	 */
    public $mixi_access_token = '';
	
	/**
	 * @var string
	 */
    public $mixi_refresh_token = '';
	
	/**
     * User's mixi ID
     *
	 * @var string
	 */
	public $umeta_id = '_mixi_id';
	
	/**
     * User's mixi profile page URL
     *
	 * @var string
	 */
	public $umeta_profile_url = '_mixi_url';
	
	/**
     * Refresh token
     *
	 * @var string
	 */
	public $umeta_refresh_token = '_mixi_refresh_token';
	
	/**
     * Pseudo domain
     *
	 * @var string
	 */
	protected $pseudo_domain = 'pseudo.mixi.jp';
	
	/**
	 * @var string
	 */
	const END_POINT = 'mixi';

    /**
     * Key names to retrieve
     *
     * @var array
     */
    protected $option_keys = array( "mixi_consumer_key", "mixi_consumer_secret", "mixi_access_token", "mixi_refresh_token");

    /**
     * Handle auth action
     *
     * @param \WP_Query $wp_query
     */
    protected function handle_refresh( \WP_Query $wp_query ){
        try{
            if(!current_user_can('manage_options')){
                throw new \Exception($this->_('You have no permission.'));
            }
            if( !($url = $this->get_api_url('refresh')) ){
                throw new \Exception($this->api_error_string());
            }
            $this->session_write('action', 'refresh');
            $this->session_write('redirect_to', $this->get('redirect_to'));
            wp_redirect($url);
            exit;
        }catch (\Exception $e){
            $this->wp_die($e->getMessage(), 403);
        }
    }

    /**
     * Handle callback request
     *
     * @param string $action
     * @return mixed|void
     */
    public function handle_default( $action ){
        /** @var \wpdb $wpdb */
        global $wpdb;
        // Get common values
        $redirect_url = $this->session_get('redirect_to');
        $code = $this->request('code');
        switch($action){
            case 'login':
                try{
                    // Check code existance
                    if( !$code ){
                        throw new \Exception($this->api_error_string());
                    }
                    // Get token
                    $response = $this->get_access_token($code);
                    // Get profile token
                    if( !isset($response->access_token) ){
                        throw new \Exception($this->api_error_string());
                    }
                    $profile = $this->get_profile($response->access_token);
                    if( !isset($profile->entry->id) ){
                        throw new \Exception($this->api_error_string());
                    }
                    $user_id = $this->get_meta_owner($this->umeta_id, $profile->entry->id);
                    if($user_id){
                        // User exists, so update refresh token
                        update_user_meta($user_id, $this->umeta_refresh_token, $response->refresh_token);
                    }else{
                        // Test
                        $this->test_user_can_register();
                        // User not found, thus try to create new User
                        require_once(ABSPATH . WPINC . '/registration.php');
                        $user_login = 'mixi-'.$profile->entry->id;
                        $email = $profile->entry->id.'@'.$this->pseudo_domain;
                        // If exists, return false
                        if( username_exists($user_login) || email_exists($email) ){
                            throw new \Exception($this->duplicate_account_string());
                        }
                        // Create user
                        $user_id = wp_create_user(sanitize_user($user_login), wp_generate_password(), $email);
                        if(is_wp_error($user_id)){
                            throw new \Exception($this->registration_error_string());
                        }
                        update_user_meta($user_id, $this->umeta_id, $profile->entry->id);
                        update_user_meta($user_id, $this->umeta_profile_url, $profile->entry->profileUrl);
                        update_user_meta($user_id, $this->umeta_refresh_token, $response->refresh_token);
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
                        $this->hook_connect($user_id, $profile, true);
                        // Save message
                        $this->welcome(esc_html($profile->entry->displayName));
                    }
                    // Make user logged in
                    wp_set_auth_cookie($user_id, true);
                    $redirect_url = $this->filter_redirect($redirect_url, 'login');
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                    $redirect_url = wp_login_url($redirect_url, true);
                }
                // Redirect
                wp_redirect($redirect_url);
                exit;
                break;
            case 'connect':
                try{
                    // Check code existance
                    if( !$code ){
                        throw new \Exception($this->api_error_string());
                    }
                    // Get token
                    $response = $this->get_access_token($code);
                    // Get profile token
                    if( !isset($response->access_token) ){
                        throw new \Exception($this->api_error_string());
                    }
                    $profile = $this->get_profile($response->access_token);
                    if( !isset($profile->entry->id) ){
                        throw new \Exception($this->api_error_string());
                    }
                    $user_id = $this->get_meta_owner($this->umeta_id, $profile->entry->id);
                    if( $user_id && $user_id != get_current_user_id() ){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    update_user_meta(get_current_user_id(), $this->umeta_id, $profile->entry->id);
                    update_user_meta(get_current_user_id(), $this->umeta_profile_url, $profile->entry->profileUrl);
                    update_user_meta(get_current_user_id(), $this->umeta_refresh_token, $response->refresh_token);
                    $this->hook_connect(get_current_user_id(), $profile, false);
                    $this->welcome(esc_html($profile->entry->displayName));
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                }
                $redirect_url = $this->filter_redirect($redirect_url, 'connect');
                // Redirect
                wp_redirect($redirect_url);
                exit;
                break;
            case 'refresh':
                try{
                    $response = $this->get_access_token($code);
                    if( !isset($response->access_token, $response->refresh_token) ){
                        throw new \Exception($this->api_error_string());
                    }
                    /** @var \Gianism\Option $option */
                    $option = Option::get_instance();
                    if( $option->partial_update(array(
                        'mixi_access_token' => $response->access_token,
                        'mixi_refresh_token' => $response->refresh_token,
                    )) ){
                        $this->add_message($this->_('Refresh token was updated.'));
                    }
                }catch(\Exception $e){
                    $this->add_message($e->getMessage(), true);
                }
                $redirect_url = $this->filter_redirect($redirect_url, 'refresh');
                wp_redirect($redirect_url);
                exit;
                break;
            default:
                // No action is set, error.
                break;
        }
    }
	
	/**
	 * Returns auth endpoint
     *
	 * @param array $scope
	 * @return string 
	 */
	private function get_auth_endpoint($scope = array()){
		$display = $this->is_smartphone() ? 'smartphone' : 'pc';
		$scope = rawurldecode(implode(' ', $scope));
		$key = rawurlencode($this->mixi_consumer_key);
		return "https://mixi.jp/connect_authorize.pl?client_id={$key}&response_type=code&scope={$scope}&display={$display}";
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
			"client_id"  => $this->mixi_consumer_key,
			"client_secret" => $this->mixi_consumer_secret,
			"code" => $code,
			"redirect_uri" => $this->get_endpoint()
		);
		return $this->get_response($endpoint, $request);
	}
	
	/**
	 * Refresh and get new access token.
	 * @param string $refesh_token
	 * @return string 
	 */
	private function get_new_token($refesh_token){
		$endpoint = 'https://secure.mixi-platform.com/2/token';
		$request = array(
			'grant_type' => 'refresh_token',
			'client_id' => $this->mixi_consumer_key,
			'client_secret' => $this->mixi_consumer_secret,
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
		return !empty($this->mixi_refresh_token) && $this->get_new_token($this->mixi_refresh_token);
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
     * Mail Handler for pseudo mail
	 *
	 * @param int $user_id
	 * @param string $subject
	 * @param string $message
	 * @param array|string $headers
	 * @param array|string $attachment
	 */
	public function wp_mail($user_id, $subject, $message, $headers = '', $attachment = ''){
        parent::wp_mail($user_id, $subject, $message, $headers, $attachment);
		$this->send_message($user_id, $subject, $message);
	}
	
	/**
	 * Send message via mixi
     *
	 * @param int $user_id
	 * @param string $subject
	 * @param string $body 
	 */
	public function send_message($user_id, $subject, $body){
		$mixi_id = get_user_meta($user_id, $this->umeta_id, true);
		$token = $this->get_new_token($this->mixi_refresh_token);
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

    /**
     * Detect if user is connected to this service
     *
     * @param int $user_id
     * @return bool
     */
    public function is_connected($user_id){
        return (bool) get_user_meta($user_id, $this->umeta_id, true);
    }

    /**
     * Disconnect user from this service
     *
     * @param int $user_id
     * @return mixed
     */
    public function disconnect($user_id){
        delete_user_meta($user_id, $this->umeta_id);
        delete_user_meta($user_id, $this->umeta_profile_url);
        delete_user_meta($user_id, $this->umeta_refresh_token);
    }

    /**
     * Return api URL to authenticate
     *
     * If you need additional information (ex. token),
     * use $this->session_write inside.
     *
     * <code>
     * $this->session_write('token', $token);
     * return $url;
     * </code>
     *
     * @param string $action 'connect', 'login'
     * @return string|false URL to redirect
     * @throws \Exception
     */
    protected function get_api_url($action){
        switch($action){
            case 'login':
            case 'connect':
                return $this->get_auth_endpoint(array('r_profile'));
                break;
            case 'refresh':
                return $this->get_auth_endpoint(array('w_message'));
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Get refresh token
     *
     * @param string $redirect_to
     * @return string
     */
    public function refresh_button($redirect_to){
        $url = $this->get_redirect_endpoint('refresh', $this->service_name.'_refresh', array(
                'redirect_to' => $redirect_to,
            ));
        $text = $this->_('Refresh token');
        $button = $this->button($text, $url, 'refresh', array('wpg-button', 'wpg-button-auth'), array(
            'gianism-ga-category' => "gianism/{$this->service_name}",
            'gianism-ga-action' => 'refresh',
            'gianism-ga-label' => sprintf($this->_('Login with %s'), $this->verbose_service_name),
        ));
        return $button;

    }

}