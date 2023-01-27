<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */
?>


<h3><?php $this->e( 'Change Login Button' ); ?></h3>
<p class="description">
	<?php $this->e( 'You can change the appearance of login button.' ); ?>
</p>
<pre class="brush: php">
/**
 * <?php $this->e( 'You can customize Facebook login button like this' ); ?>

 * @param string  $markup
 * @param string  $link
 * @param string  $title
 * @param boolean $link
 * @param string  $service facebook, google, etc.
 */
function _my_login_link_facebook($markup, $link, $title, $is_register, $service){
	return '&lt;a class="my_fb_link my_fb_link_{$service}" href="'.$link.'"&gt;'.$title.'&lt;/a&gt;';
}
// Add filter.
add_filter('gianism_link_html', '_my_login_link_facebook', 10, 5);
</pre>

<h3><?php $this->e( 'Change Redirect' ); ?></h3>

<p class="description">
	<?php $this->e( 'You can hook on redirect URL after user logged in.' ); ?>
</p>

<pre class="brush: php">
/**
 * Customize redirect URL
 * @param string $url     if not specified, null will be passed.
 * @param string $service facebook, twitter, etc.
 * @param string $context login, connect, etc.
 * @return string URL string to redirect to. Null is no-redirect.
 */
function _my_redirect_to($url, $service, $context){
	//<?php $this->e( 'Now you can get redirect URL.' ); ?>

	//<?php $this->e( 'Not specified, $url is null.' ); ?>

	return home_url();
}
// Add filter.
add_filter('gianism_redirect_to', '_my_redirect_to', 10, 3);
</pre>

<p class="notice">
	<?php $this->e( '<strong>Note:</strong> Redirect occurs on various situations. If you are not enough aware of WordPress URL process, some troubles might occurs.' ); ?>
</p>


<h3><?php $this->e( 'Control login button display' ); ?></h3>
<p class="description">
	<?php $this->e( 'By default, all login buttons of each services are displayed on both login screen and register screen. You can turn it off on admin screen. Besides it, you can controll it with filter hook.' ); ?>
</p>

<pre class="brush: php">
<?php
$code = <<<EOS
/**
 * This function determines whether buttons will be displayed.
 * 
 * @param boolean \$display If true, buttons will be display
 * @param string \$context 'login' or 'register'.
 * @return boolean Don't forget to return true or false.
 */
function _my_login_button_condition(\$display, \$context){
	// Use switch statement is good practice.
	// Because \$context may got more options.
	switch(\$context){
		case 'register':
			// You don't like to display on registeration.
			return false;
			break;
		default:
			// Otherwise, returns as it is.
			return \$display;
			break;
	}
}

//Add filter on display condition of buttons
//You will get 2 arguments, the 1st is display flag and another is context string.
add_filter('gianism_show_button_on_login', '_my_login_button_condition', 10, 2);
EOS;
echo esc_html( $code );
?>
</pre>


<h3><?php $this->e( 'Display login button as you like' ); ?></h3>
<p class="description">
	<?php $this->e( 'You can display social login buttons anywhere.' ); ?>
</p>
<p>
	<?php $this->e( 'Gianism displays social login button on login screen. But you may want to display in other situations.' ); ?>
	<br/>
	<?php $this->e( 'For example, you have some SNS oriented site and want to hide WordPress\'s login screen and want your user to log in only through social login buttons.' ); ?>
	<br/>
	<?php $this->e( 'In this case, you can display social login buttons wherever you like. The &quot;if&quot; statement is not necessary if you are brave.' ); ?>
	<br/>
</p>
<pre class="brush: php">
<?php
$code = <<<EOS
if( function_exists('gianism_login') ){
	gianism_login();
}
EOS;
echo esc_html( $code );
?>
</pre>

<p>
	<?php $this->e( 'Further more, you might think your user should be redirect to your single page. In this case, you can specify redirect URL.' ); ?>
	<br/>
</p>

<pre class="brush: php">
<?php
$code = <<<EOS
gianism_login('', '', get_permalink());
EOS;
echo esc_html( $code );
?>
</pre>

<p>
	<?php $this->e( 'First argument is starting tag(e.g. &lt;div id="some-id"&gt;). Second argument is closing tag(e.g. &lt;/div&gt;). And third argument is redirect URL where your user will be redirected after logging-in.' ); ?>
</p>

<h3><?php $this->e( 'Data Source' ); ?></h3>
<p>
	<?php
	printf(
		$this->_( 'For more detailed information, please visit our <a target="_blank" href="%s">support site</a>.' ),
		gianism_utm_link(
			'https://gianism.info/',
			[
				'utm_medium' => 'customize',
			]
		)
	);
	?>
</p>
