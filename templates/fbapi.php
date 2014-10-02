<?php

defined('ABSPATH') or die();

/** @var \Gianism\Admin $this */
/** @var \Gianism\Option $option */
/** @var \Gianism\Service\Facebook $facebook */
$facebook = $this->get_service_instance('facebook');

?>
<h3><i class="lsf lsf-facebook"></i> <?php $this->e('Token setting') ?></h3>
<?php if( is_wp_error($facebook->admin) ): ?>
	<p class="danger">
		<?php if( $facebook->admin->get_error_code() == 410 ):  ?>
			<?php $this->e('Token is outdated. Click link and get new one.'); ?>
		<?php else: ?>
			<?php $this->e('Token is not set. Please click link below and get one.'); ?>
		<?php endif; ?>
	</p>
<?php else: ?>
	<p class="notice">
		<?php printf($this->_('O.K. You have permission. If you want to renew token, please click link below. Token will be valid for %d days.'),
			(60 - floor(( current_time('timestamp') - get_option('gianism_facebook_admin_refreshed', 0) ) / 60 / 60 / 24)) ); ?>
	</p>
<?php endif; ?>
<a class="button" href="<?php echo esc_url($facebook->get_admin_connect_link()) ?>"><?php $this->e('Get Token') ?> <small><?php $this->e('Read Only') ?></small></a>
<a class="button" href="<?php echo esc_url($facebook->get_admin_connect_link(true)) ?>"><?php $this->e('Get Token') ?> <small><?php $this->e('Publish') ?></small></a>

<h3><i class="lsf lsf-friend"></i> <?php $this->e('Account to use') ?></h3>
<?php if( is_wp_error($facebook->admin) ): ?>
	<p class="description"><?php $this->e('You need access token to manage accounts. Click links above and you can get Facebook API token.') ?></p>
<?php elseif( !($me = $facebook->admin_account) ): ?>
	<p class="danger"><?php $this->e('Failed to get your account. Please renew token.') ?></p>
<?php else: ?>
	<p class="description"><?php $this->e('Please select account to use.') ?></p>
	<form method="post" action="<?php echo $this->setting_url('fb-api') ?>">
		<?php wp_nonce_field('gianism_fb_account') ?>
		<table class="form-table">
			<tr>
				<th><?php $this->e('You') ?></th>
				<td>
					<label><input type="radio" name="fb_account_id" value="me"<?php checked( 'me' == $facebook->admin_id ) ?> /> <?php echo esc_html($me['name']) ?></label>
				</td>
			</tr>
			<tr>
				<th><?php $this->e('Your Page') ?></th>
				<td>
					<?php
						$out = array();
						foreach( $facebook->admin_pages as $account ){
							$out[] = sprintf('<label><input type="radio" name="fb_account_id" value="%s"%s /> %s</label>',
								$account['id'], checked($account['id'] == $facebook->admin_id, true, false), esc_html($account['name']));
						}
						if( $out ):
							echo implode('<br />', $out);
						else:
					?>
							<p class="description"><?php $this->e('You have no Facebook page.') ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php submit_button() ?>
	</form>
<?php endif; ?>

<h3><i class="lsf lsf-help"></i> <?php $this->e('How to use') ?></h3>
<p><?php printf($this->_('You can get token-ready <a href="%s">Facebook PHP SDK\'s API client</a>. For example, get latest news feed and show it on footer.'), 'https://github.com/facebook/facebook-php-sdk') ?></p>
<pre class="brush: php">
<?php
echo <<<PHP
// Add action hook.
add_action('wp_footer', function(){
    \$status = my_fb_status();
    echo '&lt;div class="footer-fb-status"&gt;'.\$status.'&lt;/div&gt;';
});

/**
 * This function will return facebook's latest staus
 *
 * @return string
 */
function my_fb_status(){
	// Use transient, because network I/O is slow.
	\$status = get_transient('my_fb_status');
	if( false === \$status ){
		// If settings are done,
		// You can get token-ready client with this function.
		\$fb = gianism_fb_admin();
		if( is_wp_error(\$fb) ){
			// Oops,
			return 'Sorry!';
		}
		// Let's get Page setting.
		\$page_id = gianism_fb_admin_id();
		// api method is SDK's method for Graph API
		\$feeds = \$fb->api("\$page_id/feed");
		if( !\$feeds['data'] ){
			return 'Sorry, no updates.';
		}
		\$status = \$feeds['data'][0]['message'];
		// Save it for 0.5h.
		set_transient('my_fb_status', \$status, 60 * 60 * 0.5);
	}
	return \$status
}
PHP;
?>
</pre>
<p><?php $this->e('You should know about Facebook Graph API. Useful resources are below.') ?></p>
<ol>
	<li><a href="https://developers.facebook.com/docs/graph-api">Graph API Reference</a></li>
	<li><a href="https://developers.facebook.com/tools/explorer/">Graph API Explorer</a></li>
	<li><a href="https://developers.facebook.com/docs/reference/php/3.2.3">PHP SDK's documentation</a></li>
</ol>