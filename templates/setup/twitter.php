<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */
/** @var \Gianism\Service\Google $instance */

?>
<h3><i class="lsf lsf-twitter"></i> Twitter</h3>

<p class="description"><?php $this->e( 'First of all, your twitter account is required. If you don\'t have one, create it at <a href="https://twitter.com">twitter</a>' ); ?></p>

<h4>Step1. <?php $this->e( 'Create twitter application' ); ?></h4>

<p><?php $this->e( 'Go to <a href="https://dev.twitter.com">Twitter Developer</a> and sign in with your twitter account. Then go to <a href="https://dev.twitter.com/apps">My Applications</a> and click <code>Create new application</code>.' ); ?></p>

<p><?php sprintf( $this->_( 'In Application detail section, enter application name(same as <code>%s</code> recommended), description and Website. If you are not bot, enter reCaptha and creation flow will be done :)' ), get_bloginfo( 'name' ) ); ?></p>

<p><?php $this->e( 'Now you have new application. Go to your new app\'s detail page. On <code>Settings</code> tab, various information can be editted. Besides the required informations, you had better to upload Application icon which will be displayed on authentication screen.' ); ?></p>


<table class="gianism-example-table">
	<tr>
		<th>Name</th>
		<td>
			<?php $this->e( 'App name. Blog name is recommended. User will see this name on authentication screen.' ); ?>
			<br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), get_bloginfo( 'name' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Description</th>
		<td>
			<?php $this->e( 'Description about your site. It is recommended explaining your user why you request authentication.' ); ?>
			<br/>
		</td>
	</tr>
	<tr>
		<th>Website</th>
		<td>
			<?php $this->e( 'Website\'s URL. Use this blog\'s URL' ); ?><br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), home_url( '/', 'http' ) ); ?>
		</td>
	</tr>
	<tr>
		<th>Callback URL</th>
		<td>
			<?php $this->e( 'The URL where user will be redirect after authentication. If you use other application which use twitter OAuth, leave this blank.' ); ?>
			<br/>
			<?php printf( $this->_( '<strong>e.g.</strong> <code>%s</code>' ), home_url( '/twitter/', $this->option->is_ssl_required() ? 'https' : 'http' ) ); ?>
		</td>
	</tr>
</table>

<p><?php $this->e( 'Take care of application access type. Default is <code>readonly</code> and it\'s sufficient. Users tend to avoid strong access like <code>Read and Write</code>.' ); ?></p>

<p><?php $this->e( 'Now you have finished setting application up.' ); ?></p>

<h4>Step2. <?php $this->e( 'Enter app information' ); ?></h4>

<p><?php $this->e( 'Gianism requires 5 information. Go to <a href="https://dev.twitter.com/apps">your application\'s detail page</a> and click <code>OAuth tools</code> tab. There, you can get 4 information below: ' ); ?></p>

<ol>
	<li>Consumer key</li>
	<li>Consumer secret</li>
	<li>Access token</li>
	<li>Access token secret</li>
</ol>

<p><?php printf( $this->_( 'Besides above, your twitter screen name will be required. Now please enter these credentials on <a href="%s">Setting page</a>.' ), $this->setting_url() ); ?></p>

