<?php
defined( 'ABSPATH' ) or die();
/** @var \Gianism\UI\Screen $this */
/** @var \Gianism\Service\Line $instance */
?>

<h3><i class="lsf lsf-line"></i> LINE</h3>
<table class="form-table">
	<tbody>
	<tr>
		<th><label><?php printf( __( 'Connect with %s', 'wp-gianism' ), 'LINE' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'line_enabled', $this->option->is_enabled( 'line'), 'line_enabled' ) ?>
			<label>
			<p class="description">
				<?php printf( __( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required information.', 'wp-gianism' ), "LINE", "https://developers.line.me/" ); ?>
				<?php printf( __( 'See detail at <a href="%1$s">%2$s</a>.', 'wp-gianism' ), $this->setting_url( 'setup' ), __( 'How to set up', 'wp-gianism' ) ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="line_channel_id"><?php _e( 'Channel ID', 'wp-gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="line_channel_id" id="line_channel_id"
		           value="<?php echo esc_attr( $instance->line_channel_id ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="line_channel_secret"><?php _e( 'Channel Secret', 'wp-gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="line_channel_secret" id="line_channel_secret"
		           value="<?php echo esc_attr( $instance->line_channel_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="ggl_redirect_uri"><?php _e( 'Redirect URI', 'wp-gianism' ); ?></label></th>
		<td>
			<p class="description">
				<?php
				$end_point = home_url( '/' . $instance->url_prefix . '/', ( $this->option->is_ssl_required() ? 'https' : 'http' ) );
				printf(
					__( 'Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.', 'wp-gianism' ),
					__( 'Callback URL', 'wp-gianism' ),
					'<code>' . $end_point . '</code>',
					'LINE Developers',
					'https://developers.line.me/console/'
				);
				?>
				<a class="button" href="<?php echo esc_attr( $end_point ) ?>"
				   onclick="window.prompt('<?php _e( 'Please copy this URL.', 'wp-gianism' ) ?>', this.href); return false;"><?php _e( 'Copy', 'wp-gianism' ) ?></a>
			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php esc_html_e( 'Retrieve Email', 'wp-gianism' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'line_retrieve_email', $instance->line_retrieve_email, 1 ) ?>
			<label>
			<p class="description">
				<?php printf( __( 'You need registration to retrieve user email. Go to <a target="_blank" href="%1$s">LINE developer</a> and submit request.', 'wp-gianism' ), 'https://developers.line.me/' ); ?>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
