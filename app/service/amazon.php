<?php

namespace Gianism\Service;

/**
 * Amazon Controller
 *
 * @package Gianism\Service
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Amazon extends Common\Mail
{

    /**
     * URL prefix
     *
     * @var string
     */
    public  $url_prefix = 'amazon-auth';

    /**
     * Verbose service name
     *
     * @var string
     */
    public $verbose_service_name = 'Amazon';

    /**
     * Amazon's client ID
     *
     * @var string
     */
    protected $amazon_client_id = '';

    /**
     * Amazon'2 client secret
     *
     * @var string
     */
    protected $amazon_client_secret = '';


    /**
     * User meta key for amazon id
     *
     * @var string
     */
    public $umeta_id = '_wpg_amazon_id';


    /**
     * Option key to copy
     *
     * @var array
     */
    protected $option_keys = array('amazon_client_id', 'amazon_client_secret');

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
    }

    /**
     * Handle callback request
     *
     * This function must exit at last.
     *
     * @param string $action
     * @return void
     */
    protected function handle_default($action){
        global $wpdb;
        // Get common values
        $redirect_url = $this->session_get('redirect_to');
        $saved_state = $this->session_get('state');
        $state = $this->get('state');
        $code = $this->get('code');
        switch( $action ){
            case 'login':
                try{
                    $user_info = $this->get_user_profile($code, $state, $saved_state);
                    $user_id = $this->get_meta_owner($this->umeta_id, $user_info->user_id);
                    if( !$user_id ){
                        $this->test_user_can_register();
                        // Create user
                        require_once ABSPATH.WPINC.'/registration.php';
                        // Check email
                        if( email_exists($user_info->email) ){
                            throw new \Exception($this->duplicate_account_string());
                        }
                        // Check user name
                        $user_name = $this->valid_username_from_mail($user_info->email);
                        $user_id = wp_create_user($user_name, wp_generate_password(), $user_info->email);
                        if( is_wp_error($user_id) ){
                            throw new \Exception($this->registration_error_string());
                        }
                        // Update extra information
                        update_user_meta($user_id, $this->umeta_id, $user_info->user_id);
                        update_user_meta($user_id, 'nickname', $user_info->name);
                        $wpdb->update(
                            $wpdb->users,
                            array(
                                'display_name' => $user_info->name,
                            ),
                            array('ID' => $user_id),
                            array('%s'),
                            array('%d')
                        );
                        // Password is unknown
                        $this->user_password_unknown($user_id);
                        $this->hook_connect($user_id, $user_info, true);
                        $this->welcome($user_info->name);
                    }
                    wp_set_auth_cookie($user_id, true);
                    $redirect_url = $this->filter_redirect($redirect_url, 'login');
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                    $redirect_url = wp_login_url($redirect_url);
                }
                wp_redirect($redirect_url);
                exit;
                break;
            case 'connect':
                try{
                    // Is user logged in?
                    if( !is_user_logged_in() ){
                        throw new \Exception($this->_('You must be logged in'));
                    }
                    // Get user info
                    $user_info = $this->get_user_profile($code, $state, $saved_state);
                    $owner = $this->get_meta_owner($this->umeta_id, $user_info->user_id);
                    if( $owner ){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    // O.k.
                    update_user_meta(get_current_user_id(), $this->umeta_id, $user_info->user_id);
                    $this->hook_connect(get_current_user_id(), $user_info, false);
                    $this->welcome((string)$user_info->name);
                }catch (\Exception $e){
                    $this->auth_fail($e->getMessage());
                }
                wp_redirect($this->filter_redirect($redirect_url, 'connect'));
                exit;
                break;
        }
    }

    /**
     * Get user profile
     *
     * @param string $code
     * @param string $state
     * @param string $saved_state
     * @return array
     * @throws \Exception
     */
    private function get_user_profile($code, $state, $saved_state){
        $token = $this->get_access_token($code, $state, $saved_state);
        $user_info = $this->get_response('https://api.amazon.com/user/profile', array(
            'access_token' => $token,
        ), 'GET');
        if( !$user_info || isset($user_info->error) ){
            throw new \Exception($this->mail_fail_string());
        }
        return $user_info;
    }

    /**
     * Test token
     *
     * @param string $code
     * @param string $state
     * @param string $saved_state
     * @return bool
     * @throws \Exception
     */
    private function get_access_token($code, $state, $saved_state){
        if( !$code || !$state || !$saved_state || $state != $saved_state ){
            throw new \Exception($this->api_error_string());
        }
        $endpoint = 'https://api.amazon.com/auth/o2/token';
        $response = $this->get_response($endpoint, array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->get_redirect_endpoint(),
            'client_id' => $this->amazon_client_id,
            'client_secret' => $this->amazon_client_secret,
        ), 'POST');
        if( !$response || isset($response->error) || !isset($response->access_token)){
            throw new \Exception($this->api_error_string());
        }
        return $response->access_token;
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
            case 'connect':
            case 'login':
                $state = sha1(uniqid('amazon_'.$action, true));
                $this->session_write('state', $state);
                return 'https://www.amazon.com/ap/oa?'.http_build_query(array(
                    'client_id' => $this->amazon_client_id,
                    'scope' => 'profile',
                    'response_type' => 'code',
                    'state' => $state,
                    'redirect_uri' => $this->get_redirect_endpoint(),
                ));
                break;
            default:
                return false;
                break;
        }
    }


}