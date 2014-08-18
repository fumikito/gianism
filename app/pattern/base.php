<?php

namespace Gianism\Pattern;

use Gianism\Option;

/**
 * Base class
 *
 * @package Gianism\Pattern
 * @author Takahashi Fumiki
 * @since 2.0
 * @property-read string $dir
 * @property-read string $url
 * @property-read array $all_services
 * @property-read string $nonce_key_name
 *
 */
abstract class Base
{

    /**
     * Name
     *
     * Used for option key, nonce, etc.
     *
     * @var string
     */
    protected $name = 'wp_gianism';


    /**
     * Version number
     *
     * @var string
     */
    protected $version = \GIANISM_VERSION;


    /**
     * i18n Domain
     *
     * @var string
     */
    protected $domain = \GIANISM_DOMAIN;

    /**
     * Message post type
     *
     * @var string
     */
    public $message_key_name = '_gianism_message';

    /**
     * All services
     *
     * @var array
     */
    private $_all_services = array();

    /**
     * Alias of __
     *
     * @param string $string
     * @return string
     */
    public function _($string){
        return __($string, $this->domain);
    }

    /**
     * Alias of _e
     *
     * @param string $string
     */
    public function e($string){
        _e($string, $this->domain);
    }

    /**
     * Return $_GET
     *
     * @param string $key
     * @return mixed
     */
    public function get($key){
        if(isset($_GET[$key])){
            return $_GET[$key];
        }else{
            return null;
        }
    }

    /**
     * Return $_POST
     *
     * @param string $key
     * @return mixed
     */
    public function post($key){
        if(isset($_POST[$key])){
            return $_POST[$key];
        }else{
            return null;
        }
    }

    /**
     * return $_REQUEST
     *
     * @param string $key
     * @return mixed
     */
    public function request($key){
        if(isset($_REQUEST[$key])){
            return $_REQUEST[$key];
        }else{
            return null;
        }
    }


    /**
     * Return nonce name with prefix
     *
     * @param string $action
     * @return string
     */
    public function nonce_action($action){
        return $this->name."_".$action;
    }

    /**
     *
     *
     * @param string $action
     * @return string
     */
    public function nonce_create($action){
        return wp_create_nonce($this->nonce_action($action));
    }

    /**
     * Alias of wp_nonce_field
     *
     * @param string $action
     * @param bool $referrer Default false.
     */
    public function nonce_field($action, $referrer = false){
        wp_nonce_field($this->nonce_action($action), $this->nonce_key_name, $referrer);
    }

    /**
     * Check nonce
     *
     * @param string $action
     * @param string $referrer
     * @return boolean
     */
    public function verify_nonce($action, $referrer = ''){
        if($referrer){
            return ( (wp_verify_nonce($this->request($this->nonce_key_name), $this->nonce_action($action)) && $referrer == $this->request("_wp_http_referer")) );
        }else{
            return wp_verify_nonce($this->request($this->nonce_key_name), $this->nonce_action($action));
        }
    }

    /**
     * Returns if login is forced to use SSL.
     *
     * To override it, use filter `gianism_force_ssl_login`
     *
     * @return boolean
     */
    public function is_ssl_required(){
        $is_ssl = (defined('FORCE_SSL_LOGIN') && FORCE_SSL_LOGIN) || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN);
        /**
         * Login action must be under SSL or not.
         *
         * @param bool $is_ssl
         */
        return apply_filters('gianism_force_ssl_login', $is_ssl);
    }

    /**
     * Determine if service is enabled.
     *
     * @param string $service If not specified, one of the services are enabled, return true.
     * @return bool
     */
    public function is_enabled($service = ''){
        /** @var \Gianism\Option $option */
        $option = Option::get_instance();
        if( !empty($service) ){
            // Service is specified, use it
            switch($service){ // Backward compatibility
                case 'facebook':
                    $service = 'fb';
                    break;
                case 'twitter':
                    $service = 'tw';
                    break;
                case 'google':
                    $service = 'ggl';
                    break;
            }
            $key = $service.'_enabled';
            return isset($option->values[$key]) && (bool)$option->values[$key];
        }else{
            foreach($option->values as $key => $value){
                if( false !== strpos($key, '_enabled') && $value ){
                    return true;
                    break;
                }
            }
            return false;
        }
    }

    /**
     * Add message to show
     *
     * @param string $string
     * @param bool $error
     */
    protected function add_message($string, $error = false){
        $key = 'gianism_'.($error ? 'error' : 'updated');
        if( isset($_COOKIE[$key]) && !empty($_COOKIE[$key]) ){
            $messages = json_decode(stripcslashes($_COOKIE[$key]), true);
            $messages[] = $string;
        }else{
            $messages = array($string);
        }
        setcookie($key, json_encode($messages), current_time('timestamp') + 180, '/');
    }

    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        switch($name){
            case 'url':
                return plugin_dir_url(dirname(dirname(__FILE__)));
                break;
            case 'dir':
                return plugin_dir_path(dirname(dirname(__FILE__)));
                break;
            case 'all_services':
                if( empty($this->_all_services) ){
                    foreach(scandir($this->dir.'app'.DIRECTORY_SEPARATOR.'service') as $file){
                        if( false !== strpos($file, '.php') ){
                            $this->_all_services[] = str_replace('.php', '', $file);
                        }
                    }
                }
                return $this->_all_services;
                break;
            case 'nonce_key_name':
                return "_{$this->name}_nonce";
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Retrieve user meta's owner ID
     *
     * @global \wpdb $wpdb
     * @param string $key
     * @param string $value
     * @return int User ID. If not exists, return 0
     */
    public function get_meta_owner($key, $value){
        /** @var \wpdb $wpdb */
        global $wpdb;
        $query = <<<EOS
            SELECT user_id FROM {$wpdb->usermeta}
            WHERE meta_key = %s AND meta_value = %s
EOS;
        return (int)$wpdb->get_var($wpdb->prepare($query, $key, $value));
    }

    /**
     * Get service instance
     *
     * @param string $service
     * @return null|\Gianism\Service\Common\Mail
     */
    public function get_service_instance($service){
        $service = strtolower($service);
        $class_name = 'Gianism\\Service\\'.ucfirst($service);
        if( class_exists($class_name) ){
            $reflection = new \ReflectionMethod($class_name, 'get_instance');
            if( $reflection->isPublic() && $reflection->isStatic() ){
                return $class_name::get_instance();
            }
        }
        return null;
    }

    /**
     * Short hand for wp_die
     *
     * @param string $message
     * @param int $status_code
     * @param bool $return
     */
    protected function wp_die($message, $status_code = 500, $return = true){
        wp_die($message, get_status_header_desc($status_code).' | '.get_bloginfo('name'), array(
            'response' => intval($status_code),
            'back_link' => (boolean) $return,
        ));
    }

    /**
     * Write session
     *
     * @param string $key
     * @param mixed $value
     */
    protected function session_write($key, $value){
        if( isset($_SESSION[$this->name]) ){
            $_SESSION[$this->name][$key] = $value;
        }
    }

    /**
     * Get session
     *
     * After get, session key will be deleted.
     *
     * @param string $key
     * @return bool
     */
    protected function session_get($key){
        if( isset($_SESSION[$this->name][$key]) ){
            $value = $_SESSION[$this->name][$key];
            $this->session_delete($key);
            return $value;
        }
        return false;
    }

    /**
     * Delete session
     *
     * @param string $key
     */
    protected function session_delete($key){
        if( isset($_SESSION[$this->name][$key]) ){
            unset($_SESSION[$this->name][$key]);
        }
    }

    /**
     * Detect if user can register
     *
     * @return bool
     */
    public function user_can_register(){
        /** @var \Gianism\Option $option */
        $option = Option::get_instance();
        return $option->force_register ?: (bool)get_option('users_can_register');
    }
}
