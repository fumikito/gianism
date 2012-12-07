<h3><?php $this->e('Customizatin');?></h3>

<h4><?php $this->e('Change Login Button'); ?></h4>
<p class="description">
	<?php $this->e('You can change the appearance of login button. Available hooks are below:'); ?>
</p>
<ol>
	<li>gianism_link_facebook</li>
	<li>gianism_link_twitter</li>
	<li>gianism_link_google</li>
</ol>
<pre><code>//<?php $this->e('You can customize Facebook login button like this'); ?>

function _my_login_link_facebook($markup, $link, $title){
	return '&lt;a class="my_fb_link" href="'.$link.'"&gt;'.$title.'&lt;/a&gt;';
}
add_filter('_my_login_link_facebook', 10, 3);
</code></pre>

<h4><?php $this->e('Change Redirect'); ?></h4>

<p class="description">
	<?php $this->e('You can hook on redirect URL after user logged in.'); ?><br />
	<?php $this->e('<strong>Note:</strong> Redirect occurs on various situations. If you are not enough aware of WordPress URL process, some troubles might occurs.'); ?>
</p>

<pre><code>function _my_redirect_to($url){
	//<?php $this->e('Now you can get redirect URL.'); ?>

	//<?php $this->e('Not specified, $url is null.'); ?>

	return home_url();
}
add_filter('gianism_redirect_to', '_my_redirect_to');
</code></pre>
