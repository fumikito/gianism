<?php
defined( 'ABSPATH' ) or die();
/** @var \Gianism\UI\Screen $this *
/** @var \Gianism\Service\Google $instance */
?>

<h3><i class="lsf lsf-google"></i> Google</h3>
<table class="form-table">
	<tbody>
	<tr>
		<th><label><?php printf( $this->_( 'Connect with %s' ), 'Google' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'ggl_enabled', $this->option->is_enabled( 'google' ), 1 ) ?>
			<p class="description">
				<?php printf( $this->_( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.' ), "Google API Console", "https://code.google.com/apis/console" ); ?>
				<?php printf( $this->_( 'See detail at <a href="%1$s">%2$s</a>.' ), $this->setting_url( 'setup' ), $this->_( 'How to set up' ) ); ?>
			</p>
		</td>
	</tr>

	<tr>
		<th><label for="ggl_consumer_key"><?php $this->e( 'Client ID' ); ?></label></th>
		<td><input class="regular-text" type="text" name="ggl_consumer_key" id="ggl_consumer_key"
		           value="<?php echo esc_attr( $instance->ggl_consumer_key ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="ggl_consumer_secret"><?php $this->e( 'Client Secret' ); ?></label></th>
		<td><input class="regular-text" type="text" name="ggl_consumer_secret" id="ggl_consumer_secret"
		           value="<?php echo esc_attr( $instance->ggl_consumer_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="ggl_redirect_uri"><?php $this->e( 'Redirect URI' ); ?></label></th>
		<td>
			<p class="description">
				<?php
				$end_point = home_url( '/google-auth/', ( $this->option->is_ssl_required() ? 'https' : 'http' ) );
				printf(
					$this->_( 'Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.' ),
					$this->_( 'Redirect URI' ),
					'<code>' . $end_point . '</code>',
					'Google API Console',
					'https://code.google.com/apis/console'
				);
				?>
				<a class="button" href="<?php echo esc_attr( $end_point ) ?>"
				   onclick="window.prompt('<?php $this->e( 'Please copy this URL.' ) ?>', this.href); return false;"><?php $this->e( 'Copy' ) ?></a>
				<br/>
				<?php printf( $this->_( '<strong>Notice: </strong> Setting is changed on <code>Gianims v2.0</code>. You must set up again on Google API Console.' ) ) ?>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
