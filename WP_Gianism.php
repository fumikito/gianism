<?php
/**
 * Utility Class for WP Gianism.
 * @package wp_gianism
 */
class WP_Gianism extends Hametuha_Library{
	
	/**
	 * @see Hametuha_Plugin
	 * @var string
	 */
	protected $name = "wp_gianism";
	
	/**
	 * オプション初期値
	 * @var array
	 */
	protected $default_option = array(
		'fb_app_id' => '',
		'fb_app_secret' => ''
	);
	
	/**
	 * Public Hook
	 */
	public function template_redirect(){
		
	}
	
	/**
	 * Action hook for admin panel.
	 */
	public function admin_init(){
		//Execute when option updated.
		if($this->verify_nonce('option')){
			$this->option['fb_app_id'] = $this->post('fb_app_id');
			$this->option['fb_app_secret'] = $this->post('fb_app_secret');
			if(update_option("{$this->name}_option", $this->option)){
				$this->add_message($this->_('Option updated.'));
			}else{
				$this->add_message($this->_('Option failed to update.'), true);
			}
		}
		//Add Assets
		//add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}
	
	/**
	 * Hook for admin_menu
	 * @return void
	 */
	public function admin_menu(){
		add_users_page($this->_('WP Gianism setting'), $this->_("Social Connect"), 'edit_users', 'gianism', array($this, 'render'));
	}
	
	/**
	 * Render options page
	 * @return void
	 */
	public function render(){
		require_once $this->dir.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."setting.php";
	}
	
	/**
	 * Enqueue Javascripts on admin panel
	 * @param string $hook
	 */
	public function enqueue_scripts($hook){
		wp_enqueue_script('syntax-init', $this->url."/assets/onload.js", array('syntax-php'), $this->version);
		wp_enqueue_style('syntax-theme-default');
	}
	
}