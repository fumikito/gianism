<?php do_action('admin_notice'); ?>
<?php /* @var $this WP_Gianism */ ?>
<div id="icon-users" class="icon32"><br></div>
<h2><?php $this->e('WP Gianism setting'); ?></h2>
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
				</td>
			</tr>
		</tbody>
	</table>
	<?php submit_button(); ?>
</form>