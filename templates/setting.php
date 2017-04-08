<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */
?>

<form method="post">

	<?php wp_nonce_field( 'gianism_option' ) ?>

	<h3><i class="lsf lsf-gear"></i> <?php $this->e( 'General Setting' ) ?></h3>
	<table class="form-table">
		<tr>
			<th><?php $this->e( 'Current registration setting' ) ?></th>
			<td>
				<p>
					<?php if ( $this->option->user_can_register() ) : ?>
						<i class="lsf lsf-check" style="color: green; font-size: 1.4em;"></i>
						<strong><?php _e( 'User can register account.', 'wp-gianism' ) ?></strong>
					<?php else : ?>
						<i class="lsf lsf-ban" style="color: lightgrey; font-size: 1.4em;"></i>
						<strong><?php _e( 'User can\'t register account.', 'wp-gianism' ) ?></strong>
					<?php endif; ?>
				</p>
				<p>
					<label>
						<input type="radio" name="force_register"
						       value="1"<?php checked( $this->option->force_register ) ?> />
						<?php $this->e( 'Force register' ); ?>
					</label><br/>
					<label>
						<input type="radio" name="force_register"
						       value="0"<?php checked( ! $this->option->force_register ) ?> />
						<?php $this->e( 'Depends on WP setting' ); ?>
					</label>
				</p>
				<p class="description"><?php printf( $this->_( 'Whether registration setting depends on <a href="%s">General setting</a>. If users are allowed to register, account will be created with information provided from Web service, or else only connected users can login via SNS account.' ), admin_url( 'options-general.php' ) ) ?></p>
				<?php if ( gianism_woocommerce_detected() ) : ?>
				<p class="description" style="color: #f34357;">
					<?php echo wp_kses( __( '<strong>WooCommerce found!</strong> In spite of this setting, user can always register account.', 'wp-gianism' ), [ 'strong' => [] ] ) ?>
				</p>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<th><?php $this->e( 'Login screen' ); ?></th>
			<td>
				<label>
					<input type="radio" name="show_button_on_login"
					       value="1"<?php checked( $this->option->show_button_on_login ) ?> />
					<?php $this->e( 'Show all button on Login screen.' ); ?>
				</label><br/>
				<label>
					<input type="radio" name="show_button_on_login"
					       value="0"<?php checked( ! $this->option->show_button_on_login ) ?> />
					<?php $this->e( 'Do not show login button.' ); ?>
				</label>
				<p class="description">
					<?php printf( $this->_( 'You can output login button manually. See detail at <a href="%2$s">%1$s</a>.' ), $this->_( 'Customize' ), $this->setting_url( 'customize' ) ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="button_type"><?php $this->e( 'Button size' ); ?></label></th>
			<td>
				<select name="button_type" id="button_type">
					<?php foreach ( $this->option->button_types() as $index => $value ) : ?>
						<option value="<?php echo $index ?>"<?php selected( $index == $this->option->button_type ) ?>>
							<?php echo esc_html( $value ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description">
					<?php $this->e( 'This setting is valid only if login button\'s display setting is on.' ); ?>
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
