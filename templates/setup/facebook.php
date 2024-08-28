<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */
/** @var \Gianism\Service\Facebook $instance */

?>
<h3><i class="lsf lsf-facebook"></i> Facebook</h3>

<p class="description"><?php $this->e( 'First of all, Facebook account is required. If you don\'t have one, create it at <a href="https://www.facebook.com">facebook.com</a>' ); ?></p>

<h4>Step1. <?php $this->e( 'Create App on Facebook developers' ); ?></h4>

<p><?php $this->e( 'Gianism refers user\'s Facebook credential as Facebook app. Go to <a href="https://developers.facebook.com">Facebook developers</a> and sign up as developer. On registeration flow, you will have to enter mobile phone number or credit card number.' ); ?></p>

<p><?php printf( $this->_( 'Authenticated as developer, go to <a href="https://developers.facebook.com/apps">Apps</a> and create new App. You can enter name as you like, but the same name as WordPress site name <code>%s</code> is recommended for usability.' ), get_bloginfo( 'name' ) ); ?></p>

<p><?php $this->e( 'Though App credential is available for various usage(iOS app, Android app, Page tab, etc), minimum requirments are like below.' ); ?></p>

<table class="gianism-example-table">
	<tr>
		<th>Display Name</th>
		<td>
			<?php $this->e( 'App name. Blog name is recommended. User will see this name on authentication screen.' ); ?>
			<br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), esc_html( get_bloginfo( 'name' ) ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Namespace</th>
		<td>
			<?php $this->e( 'Unique string which identifies your app.' ); ?><br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), esc_html( $_SERVER['HTTP_HOST'] ) ); ?>
		</td>
	</tr>
	<tr>
		<th><?php $this->e( 'Contact mail address' ); ?></th>
		<td>
			<?php $this->e( 'Contact mail address. It will be displayed on authentication screen on Facebook.' ); ?>
			<br/>
			<?php
			// translators: %s is email
			printf( __( '<strong>e.g.</strong> <code>%s</code>', 'wp-gianism' ), esc_html( $this->option->get( 'admin_email' ) ) );
			?>
		</td>
	</tr>
	<tr>
		<th>App Domains</th>
		<td>
			<?php $this->e( 'Your domain.' ); ?><br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), esc_html( $_SERVER['HTTP_HOST'] ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Sandbox Mode</th>
		<td>
			<?php $this->e( 'On development environment or test flight, turn sandbox ON.' ); ?><br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), esc_html__( 'OFF', 'wp-gianism' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Type</th>
		<td>
			<?php $this->e( 'You can activate several connection types, but below is required.' ); ?><br/>
			<code>Website with Facebook Login</code>
		</td>
	</tr>
	<tr>
		<th>Valid redirect URIs</th>
		<td>
			<?php $this->e( 'The URL where user will be redirected after authentication.' ); ?>
			<br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), esc_url( home_url( "/{$instance->url_prefix}/", $this->option->is_ssl_required() ? 'https' : 'http' ) ) ); ?>
		</td>
	</tr>
</table>

<h4>Step2. <?php $this->e( 'Enter App ID and App secret' ); ?></h4>

<p><?php printf( $this->_( 'Once you create Facebook App, App ID and App secret will be provided. Now you can enter it on <a href="%s">WordPress Admin screen</a>.' ), $this->setting_url() ); ?></p>
