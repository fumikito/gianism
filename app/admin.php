<?php

namespace Gianism;
use Gianism\Service\Google;


/**
 * Create admin panel for Gianism
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0.0
 */
class Admin extends Pattern\Singleton
{

    /**
     * Constructor
     *
     * @param array $argument
     */
    protected function __construct( array $argument = array() ){
        // Add admin page
        add_options_page($this->_('Gianism Setting'), $this->_("Gianism Setting"), 'manage_options', 'gianism', array($this, 'render'));


        //Create plugin link
        add_filter('plugin_action_links', array($this, 'plugin_page_link'), 10, 2);
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 4);
        // Add option save hook
        add_action( 'load-settings_page_gianism', array($this, 'update_option'));
        // Register script
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        // Notice
        add_action('admin_notices', array($this, 'invalid_option_notices'));

        // Add tool
        if( $this->is_enabled('google') ){
            add_submenu_page('tools.php', $this->_('Google Analytics'), $this->_('Google Analytics'), 'manage_options', 'gianism_ga', array($this, 'ga_render'));
        }
    }

    /**
     * Render admin panel
     */
    public function render(){
        $this->get_template('general');
    }

    /**
     * Render tools page
     */
    public function ga_render(){
        $this->get_template('analytics');
    }

    /**
     * Get admin panel URL
     *
     * @param string $view 'setup', 'customize', 'advanced'
     * @return string|void
     */
    public function setting_url($view = ''){
        $query = array(
            'page' => 'gianism',
        );
        if( $view ){
            $query['view'] = $view;
        }
        return admin_url('options-general.php?'.http_build_query($query));
    }

    /**
     * Detect current admin panel
     *
     * @param string $view
     * @return bool
     */
    protected function is_view($view = ''){
        if( 'gianism' != $this->request('page') || 'options-general.php' !== basename($_SERVER['SCRIPT_FILENAME']) ){
            return false;
        }
        $requested_view = $this->request('view');
        switch($view){
            case 'setup':
            case 'customize':
	        case 'advanced':
			case 'fb-api':
                return $view == $requested_view;
                break;
            case 'setting':
            default:
                return (empty($requested_view) || 'setting' == $requested_view );
                break;
        }
    }

    /**
     * Load template file
     *
     * @param string $name
     */
    private function get_template($name){
        $path = $this->dir.'templates'.DIRECTORY_SEPARATOR."{$name}.php";
        if( file_exists($path) ){
            $option = Option::get_instance();

            include $path;
        }
    }

    /**
     * Register assets
     *
     * @param string $hook_suffix
     */
    public function admin_enqueue_scripts($hook_suffix){
        // Only on setting page
        if( false !== array_search($hook_suffix, array('settings_page_gianism', 'tools_page_gianism_ga')) ){
            if( !is_null($this->request('view')) ){
                wp_enqueue_style('gianism-syntax-highlighter-core', $this->url.'assets/syntax-highlighter/shCore.css', null, '3.0.83');
                wp_enqueue_style('gianism-syntax-highlighter-default', $this->url.'assets/syntax-highlighter/shThemeDefault.css', null, '3.0.83');
                wp_enqueue_script('gianism-syntax-highlighter-core', $this->url.'assets/syntax-highlighter/shCore.js', null, '3.0.83');
                wp_enqueue_script('gianism-syntax-highlighter-php', $this->url.'assets/syntax-highlighter/shBrushPhp.js', null, '3.0.83');
            }
        }
        // Setting page and profile page
        if( false !== array_search($hook_suffix, array('settings_page_gianism', 'profile.php', 'tools_page_gianism_ga', 'post-new.php', 'post.php'))){
            wp_enqueue_script($this->name.'-admin-helper', $this->url.'assets/compass/js/admin-helper'.( WP_DEBUG ? '' : '.min' ).'.js', array('jquery', 'jquery-form'), $this->version, true);
            wp_localize_script($this->name.'-admin-helper', 'Gianism', array(
                'endpoint' => admin_url('admin-ajax.php'),
                'action' => Google::AJAX_ACTION,
                'nonce' => wp_create_nonce(Google::AJAX_ACTION),
            ));
        }

	    // Other
	    if( false !== array_search($hook_suffix, array('settings_page_gianism', 'profile.php', 'tools_page_gianism_ga', 'post-new.php', 'post.php', 'edit.php'))) {
		    wp_enqueue_style( $this->name . '-admin-panel', $this->url . 'assets/compass/stylesheets/gianism-admin.css', array( 'ligature-symbols' ), $this->version );
	    }
    }

    /**
     * Update option
     */
    public function update_option(){
        if( $this->verify_nonce('option') ){
            /** @var \Gianism\Option $option */
            $option = Option::get_instance();
            $option->update();
            wp_redirect($this->setting_url());
            exit;
        }
    }

    /**
     * Show message is options are invalid.
     */
    public function invalid_option_notices(){
        $message = array();
        /** @var \Gianism\Option $option */
        $option = Option::get_instance();
        if( current_user_can('manage_options') && $option->has_invalid_option('google_redirect')){
            $message[] = sprintf($this->_('Google redirect URL is deprecated since version 2.0. <strong>You must change setting on Google API Console</strong>. Please <a href="%s">update option</a> and follow the instruction there.'), $this->setting_url());
        }
        if( !$this->is_enabled() ){
            $message[] = sprintf($this->_('No service is enabled. Please go to <a href="%s">Gianism Setiting</a> and follow instructions there.'), $this->setting_url());
        }
        if( !empty($message) ){
            array_unshift($message, '<strong>[Gianism]</strong>');
            printf('<div class="error"><p>%s</p></div>', implode('<br />', $message));
        }
    }

    /**
     * Setup plugin links.
     *
     * @param array $links
     * @param string $file
     * @return array
     */
    public function plugin_page_link($links, $file){
        if(false !== strpos($file, 'wp-gianism')){
            array_unshift($links, '<a href="'.$this->setting_url().'">'.__('Settings').'</a>');
        }
        return $links;
    }


    /**
     * Plugin row meta
     *
     * @param array $plugin_meta
     * @param string $plugin_file
     * @param array $plugin_data
     * @param string $status
     * @return mixed
     */
    public function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status){
        if(false !== strpos($plugin_file, 'wp-gianism')){
            for($i = 0, $l = count($plugin_meta); $i < $l; $i++ ){
                if( false !== strpos($plugin_meta[$i], 'http://takahashifumiki.com') ){
                    $plugin_meta[$i] = str_replace('http://takahashifumiki.com', $this->ga_link('http://takahashifumiki.com', 'link'), $plugin_meta[$i]);
                }
            }
            $plugin_meta[] = sprintf('<a href="http://github.takahashifumiki.com/Gianism/">Github</a>');
        }
        return $plugin_meta;
    }

    /**
     * Generate Google Analytics ready URL
     *
     * @ignore
     */
    public function ga_link($link, $media = 'link'){
        $source = rawurlencode(str_replace('http://', '', home_url('', 'http')));
        return $link."?utm_source={$source}&utm_medium={$media}&utm_campaign=Gianism";
    }

	/**
	 * Show version info
	 *
	 * @param string $version
	 */
	public function new_from($version){
		$version = $this->major_version($version);
		$current_version = $this->major_version($this->version);
		if( version_compare($version, $current_version, '>=') ){
			echo '<span class="gianism-new">New since '.$version.'</span>';
		}
	}

	/**
	 * Get major version
	 *
	 * @param string $version
	 * @return string
	 */
	private function major_version($version){
		$segments = explode('.', $version);
		return $segments[0].'.'.$segments[1];
	}
}