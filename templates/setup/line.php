<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */
/** @var \Gianism\Service\Line $instance */

?>

<h3><i class="lsf lsf-line"></i> LINE</h3>

<h4>Step1. <?php esc_html_e( 'Create new channel', 'wp-gianism' ); ?></h4>

<p><?php esc_html_e( 'If you don\'t have LINE account, create new one.', 'wp-gianism' ); ?></p>
<p><?php _e( 'Go to <a href="https://developers.line.me/">LINE developer</a> and click <code>Start LINE login</code>.', 'wp-gianism' ); ?></p>
<p><?php esc_html_e( 'Then register new client. Enter required information.', 'wp-gianism' ); ?></p>

<table class="gianism-example-table">
	<tr>
		<th><?php esc_html_e( 'Provider Name', 'wp-gianism' ); ?></th>
		<td>
			<?php
			// translators: %s is site name.
			printf( __( 'Your service or company name. e.g. <code>%s</code>', 'wp-gianism' ), esc_html( get_bloginfo( 'name' ) ) );
			?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Application Name', 'wp-gianism' ); ?></th>
		<td>
			<?php
			// translators: %s is site name.
			printf( __( 'Your application name. Site name <code>%s</code> is clear for your user.', 'wp-gianism' ), esc_html( get_bloginfo( 'name' ) ) );
			?>
		</td>
	</tr>
	<tr>
		<th><?php esc_html_e( 'Redirect URI', 'wp-gianism' ); ?></th>
		<td>
			<code>
				<?php
				echo esc_html( home_url( "/{$instance->url_prefix}/", $this->option->is_ssl_required() ? 'https' : 'http' ) );
				?>
			</code>
		</td>
	</tr>
</table>

<h4>Step2. <?php $this->e( 'Enter API Information' ); ?></h4>

<p><?php printf( $this->_( 'Save credentials on <a href="%s">WordPress admin screen</a>.' ), $this->setting_url() ); ?></p>
