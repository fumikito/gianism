<?php

namespace Gianism\UI;


use Gianism\Pattern\AppBase;

/**
 * Screen base class.
 * @package Gianism
 */
abstract class Screen {

	use AppBase;

	protected $slug = '';

	protected $default_view = '';

	protected $template = '';

	protected $base_url = 'options-general.php';

	/**
	 * View names
	 *
	 * @var array
	 */
	protected $views = [];

	/**
	 * Screen constructor.
	 */
	final public function __construct() {
		$this->admin_init();
		/**
		 * Add view to tab
		 */
		$this->views = apply_filters( 'gianism_setting_screen_views', $this->views, $this->slug );
	}

	/**
	 * Do something on admin screen.
	 *
	 * @return void
	 */
	abstract protected function admin_init();

	/**
	 * Get template file
	 *
	 * @param string $name
	 * @return string
	 */
	protected function get_template( $name ) {
		$name = basename( $name );
		$path = $this->get_dir() . DIRECTORY_SEPARATOR . "{$name}.php";
		if ( file_exists( $path ) ) {
			return $path;
		} else {
			/**
			 * Add contents for external plugins
			 *
			 * @filter gianism_external_view_file
			 * @param string $path
			 * @param string $slug
			 * @param string $view_name
			 * @return string File path for template.
			 */
			$external = apply_filters( 'gianism_external_view_file', '', $this->slug, $name );
			if ( file_exists( $external ) ) {
				return $external;
			}
		}
		return '';
	}

	/**
	 * Render screen
	 */
	public function render() {
		$this->load_template( $this->get_view() );
	}

	/**
	 * Show toggle button
	 *
	 * @param string $name
	 * @param string $current_value
	 * @param int    $value
	 */
	public function switch_button( $name, $current_value, $value = 1 ) {
		?>
		<div class="onoffswitch">
			<input type="checkbox" name="<?php echo esc_attr( $name ) ?>" class="onoffswitch-checkbox" id="<?php echo esc_attr( $name ) ?>"
			       value="<?php echo esc_attr( $value ) ?>"<?php checked( $current_value == $value ) ?>>
			<label class="onoffswitch-label" for="<?php echo esc_attr( $name ) ?>">
				<span class="onoffswitch-inner"></span>
				<span class="onoffswitch-switch"></span>
			</label>
		</div>
		<?php
	}

	/**
	 * Load template
	 *
	 * @param string $name
	 */
	protected function load_template( $name ) {
		include $this->get_dir() . '/parts/header.php';
		$path = $this->get_template( $name );
		if ( $path ) {
			include $path;
		} else {
			printf(
				'<div class="gianism-load-error">%s</div>',
				sprintf( $this->_( 'Template file <code>%s</code> is missing.' ), esc_html( $name ) )
			);
		}
		/**
		 * Executed on Admin screen footer.
		 *
		 * @action gianism_admin_template_footer
		 * @param string $slug
		 * @param string $name
		 */
		do_action( 'gianism_admin_template_footer', $this->slug, $name );
		include $this->get_dir() . '/parts/footer.php';
	}

	/**
	 * Override this function if template is outside.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function template_path( $name ) {
		return '';
	}

	/**
	 * @return string
	 */
	protected function get_dir() {
		return dirname( dirname( dirname( __DIR__ ) ) ) . '/templates';
	}

	/**
	 * Get view
	 *
	 * @return string
	 */
	protected function get_view() {
		$view = $this->input->request( 'view' );
		return isset( $this->views[ $view ] ) ? $view : $this->default_view;
	}

	/**
	 * Detect current admin panel
	 *
	 * @param string $view
	 *
	 * @return bool
	 */
	protected function is_view( $view = '' ) {
		$requested_view = $this->input->request( 'view' );
		if ( ! $view ) {
			return $this->input->request( 'page' ) == $this->slug;
		} elseif ( $this->default_view == $view ) {
			return ( empty( $requested_view ) || $this->default_view == $requested_view );
		} else {
			return $view == $requested_view;
		}
	}

	/**
	 * Get admin panel URL
	 *
	 * @param string $view
	 *
	 * @return string|void
	 */
	public function setting_url( $view = '' ) {
		$query = array(
			'page' => $this->slug,
		);
		if ( $view && $this->default_view != $view ) {
			$query['view'] = $view;
		}

		return add_query_arg( $query, admin_url( $this->base_url ) );
	}

	/**
	 * Show version info
	 *
	 * @param string $version
	 */
	public function new_from( $version ) {
		$version = $this->major_version( $version );
		$current_version = $this->major_version( $this->version );
		if ( version_compare( $version, $current_version, '>=' ) ) {
			echo '<span class="gianism-new">New since ' . $version . '</span>';
		}
	}

	/**
	 * Get major version
	 *
	 * @param string $version
	 *
	 * @return string
	 */
	private function major_version( $version ) {
		$segments = explode( '.', $version );

		return $segments[0] . '.' . $segments[1];
	}

	/**
	 * Generate Google Analytics ready URL
	 *
	 * @ignore
	 */
	public function ga_link( $link, $media = 'link' ) {
		$source = rawurlencode( str_replace( 'http://', '', home_url( '', 'http' ) ) );

		return $link . "?utm_source={$source}&utm_medium={$media}&utm_campaign=Gianism";
	}
}
