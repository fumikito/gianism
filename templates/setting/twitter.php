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
				<?php printf( $this->_( 'You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.' ), "Twitter", "https://dev.twitter.com/apps" ); ?>
				<?php printf( $this->_( 'See detail at <a href="%1$s">%2$s</a>.' ), $this->setting_url( 'setup' ), $this->_( 'How to set up' ) ); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label for="tw_screen_name"><?php $this->e( 'Screen Name' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_screen_name" id="tw_screen_name"
		           value="<?php echo esc_attr( $instance->tw_screen_name ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_consumer_key"><?php $this->e( 'Consumer Key' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_consumer_key" id="tw_consumer_key"
		           value="<?php echo esc_attr( $instance->tw_consumer_key ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_consumer_secret"><?php $this->e( 'Consumer Secret' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_consumer_secret" id="tw_consumer_secret"
		           value="<?php echo esc_attr( $instance->tw_consumer_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_access_token"><?php $this->e( 'Access Token' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_access_token" id="tw_access_token"
		           value="<?php echo esc_attr( $instance->tw_access_token ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="tw_access_token_secret"><?php $this->e( 'Access token secret' ); ?></label></th>
		<td><input class="regular-text" type="text" name="tw_access_token_secret" id="tw_access_token_secret"
		           value="<?php echo esc_attr( $instance->tw_access_token_secret ) ?>"/></td>
	</tr>
	<tr>
		<th><label for="ggl_redirect_uri"><?php $this->e( 'Redirect URI' ); ?></label></th>
		<td>
			<p class="description">
				<?php
				$end_point = home_url( '/twitter/', ( $this->option->is_ssl_required() ? 'https' : 'http' ) );
				printf(
					$this->_( 'Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.' ),
					$this->_( 'Callback URL' ),
					'<code>' . $end_point . '</code>',
					'Twitter Developers',
					'https://dev.twitter.com/apps'
				);
				?>
				<a class="button" href="<?php echo esc_attr( $end_point ) ?>"
				   onclick="window.prompt('<?php $this->e( 'Please copy this URL.' ) ?>', this.href); return false;"><?php $this->e( 'Copy' ) ?></a>
			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php $this->e( 'Twitter bot by Gianism' ); ?></label></th>
		<td>
			<?php $this->switch_button( 'tw_use_cron', $instance->tw_use_cron ) ?>
			<p class="description">
				<?php $this->new_from( '2.2' ) ?>
				<?php printf( $this->_( 'If enabled, you can make twitter bot which tweet at the time you specified. %1$s, %2$s, %3$s, %4$s, and %5$s are required.' ),
					$this->_( 'Screen Name' ), $this->_( 'Consumer Key' ), $this->_( 'Consumer Secret' ),
					$this->_( 'Access Token' ), $this->_( 'Access token secret' ) ) ?>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<?php submit_button(); ?>
