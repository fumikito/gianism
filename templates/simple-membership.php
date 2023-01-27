<?php
/**
 * Simple membership screen.
 *
 * @package gianism
 * @see \Gianism\Plugins\SimpleMembership
 */
defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */

$simple_membership = \Gianism\Plugins\SimpleMembership::get_instance();

?>
<h3><i class="lsf lsf-users"></i> <?php esc_html_e( 'Simple Membership Integration', 'wp-gianism' ); ?></h3>

<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
	<?php
	settings_fields( $simple_membership->get_setting_slug() );
	do_settings_sections( $simple_membership->get_setting_slug() );
	submit_button();
	?>
</form>
