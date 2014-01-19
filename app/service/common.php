<?php

namespace Gianism\Service;

use Gianism\Option, Gianism\Pattern\Singleton;

/**
 * Common Utility for Social Service
 *
 * @package Gianism\Service
 * @author Takahashi Fumiki
 * @property-read string $service_name
 * @property-read bool $enabled
 */
abstract class Common extends Singleton
{

    /**
     * URL prefix
     *
     * If this property is empty,
     * service name will be used.
     * e.g. http://example.jp/facebook/
     *
     * @var string
     */
    public $url_prefix = '';

    /**
     * Option key to retrieve
     *
     * @var array
     */
    protected $option_keys = array();

    /**
     * Verbose service name
     *
     * If not set, $this->service_name will be used;
     *
     * @var string
     */
    public $verbose_service_name = '';

	/**
	 * Constructor
     *
     * If you override constructor, call inside that
     *
     * <code>
     * parent::construct();
     * </code>
     *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
        // Setup name
        if( empty($this->verbose_service_name) ){
            $this->verbose_service_name = $this->service_name;
        }
        if( empty($this->url_prefix) ){
            $this->url_prefix = $this->service_name;
        }
        // Sync options
		$this->set_option();
        add_action(Option::UPDATED_ACTION, array($this, 'set_option'));
        // Register actions if enabled.
        if( $this->enabled ){
            // Initialize
            $this->init_action();
            // Show profile page
            add_action('gianism_user_profile', array($this, 'profile_connect'));
            //Add Hook on Login Form page
            add_action('gianism_login_form', array($this, 'login_form'));
            if(method_exists($this, 'print_script')){
                //Add Hook On footer
                add_action('admin_print_footer_scripts', array($this, 'print_script'));
                add_action('wp_footer', array($this, 'print_script'));
                add_action('login_footer', array($this, 'print_script'));
            }
		}
	}

    /**
     * Initialize
     *
     * If some stuff is required, override this.
     *
     * @return void
     */
    protected function init_action(){
        // Do stuff.
    }
	
	/**
	 * Setup default option
     *
     * @return void
	 */
	final protected function set_option(){
        /** @var \Gianism\Option $option */
        $option = Option::get_instance();
		foreach($this->option_keys as $key){
            if( isset($option->values[$key]) ){
                $this->{$key} = $option->values[$key];
            }
        }
	}

    /**
     * Parse request
     *
     * @param string $action
     * @return void
     */
    //abstract public function parse_request($action);

    /**
     * Detect if user is connected to this service
     *
     * @param int $user_id
     * @return bool
     */
    abstract public function is_connected($user_id);

    /**
     * Called on redirect endpoint
     *
     * @param string $action
     * @param \WP_Query $wp_query
     * @return void
     */
    public function parse_request($action, \WP_Query $wp_query){
        $method = 'handle_'.strtolower(str_replace('-', '_', $action));
        if( method_exists($this, $method) && $this->enabled ){
            if( 'default' !== $action && !$this->verify_nonce($this->service_name.'_'.$action) ){
                $this->wp_die($this->_('Cheatin\'? Wrong access.'), 403);
            }else{
                $this->{$method}($wp_query);
            }
        }else{
            $wp_query->set_404();
        }
    }
    /**
     * Show connect button on profile page
     *
     * @param \WP_User $user
     * @return void
     */
    public function profile_connect( \WP_User $user ){
        $html = <<<EOS
<tr>
    <th><i class="lsf lsf-{$this->service_name}"></i> {$this->verbose_service_name}</th>
    <td class="wpg-connector {$this->service_name}">
        <p class="description desc-%s"><i class="lsf lsf-%s"></i> %s</p>
        <p class="button-wrap">%s</p>
    </td><!-- .wpg-connector -->
</tr>
EOS;
        $is_connected = $this->is_connected($user->ID);
        if( $is_connected ){
            $class_name = 'connected';
            $icon_class = 'checkbox';
            $message = $this->connection_message('connected');
            $button = $this->disconnect_button();
        }else{
            $class_name = 'disconnected';
            $icon_class = 'checkboxempty';
            $message = $this->connection_message('disconnected');
            $button = $this->connect_button();
        }
        /**
         * Filtering messsage on connection table
         *
         * @param string $message
         * @param string $service
         * @param bool $is_connected
         * @return string
         */
        $message = apply_filters('gianism_connect_message', $message, $this->service_name, $is_connected);
        printf($html, $class_name, $icon_class, $message, $button);
    }

    /**
     * Connection message
     *
     * Overriding this function, you can
     * customize connection message
     *
     * @param string $context
     * @return string
     */
    public function connection_message($context = 'connected'){
        switch($context){
            case 'connected':
                return sprintf($this->_('Your account is already connected with %s account.'), $this->verbose_service_name);
                break;
            default: // Disconnected
                return sprintf($this->_('Connecting to %1$s, you can login with %2$s via %1$s without password or email address.'), $this->verbose_service_name, get_bloginfo('name'));
                break;
        }
    }

    abstract public function login_form();

	/**
	 * Returns redirect to url if set.
     *
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
     *
	 * @return string
	 */
	protected function get_action(){
        return $this->request('wpg');
	}

	/**
	 * Resturns link to filter
     *
	 * @param string $markup
	 * @param string $href
	 * @param string $text
	 * @return string
	 */
	public function filter_link($markup, $href, $text){
		$markup = apply_filters('gianism_link_'.$this->service_name, $markup, $href, $text);
		return $markup;
	}

	/**
	 * Get URL for emdiate endpoint.
     *
	 * @param string $action 'connect', 'disconnect', 'login' or else.
     * @param string $nonce_key If empty, nonce won't be set.
	 * @param array $args
	 * @return string 
	 */
	protected function get_redirect_endpoint($action = '', $nonce_key = '', $args = array()){
        $prefix = empty($this->url_prefix) ? $this->service_name : $this->url_prefix;
		$url = untrailingslashit(home_url($prefix, ($this->is_ssl_required() ? 'https' : 'http'))).'/';
        if( !empty($action) ){
            $url .= $action.'/';
        }
        if( !empty($args) ){
            $url .= '?'.http_build_query($args);
        }
        if( !empty($nonce_key) ){
            $url = wp_nonce_url($url, $this->nonce_action($nonce_key), "_{$this->name}_nonce");
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
     * Create common button
     *
     * @param string $text
     * @param string $href
     * @param bool $icon_name
     * @param array $class_names
     * @param array $attributes
     * @return string
     */
    public function button($text, $href, $icon_name = true, array $class_names = array('wpg-button'), array $attributes = array()){
        // Create icon
        $icon = '';
        if( true === $icon_name ){
            $icon = "<i class=\"lsf lsf-{$this->service_name}\"></i> ";
        }elseif( is_string($icon_name) ){
            $icon = "<i class=\"lsf lsf-{$icon_name}\"></i> ";
        }
        $class_attr = implode(' ', array_map(function($attr){
            return esc_attr($attr);
        }, $class_names));
        $atts = array();
        foreach( $attributes as $key => $value){
            switch($key){
                case 'onclick':
                    // Do nothing
                    break;
                default:
                    $key = 'data-'.$key;
                    break;
            }
            $value = esc_attr($value);
            $atts[] = "{$key}=\"{$value}\"";
        }
        $atts = ' '.implode(' ', $atts);
        return sprintf('<a href="%2$s" class="%4$s"%5$s>%3$s%1$s</a>',
            $text, $href, $icon, $class_attr, $atts);
    }

    /**
     * Get connect button
     *
     * @param string $redirect If not set, profile page's URL
     * @return string
     */
    public function connect_button( $redirect = '' ){
        if( empty($redirect) ){
            $redirect = admin_url('profile.php');
        }
        $redirect_to = apply_filters('gianism_redirect_after_connect', $redirect, $this->service_name);
        $url = $this->get_redirect_endpoint('connect', $this->service_name.'_connect', array(
                'redirect_to' => $redirect_to,
            ));
        $args = array(
            'gianism-ga-category' => "gianism/{$this->service_name}",
            'gianism-ga-action' => 'connect',
            'gianism-ga-label' => sprintf($this->_('Connect %s'), $this->verbose_service_name),
        );
        return $this->button($this->_('Connect'), $url, 'link', array('wpg-button', 'connect'), $args);
    }

    /**
     * Get disconnect button
     *
     * @param string $redirect If not set, profile page's URL
     * @return string
     */
    public function disconnect_button( $redirect = '' ){
        if( empty($redirect) ){
            $redirect = admin_url('profile.php');
        }
        $redirect_to = apply_filters('gianism_redirect_after_disconnect', $redirect, $this->service_name);
        $url = $this->get_redirect_endpoint('disconnect', $this->service_name.'_disconnect', array(
                'redirect_to' => $redirect_to,
            ));
        $args = array(
            'gianism-ga-category' => "gianism/{$this->service_name}",
            'gianism-ga-action' => 'disconnect',
            'gianism-ga-label' => sprintf($this->_('Disconnect %s'), $this->verbose_service_name),
            'gianism-confirm' => sprintf($this->_('You really disconnect from %s? If so, please be sure about your credential(email, passowrd), or else you might not be able to login again.'))
        );
        return $this->button($this->_('Disconnect'), $url, 'logout', array('wpg-button', 'disconnect'), $args);
    }

    /**
     * Fires connect hook
     *
     * @param int $user_id
     * @param mixed $data
     * @param bool $on_creation
     */
    protected function hook_connect($user_id, $data, $on_creation = false){
        /**
         * wpg_disconnect
         *
         * Fires when user account is disconnected from SNS account.
         *
         * @param int $user_id
         * @param mixed $data
         * @param string $service_name
         * @param bool $on_creation
         */
        do_action('wpg_connect', $user_id, $data, $this->service_name, (bool) $on_creation);
    }

    /**
     * Fires disconnect hook
     *
     * @param $user_id
     */
    protected function hook_disconnect($user_id){
        /**
         * wpg_disconnect
         *
         * Fires when user account is disconnected from SNS account.
         *
         * @param int $user_id
         * @param string $service_name
         */
        do_action('wpg_disconnect', $user_id, $this->service_name);
    }

    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name){
        switch($name){
            case 'service_name':
                $segments = explode('\\', get_called_class());
                return strtolower($segments[count($segments) - 1]);
                break;
            case 'enabled':
                return $this->is_enabled($this->service_name);
                break;
            default:
                return parent::__get($name);
                break;
        }
    }
}
