<?php

namespace Gianism;


/**
 * Login screen controller
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Login extends Pattern\Singleton
{

    /**
     * Hook name
     */
    const LOGIN_FORM_ACTION = 'gianism_login_form';

    /**
     * Hook name
     */
    const BEFORE_LOGIN_FORM = 'gianism_before_login_form';

    /**
     * Hook name
     */
    const AFTER_LOGIN_FORM = 'gianism_after_login_form';

    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct( array $argument = array() ){
        /** @var \Gianism\Option $option */
        $option = Option::get_instance();
        if( $option->is_enabled() ){
            // Show button?
            if( $option->show_button_on_login('login') ){
                add_action('login_form', array($this, 'login_form'));
            }
            if( $option->show_button_on_login('register') ){
                add_action('register_form', array($this, 'register_form'));
            }
        }
    }

    /**
     * Show Login Form
     *
     * @param string $before Default '<div id="wpg-login">'.
     * @param string $after Default '</div>'.
     * @param bool $register Is register form.
     * @param string $redirect_to Redirect URL. Default empty string.
     * @return void
     */
	public function login_form($before = '', $after = '', $register = false, $redirect_to = ''){
        if(empty($before)){
            /** @var \Gianism\Option $option */
            $option = Option::get_instance();
            $class_name = array();
            if($register){
                $class_name[] = 'regsiter';
            }
            if($option->button_type){
                $class_name[] = 'large';
            }
            $class_name = empty($class_name) ? '' : sprintf(' class="%s"', implode(' ', $class_name));
            $before = sprintf('<div id="wpg-login"%s>', $class_name);
        }
        if( empty($after)){
            $after = '</div>';
        }
        echo $before;
        /**
         * gianism_before_login_form
         *
         * @param bool $register Is register form
         */
        do_action(self::BEFORE_LOGIN_FORM, $register);
        /**
         * gianism_login_form
         *
         * Display login buttons
         *
         * @param bool $register Is register form
         * @param string $redirect_to Redirect URL after login
         */
        do_action(self::LOGIN_FORM_ACTION, $register, $redirect_to);
        /**
         * gianism_after_login_form
         *
         * @param bool $register Is register form
         */
        do_action(self::AFTER_LOGIN_FORM, $register);
        echo $after;
    }

    /**
     * Show buttons on register form.
     *
     * @return void
     */
    public function register_form(){
        $this->login_form('', '', true);
    }

} 