<?php
defined( 'ABSPATH' ) or die();
/** @var \Gianism\UI\Screen $this */
/** @var \Gianism\Service\Instagam $instance */
?>

<h3><i class="lsf lsf-instagram"></i> Instagram</h3>
<table class="form-table">
	<tbody>
	<tr>
		<th><label><?php printf( $this->_( 'Connect with %s' ), 'Instagram' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'instagram_enabled', $this->option->is_enabled( 'instagram' ), 1 ) ?>
			<p class="description">
				<?php printf(
					$this->_( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.' ),
					'Instagram Developer',
					'https://www.instagram.com/developer/'
				); ?>
				<?php printf(
					$this->_( 'See detail at <a href="%1$s">%2$s</a>.' ),
					$this->setting_url( 'setup' ),
					$this->_( 'How to set up' )
				); ?>
			</p>
		</td>
	</tr>

	<tr>
		<th><label for="instagram_client_id"><?php $this->e( 'Client ID' ); ?></label></th>
		<td><input class="regular-text" type="text" name="instagram_client_id" id="instagram_client_id"
		           value="<?php echo esc_attr( $instance->instagram_client_id ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="instagram_client_secret"><?php $this->e( 'Client Secret' ); ?></label></th>
		<td><input class="regular-text" type="text" name="instagram_client_secret" id="instagram_client_secret"
		           value="<?php echo esc_attr( $instance->instagram_client_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="ggl_redirect_uri"><?php $this->e( 'Redirect URI' ); ?></label></th>
		<td>
			<p class="description">
				<?php
				$end_point = home_url( '/instagram-auth/', ( $this->option->is_ssl_required() ? 'https' : 'http' ) );
				printf(
					$this->_( 'Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.' ),
					$this->_( 'Redirect URI' ),
					'<code>' . $end_point . '</code>',
					'Instagram Developer',
					'https://www.instagram.com/developer/clients/manage/'
				);
				?>
				<a class="button" href="<?php echo esc_attr( $end_point ) ?>"
				   onclick="window.prompt('<?php $this->e( 'Please copy this URL.' ) ?>', this.href); return false;"><?php $this->e( 'Copy' ) ?></a>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
