<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */

?>

<h3><?php $this->e( 'Get publish permission of Facebook account' ); ?></h3>

<p class="description"><?php $this->e( 'You can get permission to publish information to user\'s Facebook wall.' ); ?></p>

<p><?php $this->e( 'For example, display link to post action &quot;read an article&quot; to Facebook wall.' ); ?></p>

<pre class="brush: php">
<?php
$code = <<<EOS
<?php
\$url = gianism_get_facebook_publish_permission_link(
	\$redirect_url, //%s,
	'my_facebook_auth_hook', //%s
	array('post_id' => 10), //%s
);
?>
<a href="<?php echo esc_url(\$url); ?>" rel="nofollow">Publish to Facebook</a>
EOS;
echo esc_html(
	sprintf(
		$code,
		$this->_( 'URL which user will be redirected' ),
		$this->_( 'Action name fired after authentication' ),
		$this->_( 'Additional arguments passed to hook function' )
	)
);
?>
</pre>

<p><?php $this->e( 'If user click this link, he will be redirected to Facebook and see permission dialog. After authentication, the aciton you registered will be fired. Now hook on it and execute publication.' ); ?></p>

<pre class="brush: php">
<?php
$code = <<<'EOS'
// %s
add_action( 'my_facebook_auth_hook', 'my_facebook_auth', 10, 3 );

/**
 * %s
 * @param Facebook\Facebook $facebook
 * @param array             $args
 * @param string            $token
 */
function my_facebook_auth( $facebook, $args $token ) {
	// %s
	if(isset(\$args['post_id']) && (\$post = get_post(\$args['post_id']))){
		try{
			$facebook->post("/me/feed", [
				"message" => "%s",
				"link" => get_permalink($post->ID),
				"name" => get_the_title($post->ID),
				"description" => strip_tags($post->post_content),
				"action" => json_encode( [
					"name" => get_bloginfo( 'name' ),
					"link" => home_url( '/' )
				] )
			], $token);
		} catch ( FacebookApiException \$e ) {
			// %s
		}
	}
	// %s
}
EOS;
echo esc_html(
	sprintf(
		$code,
		$this->_( 'Register action hook which you registered above.' ),
		$this->_( 'This funciton will be executed after authentication' ),
		$this->_( 'Check if argument is propery passed and if post exists.' ),
		$this->_( 'Read an artcile.' ),
		$this->_( 'Do error handling if you wish.' ),
		$this->_( 'This funcion been executed, user will be redirected.' )
	)
);
?>
</pre>

<p class="notice">
	<?php
	echo wp_kses_post(
		sprintf(
		// translators: %s is URL.
			__( '<strong>Note:</strong> <code>$facebook</code> object is instance of Facebook class which is part of Facebook PHP SDK. To know what you can do with it, read the <a href="%s">documentation</a>.', 'wp-gianism' ),
			'https://developers.facebook.com/docs/reference/php/'
		)
	);
	?>
</p>


<h3><?php $this->e( 'Make tweet with your account' ); ?></h3>

<p class="description"><?php $this->e( 'You can make tweet by registered application\'s account.' ); ?></p>

<pre class="brush: php">
<?php
$code = <<<EOS
gianism_update_twitter_status( '%s' );
EOS;
echo esc_html( sprintf( $code, $this->_( 'Hello, my followers. I updated new post.' ) ) );
?>
</pre>

<p><?php $this->e( 'This function itself makes no sense, but you can use some hook to make auto post.' ); ?></p>

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
echo esc_html( sprintf( $code, $this->_( '%1$s pulbished %2$s. Please visit %3$s' ) ) );
?>
</pre>

<p class="notice"><?php $this->e( '<strong>Note:</strong> Currently, only twitter application\'s account(thus, admin\'s twitter account) can tweet.' ); ?></p>



<h3><?php $this->e( 'More Detailed Information' ); ?></h3>
<p>
	<?php
	printf(
		$this->_( 'You can do many things with SNS API. But every solution is very specific and little bit difficult. If you need more, please visit our <a target="_blank" href="%s">support site</a>.' ),
		gianism_utm_link(
			'https://gianism.info/',
			[
				'utm_medium' => 'advanced',
			]
		)
	);
	?>
</p>
