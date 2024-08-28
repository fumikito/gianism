<?php
defined( 'ABSPATH' ) or die();
/** @var \Gianism\UI\Screen $this */
/** @var \Gianism\Service\Facebook $instance */
?>
<h3><i class="lsf lsf-facebook"></i> Facebook</h3>
<table class="form-table">
	<tbody>
	<tr>
		<th><label><?php $this->e( 'Connect with Facebook' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'fb_enabled', $this->option->is_enabled( 'facebook' ), 1 ); ?>
			<p class="description">
				<?php
				echo wp_kses_post(
					sprintf(
					// translators: %1$s is service name, %2$s is URL.
						__( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.', 'wp-gianism' ),
						'Facebook',
						'https://developers.facebook.com/apps'
					)
				);
				echo wp_kses_post(
					sprintf(
					// translators: %1$s is URL, %2$s is label.
						__( 'See detail at <a href="%1$s">%2$s</a>.', 'wp-gianism' ),
						esc_url( $this->setting_url( 'setup' ) ),
						esc_html__( 'How to set up', 'wp-gianism' )
					)
				);
				?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="fb_app_id"><?php $this->e( 'App ID' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fb_app_id" id="fb_app_id"
					value="<?php echo esc_attr( $instance->fb_app_id ); ?>"/>
		</td>
	</tr>
	<tr>
		<th><label for="fb_app_secret"><?php $this->e( 'App Secret' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fb_app_secret" id="fb_app_secret"
					value="<?php echo esc_attr( $instance->fb_app_secret ); ?>"/>
		</td>
	</tr>
	<tr>
		<th><label for="fb_version"><?php $this->e( 'API Version' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fb_version" id="fb_version" placeholder="<?php echo esc_attr( $instance->minimum_api_version ); ?>"
					value="<?php echo esc_attr( $instance->fb_version ); ?>"/>
			<p class="description">
				<?php
				$this->new_from( '3.0.0' );
				// translators: %s is version string.
				echo wp_kses_post( sprintf( __( 'Enter Facebook API version for your app. Facebook API\'s life cycle is 2 years. Format should be <code>v0.0</code>. Less than minimum version <code>%s</code> will be ignored.', 'wp-gianism' ), $instance->minimum_api_version ) );
				?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="fb_redirect_uri"><?php esc_html_e( 'Redirect URI', 'wp-gianism' ); ?></label></th>
		<td>
			<?php $end_point = $instance->get_redirect_endpoint(); ?>
			<input type="text" class="regular-text" name="fb_redirect_uri" id="fb_redirect_uri" readonly
					value="<?php echo esc_attr( $end_point ); ?>"/>
			<a class="button" href="<?php echo esc_attr( $end_point ); ?>"
				onclick="window.prompt('<?php esc_attr_e( 'Please copy this URL.', 'wp-gianism' ); ?>', this.href); return false;"><?php $this->e( 'Copy' ); ?></a>
			<p class="description">
				<?php
				$this->new_from( '3.0.9' );
				$setting_uri = 'https://developers.facebook.com/apps/' . $instance->fb_app_id . '/fb-login/settings/';
				echo wp_kses_post(
					sprintf(
					// translators: %1$s is fb URL, %2$s blog post URL.
						__( 'Since March 2018, Facebook changes redirect URIs policy and you must set URI above as <a href="%1$s" target="_blank">Valid OAuth Redirect URIs</a>. For more detail, see our <a href="%2$s" target="_blank">blog post</a>.', 'wp-gianism' ),
						esc_attr( $setting_uri ),
						'https://gianism.info/2018/03/23/failed-facebook-login-since-2018/'
					)
				);
				?>

			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php $this->e( 'Use Facebook API' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'fb_use_api', $instance->fb_use_api ); ?>
			<p class="description">
				<?php
				$this->new_from( '2.2' );
				esc_html_e( 'If enabled, you can get Facebook API Token for this site.', 'wp-gianism' );
				?>
			</p>
			<?php if ( $instance->fb_use_api ) : ?>
				<p class="notice">
					<?php
					echo wp_kses_post(
						sprintf(
							// translators: %s is URL.
							__( 'You must set up token on <a href="%s">Facebook API page</a>.', 'wp-gianism' ),
							$this->setting_url( 'fb-api' )
						)
					);
					?>
				</p>
			<?php endif; ?>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
