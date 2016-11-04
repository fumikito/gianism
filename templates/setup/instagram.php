<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */
/** @var \Gianism\Service\Instagram $instance */

?>

<h3><i class="lsf lsf-instagram"></i> Instagram</h3>

<p class="description"><?php $this->e( 'First of all, Instagram account is required.' ) ?>

<h4>Step1. <?php $this->e( 'Create new Project' ); ?></h4>

<p><?php $this->e( 'Go to <a href="https://www.instagram.com/developer/">Instagram developer</a> and click <code>Manage Clients</code>.' ); ?></p>

<p><?php $this->e( 'Then register new client. Enter required information.' ); ?></p>


<table class="gianism-example-table">
	<tr>
		<th>Application Name</th>
		<td>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), get_bloginfo( 'name' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Description</th>
		<td>
			<?php $this->e( 'Write what this app will do. This information will be displayed to user.' ); ?>
			<br/>
		</td>
	</tr>
	<tr>
		<th>Website</th>
		<td>
			<?php $this->e( 'Website\'s URL. Use this blog\'s URL' ); ?><br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), home_url( '/' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Valid redirect URIs</th>
		<td>
			<?php $this->e( 'The URL where user will be redirected after authentication.' ); ?>
			<br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), home_url( "/{$instance->url_prefix}/", $this->option->is_ssl_required() ? 'https' : 'http' ) ); ?>
		</td>
	</tr>
</table>

<p><?php printf( $this->_( 'Now you can get client\'s credentials. Save it on <a href="%s">setting page</a>.' ), $this->setting_url( 'setting' ) ) ?></p>

<h4>Step2. <?php $this->e( 'Get out of sandbox' ); ?></h4>

<p><?php printf( $this->_( 'Input and save 2 information(Client ID and Client Secret) on <a href="%s">WordPress admin screen</a>.' ), $this->setting_url() ); ?></p>

<p><?php printf( $this->_( 'By default, any instagram client is inside sandbox. This means that the instagram account which can log in to your site is just yours! So, to use it in production environment, you have to pass <a href="%s">Permissions Review</a>. If you just need login, <code>basic</code> permission satisfies.' ), 'https://www.instagram.com/developer/review/' ); ?></p>
