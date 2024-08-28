<?php
defined( 'ABSPATH' ) or die();
/** @var \Gianism\UI\Screen $this */
/** @var \Gianism\Service\Line $instance */
?>

<h3><i class="lsf lsf-line"></i> LINE</h3>
<table class="form-table">
	<tbody>
	<tr>
		<th>
			<label>
				<?php
				// translators: %s is service name.
				printf( __( 'Connect with %s', 'wp-gianism' ), 'LINE' );
				?>
			</label>
		</th>
		<td>
			<?php $this->switch_button( 'line_enabled', $this->option->is_enabled( 'line' ), 'line_enabled' ); ?>
			<p class="description">
				<?php
				// translators: %1$s is service name, %2$s is link to LINE developer page.
				printf( __( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required information.', 'wp-gianism' ), 'LINE', 'https://developers.line.me/' );
				// translators: %1$s is Link label, %2$s is link to the page.
				printf( __( 'See detail at <a href="%1$s">%2$s</a>.', 'wp-gianism' ), $this->setting_url( 'setup' ), __( 'How to set up', 'wp-gianism' ) );
				?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="line_channel_id"><?php _e( 'Channel ID', 'wp-gianism' ); ?></label></th>
		<td>
			<input class="regular-text" type="text" name="line_channel_id" id="line_channel_id"
				value="<?php echo esc_attr( $instance->line_channel_id ); ?>"/>
		</td>
	</tr>
	<tr>
		<th><label for="line_channel_secret"><?php _e( 'Channel Secret', 'wp-gianism' ); ?></label></th>
		<td>
			<input class="regular-text" type="text" name="line_channel_secret" id="line_channel_secret"
				value="<?php echo esc_attr( $instance->line_channel_secret ); ?>"/>
		</td>
	</tr>
	<tr>
		<th><label for="ggl_redirect_uri"><?php _e( 'Redirect URI', 'wp-gianism' ); ?></label></th>
		<td>
			<p class="description">
				<?php
				$end_point = esc_url( $instance->get_redirect_endpoint() );
				printf(
					// translators: %1$s is callback URL, %2$s is link to LINE developer page, %3$s is link label, %4$s is link to LINE developer console.
					__( 'Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.', 'wp-gianism' ),
					__( 'Callback URL', 'wp-gianism' ),
					'<code>' . $end_point . '</code>',
					'LINE Developers',
					'https://developers.line.me/console/'
				);
				?>
				<a class="button" href="<?php echo esc_attr( $end_point ); ?>"
					onclick="window.prompt('<?php _e( 'Please copy this URL.', 'wp-gianism' ); ?>', this.href); return false;"><?php _e( 'Copy', 'wp-gianism' ); ?></a>
			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php esc_html_e( 'Retrieve Email', 'wp-gianism' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'line_retrieve_email', $instance->line_retrieve_email, 1 ); ?>
			<p class="description">
				<?php
				// translators: %1$s is link to LINE developer page.
				printf( __( 'You need registration to retrieve user email. Go to <a target="_blank" href="%1$s">LINE developer</a> and submit request.', 'wp-gianism' ), 'https://developers.line.me/' );
				?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="line-add-friend-prompt"><?php esc_html_e( 'Add Friend', 'wp-gianism' ); ?></label></th>
		<td>
			<select id="line-add-friend-prompt" name="line_add_friend_prompt">
				<?php
				foreach ( [
					''           => __( 'Not display', 'wp-gianism' ),
					'normal'     => __( 'Display as option', 'wp-gianism' ),
					'aggressive' => __( 'Aggressive Prompt', 'wp-gianism' ),
				] as $value => $label ) :
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $instance->line_add_friend_prompt ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description">
				<?php
				// translators: %s is link to LINE developer page.
				echo wp_kses_post( sprintf( __( 'If you have an official account in LINE, you can display "Add friend button" in your connect screen. For more details, see <a href="%s" target="_blank" rel="noopener,noreferrer">LINE Developer</a>.', 'wp-gianism' ), _x( 'https://developers.line.biz/en/docs/line-login/link-a-bot/#getting-the-friendship-status-of-the-user-and-the-line-official-account', 'LINE DOC', 'wp-gianism' ) ) );
				?>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
