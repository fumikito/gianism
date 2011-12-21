<?php
/**
 * Common Utility for Social Service
 *
 * @package gianism
 */
class Gianism_Controller {
	
	/**
	 * Print Javascript if true.
	 * @var boolean
	 */
	protected $js = false;
	
	/**
	 * Script to print on footer.
	 * @var string
	 */
	protected $scripts = '';
	
	/**
	 * Pseudo domain
	 * @var string
	 */
	protected $pseudo_domain = '';
	
	/**
	 * Constructor
	 * @param array $option 
	 */
	public function __construct($option) {
		$this->set_option($option);
		if(method_exists($this, 'init_action')){
			$this->init_action();
		}
		if(method_exists($this, 'user_profile')){
			//Add Hook on Profile page
			add_action('gianism_user_profile', array($this, 'user_profile'));
		}
		if(method_exists($this, 'login_form')){
			//Add Hook on Login Form page
			add_action('gianism_login_form', array($this, 'login_form'));
			//Add Hook on Register Form
			if(!method_exists($this, 'register_form')){
				add_action('gianism_regsiter_form', array($this, 'login_form'));
			}
		}
		if(method_exists($this, 'register_form')){
			//Add Hook on Profile page
			add_action('gianism_regsiter_form', array($this, 'register_form'));
		}
		if(method_exists($this, 'print_script')){
			//Add Hook On footer
			add_action('admin_print_footer_scripts', array($this, 'print_script'));
			add_action('wp_footer', array($this, 'print_script'));
		}
		if(method_exists($this, 'wp_mail')){
			add_filter('wp_mail', array($this, '_wp_mail'));
		}
	}
	
	/**
	 * Setup default option
	 * @param array $option
	 * @return null 
	 */
	protected function set_option($option){
		return;
	}
	
	/**
	 * Returns redirect to url if set.
	 * @param string $default
	 * @param array $args
	 * @return string 
	 */
	protected function get_redirect_to($default, $args = array()){
		if(isset($_REQUEST['redirect_to'])){
			$domain = $_SERVER['SERVER_NAME'];
			if(preg_match("/^(https?:\/\/{$domain}|\/)/", $_REQUEST['redirect_to'])){
				$redirect_to = $_REQUEST['redirect_to'];
				if(!empty($args)){
					$redirect_to .= (false !== strpos($redirect_to, '?')) ? '&' : '?';
					$counter = 0;
					foreach($args as $key => $val){
						if($counter == 0){
							$redirect_to .= '&';
						}
						$redirect_to .= $key.'='.rawurlencode($val);
						$counter++;
					}
				}
				return $redirect_to;
			}else{
				return $default;
			}
		}else{
			return $default;
		}
	}
	
	/**
	 * Returns current action name.
	 * @return string
	 */
	protected function get_action(){
		if(isset($_REQUEST['wpg'])){
			return (string)$_REQUEST['wpg'];
		}else{
			return '';
		}
	}
	
	/**
	 * Retuns if given mail address is pseudo.
	 * @param string $mail
	 * @return boolean
	 */
	protected function is_pseudo_mail($mail){
		return !empty($this->pseudo_domain) && (false !== strpos($mail, "@".$this->pseudo_domain));
	}
	
	/**
	 *
	 * @param type $args
	 * @return type 
	 */
	public function _wp_mail($args){
		extract($args);
		if(!empty($this->pseudo_domain) && false !== strpos($to, "@".$this->pseudo_domain) && ($user_id = email_exists($to))){
			$this->wp_mail($user_id, $subject, $message, $headers, $attchment);
		}
		return $args;
	}
}
