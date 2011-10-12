<?php
/**
 * Utility Class for WP Gianism.
 * @package wp_gianism
 */
class WP_Gianism extends Hametuha_Library{
	
	/**
	 * @var Facebook_Controller
	 */
	public $fb = null;
	
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
		'fb_enabled' => 0,
		'fb_app_id' => '',
		'fb_app_secret' => '',
		'fb_fan_gate' => 0
	);
	
	/**
	 * Common Hook
	 */
	public function init(){
		if($this->option['fb_enabled']){
			require_once $this->dir."/sdks/facebook/facebook_controller.php";
			$this->fb = new Facebook_Controller($this->option['fb_app_id'], $this->option['fb_app_secret']);
		}
		if($this->option['fb_enabled']){
			add_action('show_user_profile', array($this, 'show_user_profile'));
		}
		//Add Assets
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('enqueue_scripts', array($this, 'enqueue_scripts'));
	}
	
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
			$this->option['fb_enabled'] = ($this->post('fb_enabled') == 1) ? 1 : 0;
			$this->option['fb_app_id'] = (string)$this->post('fb_app_id');
			$this->option['fb_app_secret'] = (string)$this->post('fb_app_secret');
			$this->option['fb_fan_gate'] = (int)$this->post('fb_fan_gate');
			if(update_option("{$this->name}_option", $this->option)){
				$this->add_message($this->_('Option updated.'));
			}else{
				$this->add_message($this->_('Option failed to update.'), true);
			}
		}
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
	 * Render Profile Options
	 * @param WP_User $profileuser
	 * @return void
	 */
	public function show_user_profile($profileuser){
		?>
		<h3><?php $this->e('External Service'); ?></h3>
		<table class="form-table">
			<tbody>
				<?php do_action('gianism_user_profile', $profileuser);?>
			</tbody>
		</table>
		<?php
	}
	
	/**
	 * Enqueue Javascripts on admin panel
	 * @param string $hook
	 */
	public function enqueue_scripts($hook){
		if(defined('IS_PROFILE_PAGE')){
			wp_enqueue_script('jquery');
		}
	}
	
}