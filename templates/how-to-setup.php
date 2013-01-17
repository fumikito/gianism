<?php /* @var $this WP_Gianism */ ?>

<p class="last-updated">
	<?php printf($this->_('Last Updated: %s'), mysql2date(get_option('date_format'), '2012-12-07')); ?>
</p>

<h3>Facebook</h3>


<p class="desrtiption"><?php $this->e('First of all, Facebook account is required. If you don\'t have one, create it at <a href="https://www.facebook.com">facebook.com</a>'); ?></p>

<h4>Step1. <?php $this->e('Create App on Facebook developers'); ?></h4>

<p><?php $this->e('Gianism refers user\'s Facebook credential as Facebook app. Go to <a href="https://developers.facebook.com">Facebook developers</a> and sign up as developer. On registeration flow, you will have to enter mobile phone number or credit card number.'); ?></p>

<p><?php printf($this->_('Authenticated as developer, go to <a href="https://developers.facebook.com/apps">Apps</a> and create new App. You can enter name as you like, but the same name as WordPress site name <code>%s</code> is recommended for usability.'), get_bloginfo('name')); ?></p>

<p><?php $this->e('Though App credential is available for various usage(iOS app, Android app, Page tab, etc), minimum requirments are like below.' ); ?></p>

<table class="gianism-example-table">
	<tr>
		<th>Display Name</th>
		<td>
			<?php $this->e('App name. Blog name is recommended. User will see this name on authentication screen.'); ?><br />
			<span class="description"><?php printf($this->_('e.g. %s'), get_bloginfo('name')); ?></span>
		</td>
	</tr>
	<tr>
		<th>Namespace</th>
		<td>
			<?php $this->e('Unique string which identifies your app.'); ?><br />
			<span class="description"><?php printf($this->_('e.g. %s'), $_SERVER['HTTP_HOST']); ?></span>
		</td>
	</tr>
	<tr>
		<th><?php $this->e('Contact mail address') ?></th>
		<td>
			<?php $this->e('Contact mail address. It will be displayed on authentication screen on Facebook.'); ?><br />
			<span class="description"><?php printf($this->_('e.g. %s'), get_option('admin_email')); ?></span>
		</td>
	</tr>
	<tr>
		<th>App Domains</th>
		<td>
			<?php $this->e('Your domain.'); ?><br />
			<span class="description"><?php printf($this->_('e.g. %s'), $_SERVER['HTTP_HOST']); ?></span>
		</td>
	</tr>
	<tr>
		<th>Sandbox Mode</th>
		<td>
			<?php $this->e('On development environment or test flight, turn sandbox ON.'); ?><br />
			<span class="description"><?php printf($this->_('e.g. %s'), $this->_('OFF')); ?></span>
		</td>
	</tr>
	<tr>
		<th>Type</th>
		<td>
			<?php $this->e('You can activate several connection types, but below is required.'); ?><br />
			<span class="description">Website with Facebook Login</span>
		</td>
	</tr>
</table>

<h4>Step2. <?php $this->e('Enter App ID and App secret'); ?></h4>

<p><?php printf($this->_('Once you create Facebook App, App ID and App secret will be provided. Now you can enter it on <a href="%s">WordPress Admin screen</a>.'), admin_url('users.php?page=gianism')); ?></p>

<h3>Twitter</h3>

<p class="desrtiption"><?php $this->e('First of all, your twitter account is required. If you don\'t have one, create it at <a href="https://twitter.com">twitter</a>'); ?></p>

<h4>Step1. <?php $this->e('Create twitter application'); ?></h4>

<p><?php $this->e('Go to <a href="https://dev.twitter.com">Twitter Developer</a> and sign in with your twitter account. Then go to <a href="https://dev.twitter.com/apps">My Applications</a> and click <code>Create new application</code>.'); ?></p>

<p><?php sprintf($this->_('In Application detail section, enter appilcation name(same as <code>%s</code> recommended), description and Website. If you are not bot, enter reCaptha and creation flow will be done :)'), get_bloginfo('name')); ?></p>

<p><?php $this->e('Now you have new application. Go to your new app\'s detail page. On <code>Settings</code> tab, various information can be editted. Besides the required informations, you had better to upload Application icon which will be displayed on authentication screen.'); ?></p>

<p><?php $this->e('Futhermore, take care of application access type. Default is <code>readonly</code> and it\'s sufficient. Users tend to avoid strong access like <code>Read and Write</code>.'); ?></p>

<p><?php $this->e('Now you have finished setting application up.'); ?></p>

<h4>Step2. <?php $this->e('Enter app information'); ?></h4>

<p><?php $this->e('Gianism requires 5 informations. '); ?></p>

<p><?php $this->e('You can get them at <a href="https://dev.twitter.com/apps">your application\'s detail page</a>.'); ?></p>

<h3>Google</h3>

<p class="desrtiption"><?php $this->e('Google API system is very complex, but don\'t be afraid. Anyway if you don\'t have Google account, go to <a hreF="https://www.google.com/accounts/NewAccount">Create Google account</a> and get it.'); ?></p>

<h4>Step1. <?php $this->e('Create new Project'); ?></h4>

<p><?php $this->e('Go to <a href="https://code.google.com/apis/console">Google API Console</a> and select <code>Create...</code> from pulldown.'); ?></p>

<p><?php $this->e('On project page, go to <code>API Access</code> menu in sidebar. You can get <code>Client ID</code> and <code>Client secret</code>.'); ?></p>

<p><?php printf($this->_('Besiteds credential informations, you have to save <coce>Redirect URIs</code>. Click <code>Edit settings...</code> and enter redirect URI. Typically, your blog home URL <code>%s</code>. If you force SSL on login, make it to start with <code>https</code>.'), get_bloginfo('url')); ?></p>

<h4>Step2. <?php $this->e('Enter API Information'); ?></h4>

<p><?php printf($this->_('Input and save 3 informations(Client ID, Client Secret and Redirect URI) on <a href="%s">WordPress admin screen</a>.'), admin_url('users.php?page=gianism')); ?></p>

<h3>Yahoo! JAPAN</h3>

<p class="description"><?php $this->e('Yahoo! JAPAN account is required. If you don\'t have one, create it at <a href="https://e.developer.yahoo.co.jp/dashboard/">Yahoo! JAPAN Developer Center</a>.'); ?></p>

<p class="notice">
	<?php $this->e('<strong>Note:</strong> Yahoo! JAPAN is different from Yahoo! and is Japanese domestic service.'); ?>
</p>

<h4>Step1. <?php $this->e('Create applicaiton'); ?></h4>

<p><?php $this->e('Log in to Yahoo! JAPAN developer network and clik <a href="https://e.developer.yahoo.co.jp/register" target="_blank">Register</a> to fill required information below.'); ?></p>

<table class="gianism-example-table">
	<tbody>
		<tr>
			<th><?php $this->e('Application Type'); ?></th>
			<td><?php $this->e('Server side application'); ?></td>
		</tr>
		<tr>
			<th><?php $this->e('Contact email address'); ?></th>
			<td><?php $this->e('Choose from pull down.'); ?></td>
		</tr>
		<tr>
			<th><?php $this->e('Application name'); ?></th>
			<td><?php printf($this->_('Same as the blog name <code>%s</code> is the best.'), get_bloginfo('name')); ?></td>
		</tr>
		<tr>
			<th><?php $this->e('Site URL'); ?></th>
			<td><?php printf($this->_('Use root URI of your site <code>%s</code>.'), home_url('/', 'http')); ?></td>
		</tr>
	</tbody>
</table>

<h4>Step2. <?php $this->e('Input credentials'); ?></h4>

<p><?php $this->e('After registeration, you will redirect to application detail. There you can get <strong>application ID</strong> and <strong>secret key</strong>.'); ?></p>

<p><?php printf($this->_('Besides them, you must enter callback URL. This must be <code>%s</code>'), home_url('/yconnect/', ($this->is_ssl_required() ? 'https' : 'http'))); ?></p>

<p><?php $this->e('Now come back to WP admin panel, enter application ID and secret key. That\'s all done.'); ?></p>

<h3>mixi</h3>

<p class="description"><?php $this->e('mixi account is required. If you don\'t have one, create it at <a href="http://developer.mixi.co.jp/">mixi Developer Center</a>.'); ?></p>

<h4>Step1. <?php $this->e('Create mixi applicaiton'); ?></h4>

<p><?php $this->e('Log in to <a href="http://developer.mixi.co.jp/">Partner Dashboard</a> and clik <code>mixi Graph API</code>.'); ?></p>

<p>
	<?php
		$mixi_end_point = trailingslashit(get_bloginfo('url')).'mixi/';
		if((defined('FORCE_SSL_LOGIN') && FORCE_SSL_LOGIN) || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN)){
			$mixi_end_point = str_replace('http:', 'https:', $mixi_end_point);
		}
		printf($this->_('Create new service. Redirect URL should be <code>%s</code>.'), $mixi_end_point);
	?>
</p>

<h4>Step2. <?php $this->e('Input credentials'); ?></h4>

<p><?php $this->e('Get <code>Consumer Key</code> and <code>Consumer Secret</code> at service detail page, and save it.'); ?></p>
