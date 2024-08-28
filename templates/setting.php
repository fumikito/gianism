<?php

defined( 'ABSPATH' ) or die();

global $wp_version;

/** @var \Gianism\UI\SettingScreen $this */
?>

<form method="post">

	<?php wp_nonce_field( 'gianism_option' ); ?>

	<h3><i class="lsf lsf-gear"></i> <?php esc_html_e( 'General Setting', 'wp-gianism' ); ?></h3>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Current registration setting', 'wp-gianism' ); ?></th>
			<td>
				<p>
					<?php if ( $this->option->user_can_register() ) : ?>
						<i class="lsf lsf-check" style="color: green; font-size: 1.4em;"></i>
						<strong><?php esc_html_e( 'User can register account.', 'wp-gianism' ); ?></strong>
					<?php else : ?>
						<i class="lsf lsf-ban" style="color: lightgrey; font-size: 1.4em;"></i>
						<strong><?php esc_html_e( 'User can\'t register account.', 'wp-gianism' ); ?></strong>
					<?php endif; ?>
				</p>
				<p>
					<label>
						<input type="radio" name="force_register"
							value="1"<?php checked( $this->option->force_register ); ?> />
						<?php esc_html_e( 'Force register', 'wp-gianism' ); ?>
					</label><br/>
					<label>
						<input type="radio" name="force_register"
							value="0"<?php checked( ! $this->option->force_register ); ?> />
						<?php esc_html_e( 'Depends on WP setting', 'wp-gianism' ); ?>
					</label>
				</p>
				<p class="description">
					<?php
					// translators: %s is link to general setting.
					echo wp_kses_post( sprintf( __( 'Whether registration setting depends on <a href="%s">General setting</a>. If users are allowed to register, account will be created with information provided from Web service, or else only connected users can login via SNS account.', 'wp-gianism' ), admin_url( 'options-general.php' ) ) );
					?>
				</p>
				<?php if ( gianism_woocommerce_detected() ) : ?>
				<p class="description" style="color: #f34357;">
					<?php echo wp_kses( __( '<strong>WooCommerce found!</strong> In spite of this setting, user can always register account.', 'wp-gianism' ), [ 'strong' => [] ] ); ?>
				</p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Login screen', 'wp-gianism' ); ?></th>
			<td>
				<label>
					<input type="radio" name="show_button_on_login"
						value="1"<?php checked( $this->option->show_button_on_login ); ?> />
					<?php esc_html_e( 'Show all button on Login screen.', 'wp-gianism' ); ?>
				</label><br/>
				<label>
					<input type="radio" name="show_button_on_login"
						value="0"<?php checked( ! $this->option->show_button_on_login ); ?> />
					<?php esc_html_e( 'Do not show login button.', 'wp-gianism' ); ?>
				</label>
				<p class="description">
					<?php
					// translators: %1$s is link title, %2$s is link to document.
					echo wp_kses_post( sprintf( __( 'You can output login button manually. See detail at <a href="%2$s">%1$s</a>.', 'wp-gianism' ), esc_html__( 'Customize', 'wp-gianism' ), $this->setting_url( 'customize' ) ) );
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="button_type"><?php esc_html_e( 'Button size', 'wp-gianism' ); ?></label></th>
			<td>
				<select name="button_type" id="button_type">
					<?php foreach ( $this->option->button_types() as $index => $value ) : ?>
						<option value="<?php echo esc_attr( $index ); ?>"<?php selected( $this->option->button_type, $index ); ?>>
							<?php echo esc_html( $value ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php esc_html_e( 'This setting is valid only if login button\'s display setting is on.', 'wp-gianism' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="prefix"><?php esc_html_e( 'URL Prefix', 'wp-gianism' ); ?></label></th>
			<td>
				<input type="text" value="<?php echo esc_attr( $this->option->prefix ); ?>" name="prefix" id="prefix" />
				<p class="description">
					<?php $this->new_from( '4.0.0' ); ?>
					<?php esc_html_e( 'You can add prefix for all Gianism redirect URIs. Useful if you site is under CDN network which filters cookie strings.', 'wp-gianism' ); ?>
					<br />
					<?php echo wp_kses_post( __( '<strong>NOTICE: </strong>if you change URL prefix, you have to change all app setting in each SNS. It might cause error on live site.', 'wp-gianism' ) ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="wpg-check-profile"><?php esc_html_e( 'Profile Completion', 'wp-gianism' ); ?></label></th>
			<td>
				<?php $this->new_from( '4.1.0' ); ?>
				<select name="check_profile" id="wpg-check-profile">
					<?php
					foreach ( [
						''         => __( 'Notify on admin profile screen(Default)', 'wp-gianism' ),
						'popup'    => __( 'Show pop up for incomplete users(Aggressive)', 'wp-gianism' ),
						'redirect' => __( 'Redirect users to fill profile(Forcible)', 'wp-gianism' ),
					] as $value => $label ) {
						printf( '<option value="%1$s"%3$s>%2$s</option>', esc_attr( $value ), esc_html( $label ), selected( $value, $this->option->check_profile, false ) );
					}
					?>
				</select>
				<p class="description">
					<?php echo wp_kses_post( __( 'Users who registered account via SNS have sometimes incomplete profile. You can choose how to treat them by this option.', 'wp-gianism' ) ); ?>
					<?php if ( ! $this->profile_checker->is_over_5() ) : ?>
						<br /><strong style="color: red"><?php esc_html_e( 'This feature requires WordPress 5.0 and higher. Please consider upgrading.', 'wp-gianism' ); ?></strong>
					<?php endif; ?>
				</p>

				<p class="gianism-toggle" data-target="#wpg-check-profile" data-valid="popup,redirect">
					<label for="profile-completion-path"><?php esc_html_e( 'Profile URL', 'wp-gianism' ); ?></label><br />
					<input name="profile_completion_path" class="regular-text" type="text" id="profile-completion-path"
						value="<?php echo esc_attr( $this->option->profile_completion_path ); ?>"
						placeholder="<?php esc_attr_e( 'e.g. /my-profile/account', 'wp-gianism' ); ?>" />
					<br />
					<span class="description">
						<?php esc_html_e( 'If you want your users to complete their profiles, you can specify the path to profile completion page.', 'wp-gianism' ); ?><br />
						<strong><?php esc_html_e( 'Current Setting', 'wp-gianism' ); ?></strong>:
						<?php
						printf(
							'<%1$s>%2$s</%1$s>',
							$this->option->check_profile ? 'code' : 'del',
							esc_url( \Gianism\Controller\ProfileChecker::get_instance()->redirect_url() )
						);
						?>
					</span>
				</p>
				<p class="gianism-toggle" data-target="#wpg-check-profile" data-valid="redirect">
					<label for="exclude-from-redirect"><?php esc_html_e( 'Excluded Path', 'wp-gianism' ); ?></label><br />
					<textarea name="exclude_from_redirect" id="exclude-from-redirect" style="width: 100%; box-sizing: border-box" placeholder="e.g. /my-account"><?php echo esc_textarea( $this->option->exclude_from_redirect ); ?></textarea>
					<br />
					<span class="description">
						<?php esc_html_e( 'To avoid the redirect loop, enter paths excluded from redirection. 1 path in 1 line. Asterisk(*) means wildcard.', 'wp-gianism' ); ?>
					</span>
				</p>
			</td>
		</tr>
	</table>
	<?php submit_button(); ?>


<?php
foreach ( $this->service->all_services() as $service ) {
	/** @var \Gianism\Service\AbstractService $instance */
	$instance = $this->service->get( $service );
	$path     = $instance->get_admin_template( 'setting' );
	if ( $path && file_exists( $path ) ) {
		include $path;
	}
}
?>

</form>
