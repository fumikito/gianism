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
			<?php $this->switch_button( 'fb_enabled', $this->option->is_enabled( 'facebook' ), 1 ) ?>
			<p class="description">
				<?php
				printf(
					$this->_( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.' ),
					'Facebook',
					'https://developers.facebook.com/apps'
				);
				printf(
					$this->_( 'See detail at <a href="%1$s">%2$s</a>.' ),
					$this->setting_url( 'setup' ),
					$this->_( 'How to set up' )
				);
				?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="fb_app_id"><?php $this->e( 'App ID' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fb_app_id" id="fb_app_id"
			       value="<?php echo esc_attr( $instance->fb_app_id ) ?>"/>
		</td>
	</tr>
	<tr>
		<th><label for="fb_app_secret"><?php $this->e( 'App Secret' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fb_app_secret" id="fb_app_secret"
			       value="<?php echo esc_attr( $instance->fb_app_secret ) ?>"/>
		</td>
	</tr>
	<tr>
		<th><label for="fb_version"><?php $this->e( 'API Version' ); ?></label></th>
		<td>
			<input type="text" class="regular-text" name="fb_version" id="fb_version"
			       value="<?php echo esc_attr( $instance->fb_version ) ?>"/>
			<p class="description">
				<?php
				$this->new_from( '3.0.0' );
				$this->e( 'Enter Facebook API version for your app. Facebook API\'s life cycle is 2 years. Format should be v*.*. e.g. <code>v2.8</code>' );
				?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php $this->e( 'Use Facebook API' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'fb_use_api', $instance->fb_use_api ) ?>
			<p class="description">
				<?php
				$this->new_from( '2.2' );
				$this->e( 'If enabled, you can get Facebook API Token for this site.' );
				?>
			</p>
			<?php if ( $instance->fb_use_api ) : ?>
				<p class="notice">
					<?php printf( $this->_( 'You must set up token on <a href="%s">Facebook API page</a>.' ), $this->setting_url( 'fb-api' ) ) ?>
				</p>
			<?php endif; ?>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
