<?php

namespace Gianism\Service;

/**
 * Github client
 *
 * @package Gianism\Service
 * @author Takahashi Fumiki
 * @since 2.0.0
 */
class Github extends Common\Mail
{

    /**
     * URL prefix to prepend
     *
     * @var string
     */
    public $url_prefix = 'github-auth';

    /**
     * Verbose service name
     *
     * @var string
     */
    public $verbose_service_name = 'Github';

	/**
	 * @var string
	 */
	protected  $github_client_id = '';
	
	/**
	 * @var string
	 */
	protected  $github_client_secret = '';
	
	/**
	 * @var string
	 */
	public $umeta_id = '_wpg_github_id';

    /**
     * @var string
     */
    public $umeta_login = '_wpg_github_login';

    /**
     * Option to retrieve
     *
     * @var array
     */
    protected $option_keys = array("github_client_id", "github_client_secret");


    /**
     * Handle callback request
     *
     * @global \wpdb $wpdb
     * @param string $action
     * @return mixed
     */
    protected function handle_default( $action ){
        /** @var \wpdb $wpdb */
        global $wpdb;
        // Get common values
        $redirect_url = $this->session_get('redirect_to');
        $saved_state = $this->session_get('state');
        $code = $this->request('code');
        $state = $this->request('state');
        switch($action){
            case 'login': // Let user login
                try{
                    // Authenticate and get token
                    // Authenticate and get token
                    $token = $this->get_access_token($code, $state, $saved_state);
                    $profile = $this->get_user_profile($token);
                    $email = $this->get_user_email($token);
                    if( !$profile || !$email){
                        throw new \Exception($this->api_error_string());
                    }
                    // Check account existance
                    $user_id = $this->get_meta_owner($this->umeta_id, $profile->id);
                    if( !$user_id ){
                        // Test
                        $this->test_user_can_register();
                        //Not found, Create New User
                        require_once(ABSPATH . WPINC . '/registration.php');
                        // Check email
                        if( email_exists($email) ){
                            throw new \Exception($this->duplicate_account_string());
                        }
                        // Create user name
                        $user_name = $this->valid_username_from_mail($profile->login.'@github');
                        // Create user
                        $user_id = wp_create_user($user_name, wp_generate_password(), $email);
                        if(is_wp_error($user_id)){
                            throw new \Exception($this->registration_error_string());
                        }
                        // Update user meta
                        update_user_meta($user_id, $this->umeta_id, $profile->id);
                        update_user_meta($user_id, $this->umeta_login, $profile->login);
                        update_user_meta($user_id, 'nickname', $profile->name);
                        $wpdb->update(
                            $wpdb->users,
                            array(
                                'display_name' => $profile->name,
                                'user_url' => $profile->url,
                            ),
                            array('ID' => $user_id),
                            array('%s', '%s'),
                            array('%d')
                        );
                        $this->user_password_unknown($user_id);
                        $this->hook_connect($user_id, $profile, true);
                        $this->welcome($profile->login);
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
            case 'connect': // Connect account
                try{
                    // Authenticate and get token
                    $token = $this->get_access_token($code, $state, $saved_state);
                    $profile = $this->get_user_profile($token);
                    $mail = $this->get_user_email($token);
                    if( !$profile || !$mail){
                        throw new \Exception($this->api_error_string());
                    }
                    // Check if other user has these as meta_value
                    if( $this->get_meta_owner($this->umeta_id, $profile->id)
                        || ($mail_owner = email_exists($mail)) && get_current_user_id() != $mail_owner
                    ){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    // Now let's save userdata
                    update_user_meta(get_current_user_id(), $this->umeta_id, $profile->id);
                    update_user_meta(get_current_user_id(), $this->umeta_login, $profile->login);
                    // Fires hook
                    $this->hook_connect(get_current_user_id(), $profile);
                    // Save message
                    $this->welcome($profile->login);
                }catch(\Exception $e){
                    $this->auth_fail($e->getMessage());
                }
                // Connection finished. Let's redirect.
                if( !$redirect_url ){
                    $redirect_url = admin_url('profile.php');
                }
                // Apply filter
                $redirect_url = $this->filter_redirect($redirect_url, 'connect');
                wp_redirect($redirect_url);
                exit;
                break;
            default:
                // No action is set, error.
                break;
        }
    }


    /**
     * Get user profile
     *
     * @see http://developer.github.com/v3/users/
     * @param string $token
     * @return \stdClass JSON object
     * @throws \Exception
     */
    private function get_user_profile($token){
        return $this->api_request('user', $token, array(), 'GET');
	}

    /**
     * Return user's primary email
     *
     * @link http://developer.github.com/v3/users/emails/
     * @param string $token
     * @return string|false
     */
    private function get_user_email($token){
        $mails = (array) $this->api_request('user/emails', $token, array(), 'GET');
        if(!$mails){
            return false;
        }
        foreach( $mails as $mail ){
            if( $mail->primary ){
                return $mail->email;
            }
        }
        return false;
    }

    /**
     * Get access token
     *
     * @param string $code
     * @param string $state
     * @param string $saved_state
     * @return string
     * @throws \Exception
     */
    private function get_access_token($code, $state, $saved_state){
        if( !$code || !$state || !$saved_state || $state != $saved_state){
            throw new \Exception($this->api_error_string());
        }
        $response = $this->get_response('https://github.com/login/oauth/access_token', http_build_query(array(
            'client_id' => $this->github_client_id,
            'client_secret' => $this->github_client_secret,
            'code' => $code,
            'redirect_uri' => $this->get_redirect_endpoint(),
        )), 'POST', false, array('Accept: application/json'));
        if( !$response || !isset($response->access_token) ){
            throw new \Exception($this->api_error_string());
        }
        return $response->access_token;
    }

    /**
     * Make request
     *
     * @link http://developer.github.com/v3/ Github documentation
     * @param string $action
     * @param string $token
     * @param array $request
     * @param string $method
     * @param string $accept Default application/vnd.github.v3+json. See {@link http://developer.github.com/v3/media/}.
     * @return array|bool|null|\stdClass
     */
    private function api_request($action, $token, array $request, $method = 'POST', $accept = 'application/vnd.github.v3+json'){
        $endpoint = 'https://api.github.com/'.ltrim($action, '/');
        return $this->get_response($endpoint, http_build_query($request), $method, false, array(
            'Accept: '.$accept,
            'Authorization: Bearer '.$token,
            'User-Agent: '.home_url('/'),
        ));
    }

    /**
     * Detect if user is connected to this service
     *
     * @param int $user_id
     * @return bool
     */
    public function is_connected($user_id){
        return (boolean) get_user_meta($user_id, $this->umeta_id, true);
    }

    /**
     * Disconnect user from this service
     *
     * @param int $user_id
     * @return mixed
     */
    public function disconnect($user_id){
        delete_user_meta($user_id, $this->umeta_id);
        delete_user_meta($user_id, $this->umeta_login);
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
                $state = sha1(uniqid('github_'.$action, true));
                $this->session_write('state', $state);
                return 'https://github.com/login/oauth/authorize?'.http_build_query(array(
                    'client_id' => $this->github_client_id,
                    'redirect_uri' => $this->get_redirect_endpoint(),
                    'scope' => 'user,user:email',
                    'state' => $state,
                ));
                break;
            default:
                return false;
                break;
        }
    }
}
