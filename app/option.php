<?php

namespace Gianism;


class Option extends Singleton
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
        'fb_enabled' => 0,
        'fb_app_id' => '',
        'fb_app_secret' => '',
        'fb_fan_gate' => 0,
        'tw_enabled' => 0,
        "tw_screen_name" => "",
        "tw_consumer_key" => "",
        "tw_consumer_secret" => "",
        "tw_access_token" => "",
        "tw_access_token_secret" => "",
        "ggl_enabled" => 0,
        "ggl_consumer_key" => "",
        "ggl_consumer_secret" => "",
        "ggl_redirect_uri" => "",
        'yahoo_enabled' => 0,
        'yahoo_application_id' => '',
        'yahoo_consumer_secret' => '',
        "mixi_enabled" => 0,
        "mixi_consumer_key" => "",
        "mixi_consumer_secret" => "",
        "mixi_access_token" => "",
        "mixi_refresh_token" => "",
        'show_button_on_login' => true
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


    public function update(){
        $this->values = wp_parse_args(array(
            'fb_enabled' => ($this->post('fb_enabled') == 1) ? 1 : 0,
            'fb_app_id' => (string)$this->post('fb_app_id'),
            'fb_app_secret' => (string)$this->post('fb_app_secret'),
            'fb_fan_gate' => (int)$this->post('fb_fan_gate'),
            'tw_screen_name' => (string)$this->post('tw_screen_name'),
            'tw_enabled' => (string)($this->post('tw_enabled') == 1) ? 1 : 0,
            "tw_consumer_key" => (string)$this->post('tw_consumer_key'),
            "tw_consumer_secret" => (string)$this->post('tw_consumer_secret'),
            "tw_access_token" => (string)$this->post('tw_access_token'),
            "tw_access_token_secret" => (string)$this->post('tw_access_token_secret'),
            'ggl_enabled' => ($this->post('ggl_enabled') == 1) ? 1 : 0,
            "ggl_consumer_key" => (string)$this->post('ggl_consumer_key'),
            "ggl_consumer_secret" => (string)$this->post('ggl_consumer_secret'),
            "ggl_redirect_uri" => (string)$this->post('ggl_redirect_uri'),
            "yahoo_enabled" => ($this->post('yahoo_enabled') == 1) ? 1 : 0,
            "yahoo_application_id" => (string)$this->post('yahoo_application_id'),
            "yahoo_consumer_secret" => (string)$this->post('yahoo_consumer_secret'),
            "mixi_enabled" => ($this->post('mixi_enabled') == 1) ? 1 : 0,
            "mixi_consumer_key" => (string)$this->post('mixi_consumer_key'),
            "mixi_consumer_secret" => (string)$this->post('mixi_consumer_secret'),
            'show_button_on_login' => (boolean)$this->post('show_button_on_login'),
        ), $this->values);
        if(update_option($this->key, $this->values)){
            $this->add_message($this->_('Option updated.'));
            do_action(self::UPDATED_ACTION, $this->values);
        }else{
            $this->add_message($this->_('Option failed to update.'), true);
        }
    }
}