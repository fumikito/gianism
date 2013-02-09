<div class="wrap gianism-wrap">

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1&appId=264573556888294";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<?php /* @var $this WP_Gianism */ ?>
<div id="icon-users" class="icon32"><br></div>

<h2 class="nav-tab-wrapper">
	<a class="nav-tab<?php if(!isset($_REQUEST['view'])) echo ' nav-tab-active'; ?>" href="<?php echo admin_url('users.php?page=gianism');?>">
		<?php $this->e('External Service'); ?>
	</a>
	<?php
		foreach(array(
			'setup' => $this->_('How to set up'),
			'customize' => $this->_('Customize'),
			'advanced' => $this->_('Advanced Usage')
		) as $key => $val):
	?>
	<a class="nav-tab<?php if(isset($_REQUEST['view']) && $_REQUEST['view'] == $key) echo ' nav-tab-active'; ?>" href="<?php echo admin_url('users.php?page=gianism&view='.$key);?>">
		<?php echo $val; ?>
	</a>
	<?php endforeach; ?>
</h2>

<br class="clear" />

<div class="sidebar">
	<div id="index">
		<h4><?php $this->e('Index'); ?></h4>
		<ol>
		</ol>
		<p class="forum-link">
			<?php $this->e('Have some questions? Go to <a href="http://wordpress.org/support/plugin/gianism">support forum</a> and create thread.'); ?>
		</p>
		<p class="amazon-link">
			<?php printf($this->_('If you find this plugin usefull, don\'t hesitate to buy me some present from <a href="%s">my wishlist</a>.'), 'http://www.amazon.co.jp/registry/wishlist/29NJ4F9NRNIKB'); ?>
		</p>
		<div class="fb-like-box" data-href="https://www.facebook.com/TakahashiFumiki.Page" data-width="278" data-height="72" data-show-faces="false" data-stream="false" data-border-color="f9f9f9" data-header="false"></div>
		<p class="social-link">
			<a href="https://twitter.com/intent/tweet?screen_name=takahashifumiki" class="twitter-mention-button" data-lang="ja" data-related="takahashifumiki">Tweet to @takahashifumiki</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
		</p>
	</div>
</div>

<div class="main-content">

<?php
	$view = isset($_REQUEST['view']) ? (string)$_REQUEST['view'] : '';
	switch($view):
		case 'setup':
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'how-to-setup.php';
			break;
		case 'customize':
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'customize.php';
			break;
		case 'advanced':
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'advanced.php';
			break;
		default:
?>
<form method="post">
	<?php $this->nonce_field('option'); ?>
	<h3>Facebook</h3>
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
						<?php printf($this->_('See detail at <a href="%1$s">%2$s</a>.'), admin_url('users.php?page=gianism&view=setup'), $this->_('How to set up')); ?>
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
						<?php printf($this->_('If you have fan page and use WordPress page as it, specify it here. Some functions are available. For details, see <strong>%s</strong>'), sprintf('<a href="%s">%s</a>', admin_url('users.php?page=gianism&view=advanced'), $this->_('Advanced Usage'))); ?>
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
						<?php printf($this->_('See detail at <a href="%1$s">%2$s</a>.'), admin_url('users.php?page=gianism&view=setup'), $this->_('How to set up')); ?>
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
						<?php printf($this->_('See detail at <a href="%1$s">%2$s</a>.'), admin_url('users.php?page=gianism&view=setup'), $this->_('How to set up')); ?>
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
						<?php printf($this->_('Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.'), $this->_('Redirect URI'), '<code>'.home_url('', ($this->is_ssl_required() ? 'https' : 'http')).'</code>', "Google API Console", 'https://code.google.com/apis/console'); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	
	
	
	<h3>Yahoo! JAPAN</h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><label><?php printf($this->_('Connect with %s'), 'Yahoo! JAPAN');?></label>
				<td>
					<label>
						<input type="radio" name="yahoo_enabled" value="1"<?php if($this->option['yahoo_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Enable');?>
					</label>
					<label>
						<input type="radio" name="yahoo_enabled" value="0"<?php if(!$this->option['yahoo_enabled']) echo ' checked="checked"';?> />
						<?php $this->e('Disable');?>
					</label>
					<p class="description">
						<?php printf($this->_('You have to create %1$s App <a target="_blank" href="%2$s">here</a> to get required infomation.'), "Yahoo! JAPAN", "https://e.developer.yahoo.co.jp/dashboard/"); ?>
						<?php printf($this->_('See detail at <a href="%1$s">%2$s</a>.'), admin_url('users.php?page=gianism&view=setup'), $this->_('How to set up')); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><label for="yahoo_application_id"><?php $this->e('Application ID'); ?></label></th>
				<td><input class="regular-text" type="text" name="yahoo_application_id" id="yahoo_application_id" value="<?php echo $this->option['yahoo_application_id']?>" /></td>
			</tr>
			<tr>
				<th><label for="yahoo_consumer_secret"><?php $this->e('Client Secret'); ?></label></th>
				<td><input class="regular-text" type="text" name="yahoo_consumer_secret" id="yahoo_consumer_secret" value="<?php echo $this->option['yahoo_consumer_secret']?>" /></td>
			</tr>
			<tr>
				<th><label><?php $this->e('Callback URI'); ?></label></th>
				<td>
					<p class="description">
						<?php
							$end_point = home_url('/yconnect/', ($this->is_ssl_required() ? 'https' : 'http'));
							printf($this->_('Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.'), $this->_('Callback URI'), "<code>{$end_point}</code>", $this->_("Yahoo! JAPAN Developer Network"), 'https://e.developer.yahoo.co.jp/dashboard/');
						?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	
	
	
	
	<h3>mixi</h3>
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
						<?php printf($this->_('See detail at <a href="%1$s">%2$s</a>.'), admin_url('users.php?page=gianism&view=setup'), $this->_('How to set up')); ?>
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
							$mixi_end_point = home_url('/mixi/', ($this->is_ssl_required() ? 'https' : 'http'));
							printf($this->_('Please set %1$s to %2$s on <a target="_blank" href="%4$s">%3$s</a>.'), $this->_('Redirect URI'), "<code>{$mixi_end_point}</code>", "mixi Partner Dashboard", 'http://developer.mixi.co.jp');
						?>
					</p>
				</td>
			</tr>
			<?php if($this->is_enabled('mixi')): ?>
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
						<?php if($this->mixi && !$this->mixi->has_valid_refresh_token()): ?>
						<small>(<?php $this->e('Your refresh token is invalid. Please reset it from link below.'); ?>)</small>
						<?php endif; ?>
					</label>
					<p class="description">
						<?php $this->e('If you want to send a message via mixi to your use who has only pseudo mail(@pseudo.mixi.jp), set up auth information by login to mixi from the link below as your account by which messages will be sent . Do not forget to check &quot;Always allow&quot;. <strong>Notice: </strong>You can send message to only your friend.');  ?>
					</p>
					<a href="<?php echo $this->mixi->get_admin_auth_link(); ?>" class="button"><?php $this->e('Auth on mixi'); ?></a>
					
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
	
	<h3><?php $this->e('Display Setting'); ?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php $this->e('Login screen'); ?></th>
				<td>
					<label>
						<input type="radio" name="show_button_on_login" value="1"<?php if($this->show_button_on_login()) echo ' checked="checked"';?> />
						<?php $this->e('Show all button on Login screen.'); ?>
					</label><br />
					<label>
						<input type="radio" name="show_button_on_login" value="0"<?php if(!$this->show_button_on_login()) echo ' checked="checked"';?> />
						<?php $this->e('Do not show login button.'); ?>
					</label>
					<p class="description">
						<?php printf($this->_('You can output login button manually. See detail at <a href="%2$s">%1$s</a>.'), $this->_('Customize'), admin_url('users.php?page=gianism&view=customize')); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	
	
	<?php submit_button(); ?>
</form>
<?php 
			break;
	endswitch;
?>

</div><!-- //.main-content -->
	
<br class="clear" />

</div><!-- //.gianism-wrap -->