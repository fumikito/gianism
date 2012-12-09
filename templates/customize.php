<h3><?php $this->e('Change Login Button'); ?></h3>
<p class="description">
	<?php $this->e('You can change the appearance of login button. Available hooks are below:'); ?>
</p>
<ol>
	<li>gianism_link_facebook</li>
	<li>gianism_link_twitter</li>
	<li>gianism_link_google</li>
	<li>gianism_link_mixi</li>
</ol>
<pre class="brush: php">
/**
 * <?php $this->e('You can customize Facebook login button like this'); ?>
 * 
 * @param string $markup 
 * @param string $link
 * @param string $title
 */
function _my_login_link_facebook($markup, $link, $title){
	return '&lt;a class="my_fb_link" href="'.$link.'"&gt;'.$title.'&lt;/a&gt;';
}
// Add filter.
add_filter('_my_login_link_facebook', 10, 3);
</pre>

<h3><?php $this->e('Change Redirect'); ?></h3>

<p class="description">
	<?php $this->e('You can hook on redirect URL after user logged in.'); ?>
</p>

<pre class="brush: php">
/**
 * Customize redirect URL
 * @param string $url if not specified, null will be passed.
 * @return string URL string to redirect to. Null is no-redirect.
 */
function _my_redirect_to($url){
	//<?php $this->e('Now you can get redirect URL.'); ?>

	//<?php $this->e('Not specified, $url is null.'); ?>

	return home_url();
}
// Add filter.
add_filter('gianism_redirect_to', '_my_redirect_to');
</pre>

<p class="notice">
	<?php $this->e('<strong>Note:</strong> Redirect occurs on various situations. If you are not enough aware of WordPress URL process, some troubles might occurs.'); ?>
</p>