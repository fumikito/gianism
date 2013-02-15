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
	 * Message to show
	 * @var string
	 */
	protected $message = '';
	
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
			add_action('login_footer', array($this, 'print_script'));
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
			}else{
				$redirect_to = $default;
			}
		}else{
			$redirect_to = $default;
		}
		return apply_filters('gianism_redirect_to', $redirect_to);
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
	
	/**
	 * Resturns link to filter
	 * @param string $markup
	 * @param string $href
	 * @param string $text
	 * @param string $hook_name
	 * @return string 
	 */
	public function filter_link($markup, $href, $text, $hook_name){
		$markup = apply_filters('gianism_link_'.$hook_name, $markup, $href, $text);
		return $markup;
	}
	
	
	/**
	 * Get URL for emdiate endpoint.
	 * @param string $action
	 * @param array $args
	 * @return string 
	 */
	protected function get_redirect_endpoint($action, $args = array()){
		$url = home_url();
		$grew = (false === strpos('?', $url)) ? '?' : "&";
		$url .= $grew."wpg={$action}";
		foreach($args as $key => $value){
			$url .= "&".$key."=".$value;
		}
		return $url;
	}
	
	/**
	 * Detect if current client is smartphone. 
	 * @return boolean
	 */
	protected function is_smartphone(){
		return (boolean)preg_match("/(iPhone|iPad|Android|MSIEMobile)/", $_SERVER['HTTP_USER_AGENT']);
	}
	
	/**
	 * Add message to alert. Can be overriden
	 * @param string $text 
	 */
	protected function add_message($text){
		$this->js = true;
		if(!empty($this->message)){
			$this->message .= '\n';
		}
		$this->message .= $text;
	}
	
	/**
	 * Print Javascript on footer. Can be overriden.
	 */
	public function print_script(){
		if($this->js && !empty($this->message)){
			echo $this->generate_message_script($this->message);
		}
	}
	
	/**
	 * Generate JS for alert
	 * @param string $message
	 * @return string 
	 */
	protected function generate_message_script($message){
		$message = esc_attr($message);
		$script = <<<EOS
			<script type="text/javascript">
				jQuery(document).ready(function($){
					alert("{$message}");
				});
			</script>
EOS;
		return apply_filters('gianism_alert', $script, $message);
	}
	
	/**
	 * Returns if ssl is required
	 * @global WP_Gianism $gianism
	 * @return boolean
	 */
	protected function is_ssl_required(){
		global $gianism;
		return $gianism->is_ssl_required();
	}
}
