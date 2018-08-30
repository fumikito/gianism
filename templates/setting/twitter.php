<?php
defined( 'ABSPATH' ) or die();
/** @var \Gianism\UI\Screen $this */
/** @var \Gianism\Service\Twitter $instance */
?>

<h3><i class="lsf lsf-twitter"></i> Twitter</h3>
<table class="form-table">
	<tbody>
	<tr>
		<th><label><?php printf( $this->_( 'Connect with %s' ), 'Twitter' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'tw_enabled', $this->option->is_enabled( 'twitter'), 'tw_enabled' ) ?>
			<label>
			<p class="description">
				<?php printf( __( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required information.', 'gianism' ), "Twitter", "https://dev.twitter.com/apps" ); ?>
				<?php printf( __( 'See detail at <a href="%1$s">%2$s</a>.', 'gianism' ), $this->setting_url( 'setup' ), __( 'How to set up', 'gianism' ) ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="tw_screen_name"><?php _e( 'Screen Name', 'gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_screen_name" id="tw_screen_name"
		           value="<?php echo esc_attr( $instance->tw_screen_name ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_consumer_key"><?php _e( 'Consumer Key', 'gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_consumer_key" id="tw_consumer_key"
		           value="<?php echo esc_attr( $instance->tw_consumer_key ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_consumer_secret"><?php _e( 'Consumer Secret', 'gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_consumer_secret" id="tw_consumer_secret"
		           value="<?php echo esc_attr( $instance->tw_consumer_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_access_token"><?php _e( 'Access Token', 'gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_access_token" id="tw_access_token"
		           value="<?php echo esc_attr( $instance->tw_access_token ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_access_token_secret"><?php _e( 'Access token secret', 'gianism' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_access_token_secret" id="tw_access_token_secret"
		           value="<?php echo esc_attr( $instance->tw_access_token_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="ggl_redirect_uri"><?php _e( 'Redirect URI', 'gianism' ); ?></label></th>
		<td>
			<p class="description">
				<?php
				$end_point = home_url( '/twitter/', ( $this->option->is_ssl_required() ? 'https' : 'http' ) );
				printf(
					__( 'Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.', 'gianism' ),
					__( 'Callback URL', 'gianism' ),
					'<code>' . $end_point . '</code>',
					'Twitter Developers',
					'https://dev.twitter.com/apps'
				);
				?>
				<a class="button" href="<?php echo esc_attr( $end_point ) ?>"
				   onclick="window.prompt('<?php _e( 'Please copy this URL.', 'gianism' ) ?>', this.href); return false;"><?php _e( 'Copy', 'gianism' ) ?></a>
			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Twitter bot by Gianism', 'gianism' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'tw_use_cron', $instance->tw_use_cron ) ?>
			<p class="description">
				<?php $this->new_from( '2.2' ) ?>
				<?php printf( __( 'If enabled, you can make twitter bot which tweet at the time you specified. %1$s, %2$s, %3$s, %4$s, and %5$s are required.', 'gianism' ),
					__( 'Screen Name', 'gianism' ), __( 'Consumer Key', 'gianism' ), __( 'Consumer Secret', 'gianism' ),
					__( 'Access Token', 'gianism' ), __( 'Access token secret', 'gianism' ) ) ?>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
