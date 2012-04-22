<?php do_action('admin_notice'); ?>
<?php /* @var $this WP_Gianism */ ?>
<div id="icon-users" class="icon32"><br></div>
<h2><?php $this->e('External Service'); ?></h2>
<form method="post">
	<?php $this->nonce_field('option'); ?>
	<h3 style="clear: left;">Facebook</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label><?php $this->e('Connect with Facebook');?></label>
				<td>
					<label>
						<input type="radio" name="fb_enabled" value="1"<?php if($this->option['fb_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Enable');?>
					</label>
					<label>
						<input type="radio" name="fb_enabled" value="0"<?php if(!$this->option['fb_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Disable');?>
					</label>
					<p class="description">
						<?php printf($this->_('You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.'), "Facebook", "https://developers.facebook.com/apps"); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="fb_app_id"><?php $this->e('App ID');?></label>
				<td>
					<input type="text" class="regular-text" name="fb_app_id" id="fb_app_id" value="<?php echo $this->option['fb_app_id']?>" />
				</td>
			</tr>
			<tr>
				<th><label for="fb_app_secret"><?php $this->e('App Secret');?></label>
				<td>
					<input type="text" class="regular-text" name="fb_app_secret" id="fb_app_secret" value="<?php echo $this->option['fb_app_secret']?>" />
				</td>
			</tr>
			<tr>
				<th><label for="fb_fan_gate"><?php $this->e('Facebook Fan Gate');?></label>
				<td>
					<select name="fb_fan_gate" id="fb_fan_gate">
						<option value="0"<?php if($this->option['fb_fan_gate'] == 0) echo ' selected="selected"';?>><?php $this->e('No Fan Gate'); ?></option>
						<?php $query = new WP_Query('post_type=page&posts_per_page=0'); if($query->have_posts()): while($query->have_posts()): $query->the_post(); ?>
						<option value="<?php the_ID(); ?>"<?php if($this->option['fb_fan_gate'] == get_the_ID()) echo ' selected="selected"';?>><?php the_title(); ?></option>
						<?php endwhile; endif; wp_reset_query();?>
					</select>
					<p class="description">
						<?php printf($this->_('If you have fan page and use WordPress page as it, specify it here. Some functions are available. For details, see <strong>%s</strong>'), dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR."functions.php"); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	<h3>Twitter</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label><?php printf($this->_('Connect with %s'), 'Twitter');?></label>
				<td>
					<label>
						<input type="radio" name="tw_enabled" value="1"<?php if($this->option['tw_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Enable');?>
					</label>
					<label>
						<input type="radio" name="tw_enabled" value="0"<?php if(!$this->option['tw_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Disable');?>
					</label>
					<p class="description">
						<?php printf($this->_('You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.'), "Twitter", "https://dev.twitter.com/apps"); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="tw_screen_name"><?php $this->e('Screen Name'); ?></label></th>
				<td><input class="regular-text" type="text" name="tw_screen_name" id="tw_screen_name" value="<?php echo $this->option['tw_screen_name']?>" /></td>
			</tr>
			<tr>
				<th><label for="tw_consumer_key"><?php $this->e('Consumer Key'); ?></label></th>
				<td><input class="regular-text" type="text" name="tw_consumer_key" id="tw_consumer_key" value="<?php echo $this->option['tw_consumer_key']?>" /></td>
			</tr>
			<tr>
				<th><label for="tw_consumer_secret"><?php $this->e('Consumer Secret'); ?></label></th>
				<td><input class="regular-text" type="text" name="tw_consumer_secret" id="tw_consumer_secret" value="<?php echo $this->option['tw_consumer_secret']?>" /></td>
			</tr>
			<tr>
				<th><label for="tw_access_token"><?php $this->e('Access Token'); ?></label></th>
				<td><input class="regular-text" type="text" name="tw_access_token" id="tw_access_token" value="<?php echo $this->option['tw_access_token']?>" /></td>
			</tr>
			<tr>
				<th><label for="tw_access_token_secret"><?php $this->e('Access token secret'); ?></label></th>
				<td><input class="regular-text" type="text" name="tw_access_token_secret" id="tw_access_token_secret" value="<?php echo $this->option['tw_access_token_secret']?>" /></td>
			</tr>
			
		</tbody>
	</table>
	<h3>Google</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label><?php printf($this->_('Connect with %s'), 'Google');?></label>
				<td>
					<label>
						<input type="radio" name="ggl_enabled" value="1"<?php if($this->option['ggl_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Enable');?>
					</label>
					<label>
						<input type="radio" name="ggl_enabled" value="0"<?php if(!$this->option['ggl_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Disable');?>
					</label>
					<p class="description">
						<?php printf($this->_('You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.'), "Google API Console", "https://code.google.com/apis/console"); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="ggl_consumer_key"><?php $this->e('Client ID'); ?></label></th>
				<td><input class="regular-text" type="text" name="ggl_consumer_key" id="ggl_consumer_key" value="<?php echo $this->option['ggl_consumer_key']?>" /></td>
			</tr>
			<tr>
				<th><label for="ggl_consumer_secret"><?php $this->e('Client Secret'); ?></label></th>
				<td><input class="regular-text" type="text" name="ggl_consumer_secret" id="ggl_consumer_secret" value="<?php echo $this->option['ggl_consumer_secret']?>" /></td>
			</tr>
			<tr>
				<th><label for="ggl_redirect_uri"><?php $this->e('Redirect URI'); ?></label></th>
				<td>
					<input class="regular-text" type="text" name="ggl_redirect_uri" id="ggl_redirect_uri" value="<?php echo $this->option['ggl_redirect_uri']?>" />
					<p class="description">
						<?php printf($this->_('Please set %1$s to %2$s on %3$s. If you use SSL on login, replace it\'s http with https.'), $this->_('Redirect URI'), get_bloginfo('url'), "Google API Console"); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	<h3>Mixi</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label><?php printf($this->_('Connect with %s'), 'mixi');?></label>
				<td>
					<label>
						<input type="radio" name="mixi_enabled" value="1"<?php if($this->option['mixi_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Enable');?>
					</label>
					<label>
						<input type="radio" name="mixi_enabled" value="0"<?php if(!$this->option['mixi_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Disable');?>
					</label>
					<p class="description">
						<?php printf($this->_('You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.'), "mixi Graph API", "http://developer.mixi.co.jp/connect/mixi_graph_api/services/"); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th><label for="mixi_consumer_key"><?php $this->e('Client ID'); ?></label></th>
				<td><input class="regular-text" type="text" name="mixi_consumer_key" id="mixi_consumer_key" value="<?php echo $this->option['mixi_consumer_key']?>" /></td>
			</tr>
			<tr>
				<th><label for="mixi_consumer_secret"><?php $this->e('Client Secret'); ?></label></th>
				<td><input class="regular-text" type="text" name="mixi_consumer_secret" id="mixi_consumer_secret" value="<?php echo $this->option['mixi_consumer_secret']?>" /></td>
			</tr>
			<tr>
				<th><label><?php $this->e('Redirect URI'); ?></label></th>
				<td>
					<p class="description">
						<?php
							$mixi_end_point = trailingslashit(get_bloginfo('url')).'mixi/';
							if((defined('FORCE_SSL_LOGIN') && FORCE_SSL_LOGIN) || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN)){
								$mixi_end_point = str_replace('http:', 'https:', $mixi_end_point);
							}
							printf($this->_('Please set %1$s to %2$s on %3$s.'), $this->_('Redirect URI'), "<code>{$mixi_end_point}</code>", "mixi Partner Dashboard");
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php $this->e('Token'); ?></th>
				<td>
					<label>
						<input class="regular-text" type="text" readonly="readonly" name="mixi_access_token" id="mixi_access_token" value="<?php echo $this->option['mixi_access_token']?>" />
						<?php $this->e('Access Token'); ?>
					</label><br />
					<label>
						<input class="regular-text" type="text" readonly="readonly" name="mixi_refresh_token" id="mixi_refresh_token" value="<?php echo $this->option['mixi_refresh_token']; ?>" />
						<?php $this->e('Refresh Token'); ?>
						<?php if(!$this->mixi->has_valid_refresh_token()): ?>
						<small>(<?php $this->e('Your refresh token is invalid. Please reset it from link below.'); ?>)</small>
						<?php endif; ?>
					</label>
					<p class="description">
						<?php $this->e('If you want to send a message via mixi to your use who has only pseudo mail(@pseudo.mixi.jp), set up auth information by login to mixi from the link below as your account by which messages will be sent . Do not forget to check &quot;Always allow&quot;. <strong>Notice: </strong>You can send message to only your friend.');  ?>
					</p>
					<a href="<?php echo $this->mixi->get_admin_auth_link(); ?>" class="button"><?php $this->e('Auth on mixi'); ?></a>
				</td>
			</tr>
			
		</tbody>
	</table>
	<?php submit_button(); ?>
</form>

<hr />

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