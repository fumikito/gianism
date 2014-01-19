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
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct(array $argument = array()){
        // Start session
        if( !session_id() ){
            session_start();
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

            // Instanciate everything
            // and build rewrite rules
            // Prefixes
            $prefixes = array();
            foreach(array('facebook', 'twitter', 'google', 'yahoo', 'mixi') as $service){
                $instance = $this->get_service_instance($service);
                if( $instance && $instance->enabled ){
                    $prefixes[] = $instance->url_prefix;
                }
            }
            if(!empty($prefixes)){
                $preg = implode('|', $prefixes);
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

            // Add User's message
            add_action('gianism_notices', array($this, 'flush_message'));
            add_action('wp_footer', array($this, ''), 100);
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
            $instance = $this->get_service_instance($service);
            $instance->parse_request($action, $wp_query);
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
                    printf('<div class="%s"><p>%s</p></div>', $key, implode('<br />', $_SESSION[$this->name][$key]));
                    unset($_SESSION[$this->name][$key]);
                }
            }
        }
    }

    /**
     * Output message for user.
     */
    public function user_notices(){
        echo '<div class="wpg-notices" style="display: none;">';
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
}
