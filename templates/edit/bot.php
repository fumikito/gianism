<?php /** @var \Gianism\Helper\Bot $this */ ?>
<?php /** @var \WP_Post $post */ ?>

<div class="gianism-post-container">

	<div class="form-row">
		<label for="excerpt"><?php printf($this->_('What you tweet as <a href="https://twitter.com/%1$s" target="_blank">@%1$s</a>?'), $this->twitter->tw_screen_name) ?></label>
		<textarea id="excerpt" name="excerpt" placeholder="<?php echo esc_attr($this->_('Enter your tweet.')) ?>"><?php echo esc_textarea($post->post_excerpt) ?></textarea>
		<p class="description">
			<?php
				printf($this->_('You can use short codes: %s'), implode(', ', array_map(function($code){
					return sprintf('<strong>%s</strong>', esc_html($code));
				}, $this->short_codes)));

			?>

		</p>
	</div>


	<div class="form-row">
		<label><?php $this->e('Frequency') ?></label>

	</div>


	<div class="form-row">
		<label><?php $this->e('End DateTime') ?></label>
		<p>
			<input type="text">

		</p>
	</div>

</div><!-- //.gianism-post-container -->