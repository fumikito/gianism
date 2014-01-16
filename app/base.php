<?php

namespace Gianism;


/**
 * Base class
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0
 * @property-read string $dir
 * @property-read string $url
 */
abstract class Base {

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
    protected $version = GIANISM_VERSION;


    /**
     * i18n Domain
     *
     * @var string
     */
    protected $domain = GIANISM_DOMAIN;

    /**
     * __のエイリアス
     *
     * @param string $string
     * @return string
     */
    public function _($string){
        return __($string, $this->domain);
    }

    /**
     * _eのエイリアス
     *
     * @param string $string
     */
    public function e($string){
        _e($string, $this->domain);
    }

    /**
     * $_GETに値が設定されていたら返す
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
     * $_POSTに値が設定されていたら返す
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
     * $_REQUESTに値が設定されていたら返す
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
     * nonce用に接頭辞をつけて返す
     *
     * @param string $action
     * @return string
     */
    public function nonce_action($action){
        return $this->name."_".$action;
    }

    /**
     * wp_nonce_fieldのエイリアス
     *
     * @param string $action
     * @param bool $referrer Default false.
     */
    public function nonce_field($action, $referrer = false){
        wp_nonce_field($this->nonce_action($action), "_{$this->name}_nonce", $referrer);
    }

    /**
     * nonceが合っているか確かめる
     *
     * @param string $action
     * @param string $referrer
     * @return boolean
     */
    public function verify_nonce($action, $referrer = ''){
        if($referrer){
            return ( (wp_verify_nonce($this->request("_{$this->name}_nonce"), $this->nonce_action($action)) && $referrer == $this->request("_wp_http_referer")) );
        }else{
            return wp_verify_nonce($this->request("_{$this->name}_nonce"), $this->nonce_action($action));
        }
    }

    /**
     * Add message to show
     *
     * @param string $string
     * @param bool $error
     */
    protected function add_message($string, $error = false){
        $key = $error ? 'error' : 'updated';
        if( session_id() ){
            if( !isset($_SESSION['gianism']) ){
                $_SESSION['gianism'] = array();
            }
            if( !isset($_SESSION['gianism'][$key]) ){
                $_SESSION['gianism'][$key] = array();
            }
            $_SESSION['gianism'][$key][] = $string;
        }
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
                return plugin_dir_url(dirname(__FILE__));
                break;
            case 'dir':
                return plugin_dir_path(dirname(__FILE__));
                break;
        }
    }
}
