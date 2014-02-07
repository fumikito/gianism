<?php

defined('ABSPATH') or die();

/** @var \Gianism\Admin $this */
/** @var \Gianism\Option $option */
?>


<h3><i class="lsf lsf-facebook"></i> Facebook</h3>

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
			<?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), get_bloginfo('name')); ?>
		</td>
	</tr>
	<tr>
		<th>Namespace</th>
		<td>
			<?php $this->e('Unique string which identifies your app.'); ?><br />
			<?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), $_SERVER['HTTP_HOST']); ?>
		</td>
	</tr>
	<tr>
		<th><?php $this->e('Contact mail address') ?></th>
		<td>
			<?php $this->e('Contact mail address. It will be displayed on authentication screen on Facebook.'); ?><br />
			<?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), get_option('admin_email')); ?>
		</td>
	</tr>
	<tr>
		<th>App Domains</th>
		<td>
			<?php $this->e('Your domain.'); ?><br />
			<?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), $_SERVER['HTTP_HOST']); ?>
		</td>
	</tr>
	<tr>
		<th>Sandbox Mode</th>
		<td>
			<?php $this->e('On development environment or test flight, turn sandbox ON.'); ?><br />
			<?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), $this->_('OFF')); ?>
		</td>
	</tr>
	<tr>
		<th>Type</th>
		<td>
			<?php $this->e('You can activate several connection types, but below is required.'); ?><br />
			<code>Website with Facebook Login</code>
		</td>
	</tr>
</table>

<h4>Step2. <?php $this->e('Enter App ID and App secret'); ?></h4>

<p><?php printf($this->_('Once you create Facebook App, App ID and App secret will be provided. Now you can enter it on <a href="%s">WordPress Admin screen</a>.'),  $this->setting_url()); ?></p>





<h3><i class="lsf lsf-twitter"></i> Twitter</h3>

<p class="desrtiption"><?php $this->e('First of all, your twitter account is required. If you don\'t have one, create it at <a href="https://twitter.com">twitter</a>'); ?></p>

<h4>Step1. <?php $this->e('Create twitter application'); ?></h4>

<p><?php $this->e('Go to <a href="https://dev.twitter.com">Twitter Developer</a> and sign in with your twitter account. Then go to <a href="https://dev.twitter.com/apps">My Applications</a> and click <code>Create new application</code>.'); ?></p>

<p><?php sprintf($this->_('In Application detail section, enter appilcation name(same as <code>%s</code> recommended), description and Website. If you are not bot, enter reCaptha and creation flow will be done :)'), get_bloginfo('name')); ?></p>

<p><?php $this->e('Now you have new application. Go to your new app\'s detail page. On <code>Settings</code> tab, various information can be editted. Besides the required informations, you had better to upload Application icon which will be displayed on authentication screen.'); ?></p>


<table class="gianism-example-table">
    <tr>
        <th>Name</th>
        <td>
            <?php $this->e('App name. Blog name is recommended. User will see this name on authentication screen.'); ?><br />
            <?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), get_bloginfo('name')); ?>
        </td>
    </tr>
    <tr>
        <th>Description</th>
        <td>
            <?php $this->e('Description about your site. It is recommended explaining your user why you request authentication.'); ?><br />
        </td>
    </tr>
    <tr>
        <th>Website</th>
        <td>
            <?php $this->e('Website\'s URL. Use this blog\'s URL'); ?><br />
            <?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), home_url('/', 'http')); ?>
        </td>
    </tr>
    <tr>
        <th>Callback URL</th>
        <td>
            <?php $this->e('The URL where user will be redirect after authentication. If you use other application which use twitter OAuth, leave this blank.'); ?><br />
            <?php printf($this->_('<strong>e.g.</strong> <code>%s</code>'), home_url('/twitter/', $this->is_ssl_required() ? 'https' : 'http')); ?>
        </td>
    </tr>
</table>

<p><?php $this->e('Take care of application access type. Default is <code>readonly</code> and it\'s sufficient. Users tend to avoid strong access like <code>Read and Write</code>.'); ?></p>

<p><?php $this->e('Now you have finished setting application up.'); ?></p>

<h4>Step2. <?php $this->e('Enter app information'); ?></h4>

<p><?php $this->e('Gianism requires 5 informations. Go to <a href="https://dev.twitter.com/apps">your application\'s detail page</a> and click <code>OAuth tools</code> tab. There, you can get 4 informations below: '); ?></p>

<ol>
    <li>Consumer key</li>
    <li>Consumer secret</li>
    <li>Access token</li>
    <li>Access token secret</li>
</ol>

<p><?php printf($this->_('Besides above, your twitter screen name will be required. Now please enter these credentials on <a href="%s">Setting page</a>.'), $this->setting_url()); ?></p>






<h3><i class="lsf lsf-google"></i> Google</h3>

<p class="desrtiption"><?php $this->e('Google API system is very complex, but don\'t be afraid. Anyway if you don\'t have Google account, go to <a hreF="https://www.google.com/accounts/NewAccount">Create Google account</a> and get it.'); ?></p>

<h4>Step1. <?php $this->e('Create new Project'); ?></h4>

<p><?php $this->e('Go to <a href="https://cloud.google.com/console/project">Google API Console &gt; Projects</a> and click <code>Create Project</code>. SMS verification will be required. After finishing, click your new project\'s name and go setting page.'); ?></p>

<p><?php $this->e('Now, On project page, clicking <code>API &amp; OAuth</code> menu in sidebar, then you are on <code>APIs</code> page which will enable various APIs one by one. Turn Google+ API <code>ON</code>.'); ?></p>

<p class="notice">
    <?php $this->e('Google API Console is too simple but values to be set are not simple, so you will be easily loose yourself. Be care of where you are.') ?>
</p>

<p><?php $this->_('Then, go to <code>Credentials</code> below <code>API &amp; auth</code> and click <code>CREATE NEW  CLIENT ID</code>.  Enter below:') ?></p>

<table class="gianism-example-table">
    <tr>
        <th>Appicaltion type</th>
        <td>
            <?php printf($this->_('Select <code>%s</code>.'), 'Web application'); ?><br />
        </td>
    </tr>
    <tr>
        <th>Authorized Javascript origin</th>
        <td>
            <code><?php echo home_url('/', 'http') ?></code><br />
            <code><?php echo home_url('/', 'https') ?></code>
        </td>
    </tr>
    <tr>
        <th>Authorized redirect URI</th>
        <td>
            <code><?php echo home_url('/google-auth/', $this->is_ssl_required() ? 'https' : 'http') ?></code>
        </td>
    </tr>
</table>

<p><?php $this->e('There you get <code>Client ID for web application</code> section, which provides <code>Client ID</code> and <code>Client secret</code>.') ?></p>

<p><?php printf($this->_('Finally, you must set up your application. Go to <code>Consent screen</code> below <code>APIs &amp; oauth</code>, and change Product name to your own. <code>%s</code> is recommended.'), get_bloginfo('name')) ?></p>

<h4>Step2. <?php $this->e('Enter API Information'); ?></h4>

<p><?php printf($this->_('Input and save 2 informations(Client ID and Client Secret) on <a href="%s">WordPress admin screen</a>.'), $this->setting_url()); ?></p>




<h3><i class="lsf lsf-amazon"></i> Amazon</h3>

<p class="description"><?php $this->e('Amazon account is required. If you don\'t have one, create it at <a href="https://sellercentral.amazon.com/gp/homepage.html">Log in with Amazon</a>.'); ?></p>

<h4>Step1. <?php $this->e('Create application'); ?></h4>

<p><?php $this->e('On Log in with Amazon, click <code>register new application</code> button right side. Then, insert information below:'); ?></p>

<table class="gianism-example-table">
    <tbody>
    <tr>
        <th>Name</th>
        <td><?php printf($this->_('<code>%s</code> is recommended.'), get_bloginfo('name')); ?></td>
    </tr>
    <tr>
        <th>Description</th>
        <td><?php $this->e('About your site.'); ?></td>
    </tr>
    <tr>
        <th>Privacy Notice URL</th>
        <td><?php $this->e('Your privacy policy URL.'); ?></td>
    </tr>
    </tbody>
</table>

<p><?php $this->e('After creation, you can see your application detail\'s setting tab. There, you can get <code>Client ID</code> and <code>Client Secret</code>.') ?></p>

<h4>Step2. <?php $this->e('Input credentials'); ?></h4>

<p><?php printf($this->_('Now come back to <a href="%s">WP admin panel</a>, enter <code>Client ID</code> and <code>Client Secret</code>. That\'s all done.'), $this->setting_url()); ?></p>


<h3><i class="lsf lsf-github"></i> Github</h3>

<p class="description"><?php $this->e('Github account is required. If you don\'t have one, create it at <a href="https://github.com">Github</a>.'); ?></p>

<h4>Step1. <?php $this->e('Create application'); ?></h4>

<p><?php printf($this->_('Go to %s and click %s. Now you will be on application form, enter information below:'), '<a href="https://github.com/settings/applications">Github Applications</a>', '<code>Register new application</code>') ?></p>

<table class="gianism-example-table">
    <tbody>
        <tr>
            <th>Application name</th>
            <td><?php printf($this->_('<code>%s</code> is recommended.'), get_bloginfo('name')); ?></td>
        </tr>
        <tr>
            <th>Homepage URL</th>
            <td><code><?php echo home_url('/', 'http') ?></code></td>
        </tr>
        <tr>
            <th>Authorization callback URL</th>
            <td><code><?php echo home_url('/github-auth/', $this->is_ssl_required() ? 'https' : 'http'); ?></code></td>
        </tr>
    </tbody>
</table>

<p><?php $this->e('After creation, you can see your application detail\'s setting tab. There, you can get <code>Client ID</code> and <code>Client Secret</code>.') ?></p>

<h4>Step2. <?php $this->e('Input credentials'); ?></h4>

<p><?php printf($this->_('Now come back to <a href="%s">WP admin panel</a>, enter <code>Client ID</code> and <code>Client Secret</code>. That\'s all done.'), $this->setting_url()); ?></p>


<h3><i class="lsf lsf-yahoo"></i> Yahoo! JAPAN</h3>

<p class="description"><?php $this->e('Yahoo! JAPAN account is required. If you don\'t have one, create it at <a href="https://e.developer.yahoo.co.jp/dashboard/">Yahoo! JAPAN Developer Center</a>.'); ?></p>

<p class="notice">
	<?php $this->e('<strong>Note:</strong> Yahoo! JAPAN is different from Yahoo! and is Japanese domestic service.'); ?>
</p>

<h4>Step1. <?php $this->e('Create application'); ?></h4>

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
			<td><?php printf($this->_('Use root URI of your site <code>%s</code>.'), home_url('/', ($this->is_ssl_required() ? 'https' : 'http'))); ?></td>
		</tr>
	</tbody>
</table>

<h4>Step2. <?php $this->e('Input credentials'); ?></h4>

<p><?php $this->e('After registeration, you will redirect to application detail. There you can get <strong>application ID</strong> and <strong>secret key</strong>.'); ?></p>

<p><?php printf($this->_('Besides them, you must enter callback URL. This must be <code>%s</code>'), home_url('/yconnect/', ($this->is_ssl_required() ? 'https' : 'http'))); ?></p>

<p><?php printf($this->_('Now come back to <a href="%s">WP admin panel</a>, enter application ID and secret key. That\'s all done.'), $this->setting_url()); ?></p>






<h3><i class="lsf lsf-mixi"></i> mixi</h3>

<p class="description"><?php $this->e('mixi account is required. If you don\'t have one, create it at <a href="http://developer.mixi.co.jp/">mixi Developer Center</a>.'); ?></p>

<h4>Step1. <?php $this->e('Create mixi application'); ?></h4>

<p><?php $this->e('Log in to <a href="http://developer.mixi.co.jp/">Partner Dashboard</a> and click <code>mixi Graph API</code>.'); ?></p>

<p>
	<?php
		printf($this->_('Create new service. Redirect URL should be <code>%s</code>.'), home_url('/mixi/', $this->is_ssl_required() ? 'https' : 'https'));
	?>
</p>

<h4>Step2. <?php $this->e('Input credentials'); ?></h4>

<p><?php printf($this->_('Get <code>Consumer Key</code> and <code>Consumer Secret</code> at service detail page, and save it on <a href="%s">WP admin screen</a>.'), $this->setting_url()); ?></p>
