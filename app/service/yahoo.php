<?php

namespace Gianism\Service;

/**
 * Description of twitter_controller
 *
 * @package Gianism\Service
 * @author Takahashi Fumiki
 * @since 2.0.0
 * @property-read \YConnect\YConnectClient $client
 */
class Yahoo extends Common\Mail
{

    /**
     * URL prefix
     *
     * @var string
     */
    public $url_prefix = 'yconnect';

    /**
     * Service name
     *
     * @var string
     */
    public $verbose_service_name = 'Yahoo! Japan';

	/**
	 * @var string
	 */
	protected $yahoo_application_id = '';
	
	/**
	 * @var string
	 */
	protected $yahoo_consumer_secret = '';
		
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
     * Option to access
     *
     * @var array
     */
    protected $option_keys = array('yahoo_application_id', 'yahoo_consumer_secret');

    /**
     * Client holder
     *
     * @ignore
     * @var \YConnect\YConnectClient
     */
    private $_client = null;

    /**
     * Getter
     *
     * @param string $name
     * @return mixed|\YConnect\YConnectClient
     */
    public function __get($name){
        switch($name){
            case 'client':
                if( is_null($this->_client) ){
                    $cred = new \YConnect\ClientCredential($this->yahoo_application_id, $this->yahoo_consumer_secret);
                    $this->_client = new \YConnect\YConnectClient($cred);
                }
                return $this->_client;
                break;
            default:
                return parent::__get($name);
                break;
        }
    }

    /**
     * Detect if user is connected to this service
     *
     * @param int $user_id
     * @return bool
     */
    public function is_connected($user_id){
        return (bool)get_user_meta($user_id, $this->umeta_id, true);
    }

    /**
     * Disconnect user from this service
     *
     * @param int $user_id
     * @return void
     */
    public function disconnect($user_id){
        delete_user_meta($user_id, $this->umeta_id);
        delete_user_meta($user_id, $this->umeta_access_token);
        delete_user_meta($user_id, $this->umeta_refresh_token);
    }

    /**
     * Handle callback request
     *
     * @param string $action
     * @return mixed
     */
    protected function handle_default( $action ){
        /** @var \wpdb $wpdb */
        global $wpdb;
        // Get common values
        $redirect_url = $this->session_get('redirect_to');
        $state = $this->session_get('state');
        $nonce = $this->session_get('nonce');
        switch($action){
            case 'login':
                try{
                    $code_result = $this->client->getAuthorizationCode($state);
                    if( !$code_result ){
                        throw new \Exception($this->api_error_string());
                    }
                    //Got code.
                    $this->client->requestAccessToken($this->get_redirect_endpoint(), $code_result);
                    $this->client->verifyIdToken($nonce);
                    $id_token = $this->client->getIdToken();
                    if($id_token->nonce != $nonce){
                        throw new \Exception($this->api_error_string());
                    }
                    $user_id = $this->get_meta_owner($this->umeta_id, $id_token->user_id);
                    if( !$user_id ){
                        // Test
                        $this->test_user_can_register();
                        //User doesn't exit, let's create new one.
                        $this->client->requestUserInfo($this->client->getAccessToken());
                        $user_info = $this->client->getUserInfo();
                        // Get email
                        if( !is_email($user_info['email']) ){
                            throw new \Exception($this->api_error_string());
                        }
                        $email = $user_info['email'];
                        //Search email and if exist, cannot create user.
                        if( email_exists($email) ){
                            throw new \Exception($this->duplicate_account_string());
                        }
                        //Not found, Create New User
                        require_once(ABSPATH . WPINC . '/registration.php');
                        $user_name = $this->valid_username_from_mail($email);
                        $user_id = wp_create_user($user_name, wp_generate_password(), $email);
                        if( is_wp_error($user_id) ){
                            throw new \Exception($this->registration_error_string());
                        }
                        // Update additional information
                        if( !empty($user_info['name']) ){
                            $wpdb->update(
                                $wpdb->users,
                                array('display_name' => $user_info['name']),
                                array('ID' => $user_id),
                                array('%s'),
                                array('%d')
                            );
                            update_user_meta($user_id, 'nickname', $user_info['name']);
                        }
                        // Name
                        foreach(array('first_name' => 'given_name', 'last_name' => 'family_name') as $meta_key => $token_key){
                            if(!empty($user_info[$token_key])){
                                update_user_meta($user_id, $meta_key, $user_info[$token_key]);
                            }
                        }
                        // Update
                        update_user_meta($user_id, $this->umeta_id, $id_token->user_id);
                        update_user_meta($user_id, $this->umeta_access_token, $this->client->getAccessToken());
                        update_user_meta($user_id, $this->umeta_refresh_token, $this->client->getRefreshToken());
                        $this->user_password_unknown($user_id);
                        $this->hook_connect($user_id, $user_info, true);
                        $this->welcome($user_info['name'] ?: $email);
                    }
                    // Make user logged in
                    wp_set_auth_cookie($user_id, true);
                    $redirect_url = $this->filter_redirect($redirect_url, 'login');
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                    $redirect_url = wp_login_url($redirect_url, true);
                }
                wp_redirect($redirect_url);
                exit;
                break;
            case 'connect':
                try{
                    $code_result = $this->client->getAuthorizationCode($state);
                    if( !$code_result ){
                        throw new \Exception($this->api_error_string());
                    }
                    //Got code.
                    $this->client->requestAccessToken($this->get_redirect_endpoint(), $code_result);
                    $this->client->verifyIdToken($nonce);
                    $id_token = $this->client->getIdToken();
                    if($id_token->nonce != $nonce){
                        throw new \Exception($this->api_error_string());
                    }
                    // Check use existance
                    $user_id = $this->get_meta_owner($this->umeta_id, $id_token->user_id);
                    if($user_id){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    //User doesn't exit, let's create new one.
                    $this->client->requestUserInfo($this->client->getAccessToken());
                    $user_info = $this->client->getUserInfo();
                    // Update
                    update_user_meta(get_current_user_id(), $this->umeta_id, $id_token->user_id);
                    update_user_meta(get_current_user_id(), $this->umeta_access_token, $this->client->getAccessToken());
                    update_user_meta(get_current_user_id(), $this->umeta_refresh_token, $this->client->getRefreshToken());
                    $this->hook_connect(get_current_user_id(), $user_info, false);
                    $this->welcome($user_info['name'] ?: $user_info['email']);
                    $this->filter_redirect($redirect_url, 'connect');
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                }
                wp_redirect($redirect_url);
                exit;
                break;
            default:
                // No action is set, error.
                break;
        }
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
                // Create credentials
                $state = sha1('yconnect_state_'.current_time('timestamp'));
                $nonce = sha1('yconnect_nonce_'.current_time('timestamp'));
                // Write session because YConnect API has no method to get redirect URI,
                // but always directly redirect
                $this->session_write('state', $state);
                $this->session_write('nonce', $nonce);
                $this->session_write('redirect_to', $this->get('redirect_to'));
                $this->session_write('action', $action);
                // Do redirect
                $this->client->requestAuth(
                    $this->get_redirect_endpoint(),
                    $state,
                    $nonce,
                    \YConnect\OAuth2ResponseType::CODE_IDTOKEN,
                    array(
                        \YConnect\OIDConnectScope::PROFILE,
                        \YConnect\OIDConnectScope::EMAIL,
                    ),
                    ($this->is_smartphone() ? \YConnect\OIDConnectDisplay::SMART_PHONE : \YConnect\OIDConnectDisplay::DEFAULT_DISPLAY ),
                    array(\YConnect\OIDConnectPrompt::LOGIN)
                );
                exit;
                break;
            default:
                return false;
                break;
        }
    }
}
