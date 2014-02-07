<?php

namespace Gianism;


/**
 * Main controller of Gianism
 *
 * This controller initialize Gianism.
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0.0
 */
class Bootstrap extends Pattern\Singleton
{

    /**
     * Rewrite rules.
     *
     * Initialized on constructor
     *
     * @var array
     */
    private $rewrites = array();

    /**
     * URL prefixes
     *
     * @var array
     */
    private $prefixes = array();

    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        // Start session
        if( !session_id() ){
            session_start();
        }
        if( session_id() ){
            if( ! isset($_SESSION[$this->name]) ){
                $_SESSION[$this->name] = array();
            }
        }else{
            add_action('admin_notices', array($this, 'notice_about_session'));
        }

        // Set option
        // Here, option is intiialized
        /** @var Option $option */
        $option = Option::get_instance();

        // Admin Message action
        add_action('admin_notices', array($this, 'flush_message'));

        // Register assets
        add_action('init', array($this, 'register_assets'));

        // Admin page
        add_action('admin_menu', function(){
            Admin::get_instance();
        });

        // Add message notice
        Message::get_instance();

        // Dequeue WPMP's CSS
        add_action('admin_enqueue_scripts', function(){
            wp_dequeue_style('wpmp-admin-custom');
        }, 1000);

        // If enabled, create interface and rewrite rules.
        if( $option->is_enabled() ){
            // Add query vars
            add_filter('query_vars', function($vars){
                return array_merge($vars, array('gianism_service', 'gianism_action'));
            });

            // Init profile manager
            Profile::get_instance();

            // Init login manager
            Login::get_instance();

            // Instanciate everything
            // and build rewrite rules
            // Prefixes
            foreach($this->all_services as $service){
                $instance = $this->get_service_instance($service);
                if( $instance && $instance->enabled ){
                    $this->prefixes[$service] = $instance->url_prefix;
                }
            }
            if(!empty($this->prefixes)){
                $preg = implode('|', $this->prefixes);
                $this->rewrites = array(
                    "({$preg})/?$" => 'index.php?gianism_service=$matches[1]&gianism_action=default',
                    "({$preg})/([^/]+)/?$" => 'index.php?gianism_service=$matches[1]&gianism_action=$matches[2]'
                );
                // Hook for rewrite rules
                add_action('rewrite_rules_array', array($this, 'rewrite_rules_array'));
                // Check if rewrite rules are satisfied
                add_action('admin_init', array($this, 'check_rewirte'));
                // WP_Query
                add_action('pre_get_posts', array($this, 'hijack_query'));
            }

            // Enqueue scripts
            add_action('login_enqueue_scripts', array($this, 'enqueue_global_assets'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_global_assets'));

            // Add User's message
            add_action('gianism_notices', array($this, 'flush_message'));
            add_action('login_footer', array($this, 'user_notices'));
            add_action('wp_footer', array($this, 'user_notices'), 100);
        }
    }

    /**
     * Customize rewrite rules
     *
     * @param array $rules
     * @return array
     */
    public function rewrite_rules_array( array $rules ){
        if( !empty($this->rewrites) ){
            foreach($this->rewrites as $rewrite => $regexp){
                if( !isset($rules[$rewrite]) ){
                    $rules = array_merge(array(
                        $rewrite => $regexp,
                    ), $rules);
                }
            }
        }
        return $rules;
    }

    /**
     * Check rewrite rules and flush if required
     *
     */
    public function check_rewirte(){
        $registered_rewrites = get_option('rewrite_rules');
        foreach( $this->rewrites as $reg => $replaced ){
            if( !isset($registered_rewrites[$reg]) || $replaced != $registered_rewrites[$reg]){
                flush_rewrite_rules();
            }
        }
    }

    /**
     * If endpoint matched, do parse request.
     *
     * @param \WP_Query $wp_query
     */
    public function hijack_query( \WP_Query &$wp_query){
        if( !is_admin() && $wp_query->is_main_query()
            && ($service = $wp_query->get('gianism_service'))
            && ($action = $wp_query->get('gianism_action'))
        ){
            $service = array_search($service, $this->prefixes);
            if( false !== $service ){
                $instance = $this->get_service_instance($service);
                $instance->parse_request($action, $wp_query);
            }else{
                $wp_query->set_404();
            }
        }
    }

    /**
     * Notice about session
     */
    public function notice_about_session(){
        if( current_user_can('manage_options') ){
            printf('<div class="error"><p>%s</p></div>', $this->_('Session is not supported. Gianism requires session for SNS connection, so please contact to your server administrator.'));
        }
    }

    /**
     * Show message on admin screen
     *
     */
    public function flush_message(){
        if( isset($_SESSION[$this->name]) ){
            foreach( array('error', 'updated') as $key ){
                if( isset($_SESSION[$this->name][$key]) && !empty($_SESSION[$this->name][$key]) ){
                    $messages = (array)$_SESSION[$this->name][$key];
                    printf('<div class="%s"><p>%s</p></div>', $key, implode('<br />', $messages));
                    $_SESSION[$this->name][$key] = array();
                }
            }
        }
    }

    /**
     * Output message for user.
     */
    public function user_notices(){
        echo '<div class="wpg-notices toggle">';
        do_action('gianism_notices');
        echo '</div><!-- wpg-notices -->';
    }


    /**
     * Register assets
     *
     */
    public function register_assets(){
        wp_register_style('ligature-symbols', $this->url.'assets/compass/stylesheets/lsf.css', array(), '2.11');
        wp_register_style($this->name, $this->url."assets/compass/stylesheets/gianism-style.css", array('ligature-symbols'), $this->version);

    }

    /**
     * Enqueue assets
     */
    public function enqueue_global_assets(){
        wp_enqueue_style($this->name);
        wp_enqueue_script($this->name.'-notice-helper', $this->url.'assets/compass/js/public-notice.js', array('jquery-effects-highlight'), $this->version);
    }
}
