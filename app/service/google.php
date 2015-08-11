<?php

namespace Gianism\Service;
use Gianism\Cron\Daily;

/**
 * Google client
 *
 * @package Gianism\Service
 * @since 2.0.0
 * @author Takahashi Fumiki
 *
 * @property-read \Google_Client $api
 * @property-read \Google_Service_Plus $plus
 * @property-read \Google_Client $ga_client
 * @property-read \Google_Service_Analytics $ga
 * @property-read string $ga_token
 * @property-read array $ga_profile
 * @property-read array $ga_accounts
 * @property-read string $ga_table
 *
 */
class Google extends Common\Mail
{

    /**
     * URL prefix to prepend
     *
     * @var string
     */
    public $url_prefix = 'google-auth';

    /**
     * Verbose service name
     *
     * @var string
     */
    public $verbose_service_name = 'Google';

	/**
	 * @var string
	 */
	protected  $ggl_consumer_key = '';
	
	/**
	 * @var string
	 */
	protected  $ggl_consumer_secret = '';
	
	/**
	 * @var string
	 */
	public $umeta_account = '_wpg_google_account';
	
	/**
	 * @var string
	 */
	public $umeta_plus = "_wpg_google_plus_id";
	
	/**
     * Oauth client store
     *
	 * @var \Google_Client
	 */
	private $_api = null;

	/**
     * Plus client
     *
	 * @var \Google_Service_Plus
	 */
	private $_plus = null;

    /**
     * Analytics service
     *
     * @var \Google_Service_Analytics
     */
    private $_ga = null;

    /**
     * Analytics client
     *
     * @var \Google_Client
     */
    private $_ga_client = null;

    /**
     * Option to retrieve
     *
     * @var array
     */
    protected $option_keys = array("ggl_consumer_key", "ggl_consumer_secret");

    /**
     * Action name
     */
    const AJAX_ACTION = 'wpg_ga_account';

    /**
     * Create table action
     */
    const AJAX_TABLE = 'wpg_ga_table';

    /**
     * Cron checker
     */
    const AJAX_CRON = 'wpg_ga_cron';

    /**
     * Cron ready classes
     *
     * @var array
     */
    public $crons = array();

    /**
     * Ajax class names
     *
     * @var array
     */
    public $ajaxes = array();

    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct( array $argument = array() ) {
        parent::__construct($argument);
        if( $this->enabled ){
            add_action('wp_ajax_'.self::AJAX_ACTION, array($this, 'ga_ajax'));
            add_action('init', array($this, 'boot_auto_cron'));
            add_action('wp_ajax_'.self::AJAX_CRON, array($this, 'ga_cron'));
        }
    }

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
        $code = $this->request('code');
        switch($action){
            case 'login': // Let user login
                try{
                    // Authenticate and get token
                    $token = $this->api->authenticate($code);
                    $profile = $this->get_profile();
                    // Check email validity
                    if( !isset($profile['email']) || !is_email($profile['email'])){
                        throw new \Exception($this->mail_fail_string());
                    }
                    $email = $profile['email'];
                    $plus_id = isset($profile['id']) ? $profile['id'] : 0;
                    $user_id = $this->get_meta_owner($this->umeta_account, $email);
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
                        $user_name = $this->valid_username_from_mail($email);
                        // Create user
                        $user_id = wp_create_user($user_name, wp_generate_password(), $email);
                        if(is_wp_error($user_id)){
                            throw new \Exception($this->registration_error_string());
                        }
                        // Update user meta
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
                        $this->user_password_unknown($user_id);
                        $this->hook_connect($user_id, $profile, true);
                        $this->welcome($profile['name']);
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
                    $token = $this->api->authenticate($code);
                    $profile = $this->get_profile();
                    // Check email validity
                    if( !isset($profile['email']) || !is_email($profile['email'])){
                        throw new \Exception($this->mail_fail_string());
                    }
                    // Check if other user has these as meta_value
                    $email = $profile['email'];
                    $email_owner = $this->get_meta_owner($this->umeta_account, $email);
                    if( $email_owner && $email_owner != get_current_user_id() ){
                        throw new \Exception($this->duplicate_account_string());
                    }
                    // Now let's save userdata
                    update_user_meta(get_current_user_id(), $this->umeta_account, $email);
                    if( isset($profile['id']) && $profile['id']){
                        update_user_meta(get_current_user_id(), $this->umeta_plus, $profile['id']);
                    }
                    // Fires hook
                    $this->hook_connect(get_current_user_id(), $profile);
                    // Save message
                    $this->welcome($profile['name']);
                }catch(\Exception $e){
                    $this->auth_fail($e->getMessage());
                }
                // Connection finished. Let's redirect.
                if( !$redirect_url ){
                    $redirect_url = admin_url('profile.php');
                }
                // Applyfilter
                $redirect_url = $this->filter_redirect($redirect_url, 'connect');
                wp_redirect($redirect_url);
                exit;
                break;
            case 'analytics_token':
                try{
                    if( $code && $this->ga_client->authenticate($code) ){
                        // O.K. save access token
                        $token = $this->ga_client->getAccessToken();
                        $this->save_token($this->ga_client->getAccessToken());
                        // Add message
                        $this->add_message($this->_('Now you got token!'));
                        // Redirect
                        wp_redirect($redirect_url);
                        exit;
                    }else{
                        throw new \Exception($this->_('Failed to authenticate with Google API.'));
                    }
                }catch ( \Exception $e ){
                    $this->wp_die($e->getMessage());
                }
                break;
            default:
                // No action is set, error.
                break;
        }
    }

    /**
     * Redirect to Google analytics
     *
     * @param \WP_Query $wp_query
     */
    public function handle_analytics( \WP_Query $wp_query ){
        try{
            if( $this->get('delete') ){
                $this->save_token('', true);
                $this->add_message($this->_('Token deleted.'));
                wp_redirect($this->get('redirect_to'));
                exit;
            }else{
                $this->session_write('action', 'analytics_token');
                $this->session_write('redirect_to', $this->get('redirect_to'));
                $this->ga_client->setApprovalPrompt('force');
                $url = $this->ga_client->createAuthUrl();
                wp_redirect($url);
                exit;
            }
        }catch ( \Exception $e ){
            $this->add_message($e->getMessage(), true);
            wp_redirect($this->get('redirect_to'));
            exit;
        }
    }

    /**
     * Save token
     *
     * @param \WP_Query $wp_query
     */
    public function handle_save_analytics( \WP_Query $wp_query ){
        try{
            if( !current_user_can('manage_options') ){
                throw new \Exception($this->_('You have no permission.'), 403);
            }
            if( update_option('wpg_analytics_profile', array(
                'account' => $this->post('ga-account'),
                'profile' => $this->post('ga-profile'),
                'view' => $this->post('ga-view'),
            )) ){
                $this->add_message($this->_('Options updated.'));
                wp_redirect($this->get('redirect_to'));
                exit;
            }else{
                throw new \Exception($this->_('Nothing changed.'), 500);
            }
        }catch ( \Exception $e ){
            $this->add_message($e->getMessage(), true);
            wp_redirect($this->get('redirect_to'));
            exit;
        }
    }

    /**
     * Create table
     *
     * @param \WP_Query $wp_query
     */
    public function handle_create_table( \WP_Query $wp_query ){
        try{
            if( !current_user_can('manage_options') ){
                throw new \Exception($this->_('You have no permission.'), 403);
            }
            if( $this->table_exists() ){
                throw new \Exception($this->_('Table is already exists.'), 500);
            }
            // O.K. Let's create table
            global $wpdb;
            $query = <<<SQL
CREATE TABLE `{$this->ga_table}` (
    ID BIGINT NOT NULL AUTO_INCREMENT,
    category VARCHAR(64) NOT NULL,
    object_id BIGINT UNSIGNED NOT NULL,
    object_value BIGINT NOT NULL,
    calc_date DATE NOT NULL,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ID),
    INDEX category_index (category, calc_date),
    INDEX object_index (category, object_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8
SQL;
            if( !function_exists('dbDelta') ){
                require_once ABSPATH."wp-admin/includes/upgrade.php";
            }
            dbDelta($query);
            if( !$this->table_exists() ){
                throw new \Exception($this->_('Sorry, but failed to create table.'), 500);
            }
            $this->add_message($this->_('Table created.'));
            wp_redirect($this->get('redirect_to'));
            exit;
        }catch (\Exception $e){
            $this->add_message($e->getMessage(), true);
            wp_redirect($this->get('redirect_to'));
            exit;
        }
    }

	
	/**
	 * Returns Profile
     *
	 * @return \Google_Service_Oauth2_Userinfo
	 */
	private function get_profile(){
		$oauth = new \Google_Service_Oauth2($this->api);
        return $oauth->userinfo->get();
	}

    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        switch($name){
            case 'api':
                if(!$this->_api){
                    $this->_api = new \Google_Client();
                    $this->_api->setClientId($this->ggl_consumer_key);
                    $this->_api->setClientSecret($this->ggl_consumer_secret);
                    $this->_api->setRedirectUri($this->get_redirect_endpoint());
                    $this->_api->setApplicationName(get_bloginfo('name'));
                    $this->_api->setApprovalPrompt('auto');
                    $this->_api->setAccessType('online');
                    $this->_api->setScopes(array(
                        'https://www.googleapis.com/auth/userinfo.profile',
                        'https://www.googleapis.com/auth/userinfo.email',
                        'https://www.googleapis.com/auth/plus.me'
                    ));
                }
                return $this->_api;
                break;
            case 'plus':
                if(!$this->_plus){
                    $this->_plus = new \Google_Service_Plus($this->api);
                }
                return $this->_plus;
                break;
            case 'ga_client':
                // Init library
                if( is_null($this->_ga_client) ){
                    $this->_ga_client = new \Google_Client();
                    $this->_ga_client->setClientId($this->ggl_consumer_key);
                    $this->_ga_client->setClientSecret($this->ggl_consumer_secret);
                    $this->_ga_client->setRedirectUri($this->get_redirect_endpoint());
                    $this->_ga_client->setScopes(array(
                        'https://www.googleapis.com/auth/analytics.readonly'
                    ));
                    $this->_ga_client->setAccessType('offline');
                }
                return $this->_ga_client;
                break;
            case 'ga_token':
                return get_option('wpg_analytics_token', '');
                break;
            case 'ga_profile':
                return get_option('wpg_analytics_profile', array(
                    'account' => 0,
                    'profile' => 0,
                    'view' => 0,
                ));
                break;
            case 'ga':
                if( $this->ga_token && is_null($this->_ga) ){
                    $this->ga_client->setAccessToken($this->ga_token);
                    if( $this->ga_client->isAccessTokenExpired() ){
                        // Refresh token if expired.
                        $token = json_decode($this->ga_token);
                        $this->ga_client->refreshToken($token->refresh_token);
                        $this->save_token($this->ga_client->getAccessToken());
                    }
                    $this->_ga = new \Google_Service_Analytics($this->ga_client);
                }
                return $this->_ga;
                break;
            case 'ga_accounts':
                static $ga_accounts = null;
                if( !is_null($ga_accounts) ){
                    return $ga_accounts;
                }
                $ga_accounts = array();
                if( $this->ga_token ){
                    try{
                        $accounts = $this->ga->management_accounts->listManagementAccounts();
                        if( count($accounts->getItems()) > 0 ){
                            $ga_accounts = $accounts;
                        }
                    }catch (\Exception $e){
                        // Do nothing.
                        error_log($e->getMessage(), $e->getCode());
                    }
                }
                return $ga_accounts;
                break;
            case 'ga_table':
                global $wpdb;
                return $wpdb->prefix.'wpg_ga_ranking';
                break;
            default:
                return parent::__get($name);
                break;
        }
    }

    /**
     * Save token
     *
     * @param string $token
     * @param bool $delete Default false.
     * @return bool
     */
    public function save_token($token, $delete = false){
        if( $delete ){
            return delete_option('wpg_analytics_token');
        }else{
            return update_option('wpg_analytics_token', $token);
        }
    }

    /**
     * Save profile
     *
     * @param string $profile
     * @return bool
     */
    public function save_profile($profile){
        return update_option('wpg_analytics_profile', $profile);
    }

    /**
     * Ajax action
     */
    public function ga_ajax(){
        header('Content-Type', 'application/json');
        try{
            // Check nonce
            if( !wp_verify_nonce($this->get('nonce'), self::AJAX_ACTION) ||  !current_user_can('manage_options') ){
                throw new \Exception($this->_('You have no permission.'), 403);
            }
            // Check data to retrieve
            $result = null;
            switch( $target = $this->get('target') ){
                case 'account':
                    $properties = $this->ga
                        ->management_webproperties
                        ->listManagementWebproperties($this->get('account_id'));
                    $json = array(
                        'success' => true,
                        'items' => $properties->getItems(),
                    );
                    break;
                case 'profile':
                    $views = $this->ga
                        ->management_profiles
                        ->listManagementProfiles($this->get('account_id'), $this->get('profile_id'));
                    $json = array(
                        'success' => true,
                        'items' => $views->getItems(),
                    );
                    break;
                default:
                    throw new \Exception($this->_('Invalid action.'), 500);
                    break;
            }
        }catch (\Exception $e){
            $json = array(
                'success' => false,
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
            );
        }
        echo json_encode($json);
        exit;
    }

    /**
     * Get redirect url to get analytics token
     *
     * @param string $redirect
     * @param bool $delete Default false
     * @return string
     */
    public function token_url($redirect, $delete = false){
        return $this->get_redirect_endpoint('analytics', $this->service_name.'_analytics', array(
            'redirect_to' => $redirect,
            'delete' => $delete,
        ));
    }

    /**
     * Get redirect url to save analytics token
     *
     * @param string $redirect
     * @return string
     */
    public function token_save_url($redirect){
        return $this->get_redirect_endpoint('save-analytics', '', array(
            'redirect_to' => $redirect,
        ));
    }


    /**
     * Table create
     *
     * @param string $redirect
     * @return string
     */
    public function table_create_url($redirect){
        return $this->get_redirect_endpoint('create-table', '', array(
            'redirect_to' => $redirect,
        ));
    }

    /**
     * Detect if table exists
     *
     * @return bool
     */
    public function table_exists(){
        global $wpdb;
        $query = "SHOW TABLES LIKE %s";
        return (bool)$wpdb->get_row($wpdb->prepare($query, $this->ga_table));
    }

    /**
     * Detect if user is connected to this service
     *
     * @param int $user_id
     * @return bool
     */
    public function is_connected($user_id){
        return (boolean) get_user_meta($user_id, $this->umeta_account, true);
    }

    /**
     * Register cron automatically
     */
    public function boot_auto_cron(){
        $template_dir = get_template_directory().'/app/gianism';
        $stylesheet_dir = get_stylesheet_directory().'/app/gianism';
        $scan = function($dir){
            $files = array();
            if( is_dir($dir) ){
                foreach(scandir($dir) as $file){
                    if( preg_match('/\.php/', $file) ){
                        $class_name = str_replace('.php', '', $file);
                        $files[$class_name] = $dir.'/'.$file;
                    }
                }
            }
            return $files;
        };
        $classes = $scan($template_dir);
        if( $template_dir != $stylesheet_dir ){
            $classes = array_merge($classes, $scan($stylesheet_dir));
        }
        if( !empty($classes) ){
            $ajax_classes = array();
            foreach($classes as $class_name => $file){
	            if( !file_exists($file) ){
		            continue;
	            }
                require $file;
                if( class_exists($class_name) ){
                    $refl = new \ReflectionClass($class_name);
                    if( $refl->isAbstract() ){
                        continue;
                    }
                    if( $refl->isSubclassOf('Gianism\\Cron\\Daily') ){
                        /** @var Daily $class_name */
                        $class_name::get_instance();
                        $this->crons[] = $class_name;
                    }elseif( $refl->isSubclassOf('Gianism\\Api\\Ga') ){
                        $ajax_classes[] = $class_name;
                    }
                }
            }
            if( !empty($ajax_classes) ){
                $this->ajaxes = $ajax_classes;
                add_action('admin_init', function() use($ajax_classes){
                    if( defined('DOING_AJAX') && DOING_AJAX ){
                        foreach( $ajax_classes as $ajax_class){
                            $ajax_class::get_instance();
                        }
                    }
                });
            }
        }
    }

    /**
     * Google analytics cron check
     */
    public function ga_cron(){
        header('Content-Type', 'application/json');
        try{
            if( !current_user_can('manage_options') ){
                throw new \Exception($this->_('You have no permisson.'), 403);
            }
            if( !wp_verify_nonce($this->post('_wpnonce'), self::AJAX_CRON) || !isset($this->crons[$this->post('cron')]) ){
                throw new \Exception($this->_('Such action does not exist.'), 403);
            }
            $class_name = $this->crons[$this->post('cron')];
            /** @var Daily $instance */
            $instance = $class_name::get_instance();
            $json = array(
                'success' => true,
                'items' => $instance->get_results(),
            );
        }catch (\Exception $e){
            $json = array(
                'success' => false,
                'error_code' => $e->getCode(),
                'message' => $e->getMessage(),
            );
        }
        echo json_encode($json);
        exit;
    }

    /**
     * Disconnect user from this service
     *
     * @param int $user_id
     * @return mixed
     */
    public function disconnect($user_id){
        delete_user_meta($user_id, $this->umeta_account);
        delete_user_meta($user_id, $this->umeta_plus);
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
                return $this->api->createAuthUrl();
                break;
            default:
                return false;
                break;
        }
    }
}
