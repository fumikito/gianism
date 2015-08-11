<?php

defined('ABSPATH') or die();

/** @var \Gianism\Admin $this */
/** @var \Gianism\Option $option */
?>

<h3><?php $this->e('Create Facebook page tab'); ?></h3>

<p><?php $this->e('<a href="https://developers.facebook.com/docs/appsonfacebook/pagetabs/">Page tab</a> is page which you can add to your facebook page. If you create Facebook Web application and connect it with Gianism, you can assign WordPress page to page tab.'); ?></p>

<p><?php printf($this->_('You can assign page tab <a href="%s">here</a>. But it is not sufficient, because page tab itself should be assigned on <a href="https://developers.facebook.com/apps">Facebook Develpers</a>. You can intergrate your WordPress page to Facebook tab there.'), admin_url('users.php?page=gianism'));?></p>

<p class="notice"><?php $this->e('<strong>Note:</strong> Page tab will be embedded in Facebook and should have fixed width(520px or 810px). Be careful to your theme layout. If you would like to see how it looks, please visit and check <a href="https://www.facebook.com/TakahashiFumiki.Page/app_264573556888294">plugin authors Facebook page tab</a>.' );?></p>

<p><?php $this->e('Now you have completed connecting WordPress page to Facebook page tab. Several utility functions are available. For example, assume that you have page template named <code>page-facebook.php</code> and edit like below.');?></p>

<pre class="brush: php">
<?php
$code = <<<EOS
// Detect user is liked.
if(is_user_like_fangate()){
	echo 'You like me, ah?';
}els{
	echo 'Oops, you don\'t like me:(';
}

// Detct user is logged in Facebook.
// Facebook page tab can be displayed to un-authenticated user.
if(is_guest_on_fangate()){
	echo 'You are not logged in to Facebook. Cheating?';
}else{
	echo 'You are logged in to Facebook.';
}

// Detect user is logged in WordPress.
// This is built-in funciton.
if(is_user_logged_in()){
	echo 'You are logged in %1\$s.';
}else{
	echo 'You are not logged in %1\$s.';
	printf('Please log in <a target="_top" href="%%s">here</a>.', wp_login_url());
}

// Detect current user's facebook account is 
// connected to WordPress account.
if(get_user_id_on_fangate()){
	echo 'Your Facebook ID is connected with %1\$s account.';
}else{
	echo 'Your Facebook ID is not connected with %1\$s account.';
}
EOS;
echo esc_html(sprintf($code, get_bloginfo('name')));
?>
</pre>

<h3><?php $this->e('Get publish permission of Facebook account');?></h3>

<p class="description"><?php $this->e('You can get permission to publish information to user\'s Facebook wall.'); ?></p>

<p><?php $this->e('For example, display link to post action &quot;read an article&quot; to Facebook wall.'); ?></p>

<pre class="brush: php">
<?php 
$code = <<<EOS
<?php
\$url = get_facebook_publish_permission_link(
	\$redirect_url, //%s,
	'my_facebook_auth_hook', //%s
	array('post_id' => 10), //%s
);
?>
<a href="<?php echo esc_url(\$url); ?>" rel="nofollow">Publish to Facebook</a>
EOS;
echo esc_html(sprintf($code, 
		$this->_('URL which user will be redirected'),
		$this->_('Action name fired after authentication'),
		$this->_('Additional arguments passed to hook function')));
?>
</pre>

<p><?php $this->e('If user click this link, he will be redirected to Facebook and see permission dialog. After authentication, the aciton you registered will be fired. Now hook on it and execute publication.'); ?></p>

<pre class="brush: php">
<?php 
$code = <<<EOS
// %s
add_aciton('my_facebook_auth_hook', 'my_facebook_auth', 10, 2);

/**
 * %s
 * @param Facebook \$facebook
 * @param array \$args
 */
function my_facebook_auth(\$facebook, \$args){
	// %s
	if(isset(\$args['post_id']) && (\$post = get_post(\$args['post_id']))){
		try{
			\$facebook->api("/me/feed", "POST", array(
				"message" => "%s",
				"link" => get_permalink(\$post->ID),
				"name" => get_the_title(\$post->ID),
				"description" => strip_tags(\$post->post_content),
				"action" => json_encode(array(
					"name" => get_bloginfo('name'),
					"link" => home_url('/')))
			));
		}catch(FacebookApiException \$e){
			// %s
		}
	}
	// %s
}
EOS;
echo esc_html(sprintf($code, 
		$this->_('Register action hook which you registered above.'), 
		$this->_('This funciton will be executed after authentication'),
		$this->_('Check if argument is propery passed and if post exists.'),
		$this->_('Read an artcile.'),
		$this->_('Do error handling if you wish.'),
		$this->_('This funcion been executed, user will be redirected.')));
?>
</pre>

<p class="notice"><?php printf($this->_('<strong>Note:</strong> <code>$facebook</code> object is instance of Facebook class which is part of Facebook PHP SDK. To know what you can do with it, read the <a href="%s">documentation</a>.' ), 'https://developers.facebook.com/docs/reference/php/');?></p>



<h3><?php $this->e('Make tweet with your account'); ?></h3>

<p class="description"><?php $this->e('You can make tweet by registered application\'s account.'); ?></p>

<pre class="brush: php">
<?php
$code = <<<EOS
update_twitter_status("%s");
EOS;
echo esc_html(sprintf($code, $this->_('Hello, my followers. I updated new post.')));
?>
</pre>

<p><?php $this->e('This function itself makes no sense, but you can use some hook to make auto post.'); ?></p>

<pre class="brush: php">
<?php
$code = <<<EOS
/**
 * Tweet when post is published.
 * 
 * @param string \$new_status
 * @param string \$old_status
 * @param int \$post
 */
function _my_gianism_publish_tweet(\$new_status, \$old_status, \$post){
	// If post status is changed to publish, tweet.
	if('publish' == \$new_status && 'post' == \$post->post->type){ 
		switch(\$old_status){
			case 'draft':
			case 'pending':
			case 'auto-draft':
			case 'future':
				\$url = wp_get_shortlink(\$post->ID);
				\$author = get_the_author_meta('display_name', \$post->post_author);
				\$string = sprintf('%s',
				    \$author, \$post->post_title, \$url);
				break;
		}
	}
}
// Hook on post status transition
add_action('transition_post_status', '_my_gianism_publish_tweet', 10, 3);
EOS;
echo esc_html(sprintf($code, $this->_('%1$s pulbished %2$s. Please visit %3$s')));
?>
</pre>

<p class="notice"><?php $this->e('<strong>Note:</strong> Currently, only twitter application\'s account(thus, admin\'s twitter account) can tweet.'); ?></p>


<h3><?php $this->e('Save additional infomration'); ?></h3>

<p class="description">
	<?php $this->e('Gianism uses only user name or ID and email(if possible) which are required on creating WordPress user account. but in some cases, you may need additional information provided from SNS.'); ?><br />
	<?php $this->e('For example, Facebook provides various information like educationla background, friends and so on.'); ?>
</p>

<pre class="brush: php">
<?php
$code = <<<EOS
/**
 * %s
 * @param int \$user_id
 * @param mixed \$data Information provided from Service
 * @param string \$service Service name(facebook, twitter, google, yahoo, mixi)
 * @param boolean \$on_creation If user is newly created, true.
 */
function _my_additional_info(\$user_id, \$data, \$service, \$on_creation){
	switch( \$service ){
		case 'facebook':
			// Save Facebook bio as user description
			// On facebook, \$data is Facebook API instance.
			\$profile = \$data->api('/me');
			if( isset(\$profile['bio']) ){
				update_user_meta(\$user_id, 'description', \$profile['bio']);
			}
			break;
	}
}
// Add hook. Don't forget to pass 4th argument(arguments length).
add_action('wpg_connect', '_my_additional_info', 10, 4);
EOS;
echo esc_html(sprintf($code, $this->_('Save additional information on SNS connection.')));
?>
</pre>

<p><?php $this->e('Data structure is different by service. For more detail, read documentation or make thread on <a href="http://wordpress.org/support/plugin/gianism">WordPress.org</a>.'); ?></p>

<p class="notice"><?php $this->e('<strong>Note:</strong> information about user is different by service, so you shouldn\'t relay on specific service. Facebook provides educationla background or sex, but twitter doesn\'t.'); ?></p>