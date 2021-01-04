<?php

namespace Gianism\Plugins;

/**
 * Additional plugin for Simple Memberships
 *
 * @package Gianism\Plugins
 */
class SimpleMembership extends PluginBase {

	/**
	 * If plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_enabled() {
		return gianism_simple_membership_is_active();
	}

	/**
	 * Get plugin description.
	 *
	 * @return string
	 */
	public function plugin_description() {
		return __( 'Integration for Simple Membership.', 'wp-gianism' );
	}

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		add_filter( 'gianism_setting_screen_views', [ $this, 'screen_view' ], 11, 2 );
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'wpg_connect', [ $this, 'connection_hook' ], 10, 4 );
	}

	/**
	 * Add screen.
	 *
	 * @param array  $views Array of views.
	 * @param string $slug  Page slug.
	 * @return array
	 */
	public function screen_view( $views, $slug ) {
		if ( 'gianism' === $slug ) {
			$views['simple-membership'] = sprintf( '<i class="lsf lsf-users"></i> %s', 'Simple Membership' );
		}
		return $views;
	}

	/**
	 * Register setting
	 */
	public function register_setting() {
		$section_id = 'gianism_simple_mbmership_section';
		add_settings_section( $section_id, __( 'Membership Setting', 'wp-gianism' ), function() {
			//
		}, $this->get_setting_slug() );
		add_settings_field( 'gianism_default_membership_level', __( 'Default Member Level', 'wp-gianism' ), function() {
			$current_setting = get_option( 'gianism_default_membership_level', '' );
			$levels = $this->get_user_levels();
			?>
			<select name="gianism_default_membership_level">
				<option value=""<?php selected( $current_setting, '' ) ?>><?php esc_html_e( 'No Level', 'wp-gianism' ) ?></option>
				<?php foreach ( $levels as $level ) : ?>
				<option value="<?php echo esc_attr( $level->id ) ?>"<?php selected( $current_setting, $level->id ) ?>>
					<?php echo esc_html( $level->alias ); ?>
				</option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php esc_html_e( 'If default membership level is assigned, a newly created user by Gianism will be assigned to that member level.', 'wp-gianism' ) ?>
			</p>
			<?php
		}, $this->get_setting_slug(), $section_id );
		register_setting( $this->get_setting_slug(), 'gianism_default_membership_level' );
	}

	/**
	 * Slug name for setting section.
	 *
	 * @return string
	 */
	public function get_setting_slug() {
		return 'gianism_simple_membership';
	}

	/**
	 * Get all user levels
	 *
	 * @global $wpdb
	 * @return \stdClass[]
	 */
	public function get_user_levels() {
		global $wpdb;
		$query = "SELECT * FROM {$wpdb->prefix}swpm_membership_tbl";
		$results = $wpdb->get_results( $query );
		return (array) $results;
	}

	/**
	 * Create user level if specified.
	 *
	 * @param int $user_id
	 * @param $data
	 * @param $service_name
	 * @param $on_creation
	 */
	public function connection_hook( $user_id, $data, $service_name, $on_creation ) {
		
	}
}
