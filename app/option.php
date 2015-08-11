<?php

namespace Gianism;

/**
 * Option interface
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 *
 * @property-read bool $force_register
 * @property-read bool $fb_enabled
 * @property-read bool $facebook_enabled
 * @property-read string $fb_app_id
 * @property-read string $fb_app_secret
 * @property-read int $fb_fan_gate
 * @property-read bool $fb_use_api
 * @property-read bool $tw_enabled
 * @property-read bool $twitter_enabled
 * @property-read string $tw_screen_name
 * @property-read string $tw_consumer_key
 * @property-read string $tw_consumer_secret
 * @property-read string $tw_access_token
 * @property-read string $tw_access_token_secret
 * @property-read bool $tw_use_cron
 * @property-read bool $ggl_enabled
 * @property-read bool $google_enabled
 * @property-read string $ggl_consumer_key
 * @property-read string $ggl_consumer_secret
 * @property-read bool $yahoo_enabled
 * @property-read string $yahoo_application_id
 * @property-read string $yahoo_consumer_secret
 * @property-read bool $mixi_enabled
 * @property-read string $mixi_consumer_key
 * @property-read string $mixi_consumer_secret
 * @property-read string $mixi_access_token
 * @property-read string $mixi_refresh_token
 * @property-read bool $amazon_enabled
 * @property-read string $amazon_client_id
 * @property-read string $amazon_client_secret
 * @property-read bool $github_enabled
 * @property-read string $github_client_id
 * @property-read string $github_client_secret
 * @property-read bool $show_button_on_login
 * @property-read int $button_type
 */
class Option extends Pattern\Singleton
{

    /**
     * @const UPDATED_ACTION Action name which fires on updating option
     */
    const UPDATED_ACTION = 'gianism_option_updated';

    /**
     * オプションのキー名
     *
     * @var string
     */
    protected $key = '';

    /**
     * オプション
     *
     * @var array
     */
    public $values = array();

    /**
     * オプション初期値
     *
     * @var array
     */
    protected $default_option = array(
        'show_button_on_login' => true,
        'button_type' => 0,
        'force_register' => true,
	    'do_cron' => false,
        'fb_enabled' => 0,
        'fb_app_id' => '',
        'fb_app_secret' => '',
        'fb_fan_gate' => 0,
	    'fb_use_api' => false,
        'tw_enabled' => 0,
        "tw_screen_name" => "",
        "tw_consumer_key" => "",
        "tw_consumer_secret" => "",
        "tw_access_token" => "",
        "tw_access_token_secret" => "",
	    'tw_use_cron' => false,
        "ggl_enabled" => 0,
        "ggl_consumer_key" => "",
        "ggl_consumer_secret" => "",
        'yahoo_enabled' => 0,
        'yahoo_application_id' => '',
        'yahoo_consumer_secret' => '',
        "mixi_enabled" => 0,
        "mixi_consumer_key" => "",
        "mixi_consumer_secret" => "",
        "mixi_access_token" => "",
        "mixi_refresh_token" => "",
        'amazon_enabled' => 0,
        'amazon_client_id' => '',
        'amazon_client_secret' => '',
        'github_enabled' => 0,
        'github_client_id' => '',
        'github_client_secret' => '',
    );


    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        $this->key = $this->name.'_option';
        $saved_option = get_option($this->key);
        foreach($this->default_option as $key => $value){
            if(!isset($saved_option[$key])){
                $this->values[$key] = $value;
            }else{
                $this->values[$key] = $saved_option[$key];
            }
        }
    }

    /**
     * Save options with post data
     */
    public function update(){
        $this->values = wp_parse_args(array(
            'fb_enabled' => ($this->post('fb_enabled') == 1) ? 1 : 0,
            'fb_app_id' => (string)$this->post('fb_app_id'),
            'fb_app_secret' => (string)$this->post('fb_app_secret'),
            'fb_fan_gate' => (int)$this->post('fb_fan_gate'),
	        'fb_use_api' => (bool)$this->post('fb_use_api'),
            'tw_screen_name' => (string)$this->post('tw_screen_name'),
            'tw_enabled' => (string)($this->post('tw_enabled') == 1) ? 1 : 0,
            "tw_consumer_key" => (string)$this->post('tw_consumer_key'),
            "tw_consumer_secret" => (string)$this->post('tw_consumer_secret'),
            "tw_access_token" => (string)$this->post('tw_access_token'),
            "tw_access_token_secret" => (string)$this->post('tw_access_token_secret'),
	        'tw_use_cron' => (bool)$this->post('tw_use_cron'),
            'ggl_enabled' => ($this->post('ggl_enabled') == 1) ? 1 : 0,
            "ggl_consumer_key" => (string)$this->post('ggl_consumer_key'),
            "ggl_consumer_secret" => (string)$this->post('ggl_consumer_secret'),
            "yahoo_enabled" => ($this->post('yahoo_enabled') == 1) ? 1 : 0,
            "yahoo_application_id" => (string)$this->post('yahoo_application_id'),
            "yahoo_consumer_secret" => (string)$this->post('yahoo_consumer_secret'),
            "mixi_enabled" => ($this->post('mixi_enabled') == 1) ? 1 : 0,
            "mixi_consumer_key" => (string)$this->post('mixi_consumer_key'),
            "mixi_consumer_secret" => (string)$this->post('mixi_consumer_secret'),
            "amazon_enabled" => ($this->post('amazon_enabled') == 1) ? 1 : 0,
            "amazon_client_id" => (string)$this->post('amazon_client_id'),
            "amazon_client_secret" => (string)$this->post('amazon_client_secret'),
            "github_enabled" => ($this->post('github_enabled') == 1) ? 1 : 0,
            'github_client_id' => (string)$this->post('github_client_id'),
            'github_client_secret' => (string)$this->post('github_client_secret'),
            'show_button_on_login' => (boolean)$this->post('show_button_on_login'),
            'button_type' => (int)$this->post('button_type'),
            'force_register' => ($this->post('force_register') == 1) ? true : false,
        ), $this->values);
        if( update_option($this->key, $this->values) ){
            $this->add_message($this->_('Option updated.'));
            do_action(self::UPDATED_ACTION, $this->values);
        }else{
            $this->add_message($this->_('Option failed to update.'), true);
        }
    }

    /**
     * Partially update
     *
     * @param array $options
     * @return bool
     */
    public function partial_update( array $options ){
        $changed = false;
        foreach($options as $key => $value){
            if( array_key_exists($key, $this->default_option) ){
                $this->values[$key] = $value;
                $changed = true;
            }
        }
        if( $changed ){
            $result = update_option($this->key, $this->values);
            if($result){
                do_action(self::UPDATED_ACTION, $this->values);
            }
            return $result;
        }
        return false;
    }

    /**
     * Detect if show login buttons
     *
     * @param string $context
     * @return mixed|void
     */
    public function show_button_on_login( $context = 'login' ){
        /**
         * Display Case-by-case filter
         *
         * @param bool $display Whether to display
         * @param string $context 'login', 'register', etc.
         */
        return apply_filters('gianism_show_button_on_login', $this->show_button_on_login, $context);
    }

    /**
     * Return button types
     *
     * @return array
     */
    public function button_types(){
        return array(
            $this->_('Medium'),
            $this->_('Large'),
        );
    }

    /**
     * Returns if option is wrong
     *
     * @param string $name
     * @return bool
     */
    public function has_invalid_option($name){
        switch($name){
            case 'google_redirect':
                $option = get_option($this->key, array());
                return isset($saved_option['ggl_redirect_uri']) && !empty($saved_option['ggl_redirect_uri']);
                break;
            default:
                return false;
                break;
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
            case 'facebook_enabled':
                return $this->fb_enabled;
                break;
            case 'twitter_enabled':
                return $this->tw_enabled;
                break;
            case 'google_enabled':
                return $this->ggl_enabled;
                break;
            default:
                if( isset($this->values[$name]) ){
                    return $this->values[$name];
                }else{
                    return parent::__get($name);
                }
                break;
        }
    }
}