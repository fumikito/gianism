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
		</tbody>
	</table>
	<?php submit_button(); ?>
</form>