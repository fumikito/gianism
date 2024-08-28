<?php

namespace Gianism\Pattern;
use Gianism\Helper\Input;
use Gianism\Helper\Option;

/**
 * Notice utility
 *
 * @package Gianism
 * @since 3.0.4
 * @property Input  $input
 * @property Option $option
 */
abstract class AbstractNotice {

	const DEPRECATED = false;

	/**
	 * @var bool If duplicated, never executed.
	 */
	public static $deprecated = false;

	/**
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * @var array
	 */
	private static $notices = [];

	/**
	 * Constructor
	 */
	final public function __construct() {
		self::$notices[ $this->get_key() ] = $this;
		// Register Ajax action
		if ( ! self::$initialized ) {
			self::$initialized = true;
			// Register notices
			add_action( 'wp_ajax_gianism_admin_notice', [ $this, 'admin_notice_handler' ] );
		}
		// Check notice and register them if exists.
		add_action( 'admin_init', [ $this, 'register_notice' ] );
		// Run constructor alternatives.
		$this->init();
	}

	/**
	 * Init function
	 */
	protected function init() {
		// Do something on constructor.
	}

	/**
	 * Register notice if exists.
	 */
	public function register_notice() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			// Do nothing.
			return;
		}
		if ( ! current_user_can( $this->role ) || ! $this->has_notice() ) {
			// Setting is O.K.
			return;
		}
		if ( $this->notice_dismissed() ) {
			// Already dismissed.
			return;
		}
		// Print notice
		add_action( 'admin_notices', [ $this, 'invalid_option_notices' ] );
	}

	/**
	 * Should return key
	 *
	 * @return string
	 */
	abstract public function get_key();

	/**
	 * If this function returns true, notice will be rendered.
	 *
	 * @return bool
	 */
	abstract protected function has_notice();

	/**
	 * Should return message string.
	 *
	 * @return string
	 */
	abstract protected function message();

	/**
	 * Role or capability to display notice.
	 *
	 * @var string
	 */
	protected $role = 'manage_options';

	/**
	 * Get options
	 *
	 * @return array
	 */
	protected function dismissed_notices() {
		return (array) Option::get_instance()->get( 'gianism_notice_log', [] );
	}

	/**
	 * Detect if notice is dismissed.
	 *
	 * @return bool
	 */
	protected function notice_dismissed() {
		$key    = $this->get_key();
		$option = $this->dismissed_notices();
		return isset( $option[ $key ] ) && $option[ $key ];
	}

	/**
	 * Update notice
	 */
	protected function update_notice() {
		$option                     = $this->dismissed_notices();
		$option[ $this->get_key() ] = 1;
		update_option( 'gianism_notice_log', $option );
	}

	/**
	 * Show setting error on screen.
	 */
	public function invalid_option_notices() {
		$endpoint = wp_nonce_url( admin_url( 'admin-ajax.php?action=gianism_admin_notice&key=' . $this->get_key() ), 'gianism_notice' );
		?>
		<div class="error" style="position: relative;">
			<p>
				<button data-endpoint="<?php echo esc_url( $endpoint ); ?>" class="gianism-admin-notice notice-dismiss"></button>
				<?php echo wp_kses_post( $this->message() ); ?>
			</p>
		</div>
		<?php
	}


	/**
	 * Handle admin notice
	 */
	final public function admin_notice_handler() {
		try {
			if ( ! $this->input->verify_nonce( 'gianism_notice' ) ) {
				throw new \Exception( __( 'You have no permission.', 'wp-gianism' ), 401 );
			}
			$key = $this->input->get( 'key' );
			if ( ! isset( self::$notices[ $key ] ) ) {
				throw new \Exception( __( 'This request type is not allowed.', 'wp-gianism' ), 400 );
			}
			/** @var AbstractNotice $instance */
			$instance = self::$notices[ $key ];
			if ( $instance->notice_dismissed() ) {
				throw new \Exception( __( 'This notice is already dismissed.', 'wp-gianism' ), 400 );
			}
			// Update option
			$instance->update_notice();
			wp_send_json_success( 'O,K.' );
		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::get_instance();
				break;
			case 'option':
				return Option::get_instance();
			default:
				return null;
				break;
		}
	}
}
