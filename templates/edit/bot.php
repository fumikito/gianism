<?php /** @var \Gianism\Helper\Bot $this */ ?>
<?php /** @var \WP_Post $post */ ?>

<div class="gianism-post-container">

	<div class="form-row">
		<label for="content"><i class="lsf lsf-balloon"></i> <?php printf($this->_('What do you tweet as <a href="https://twitter.com/%1$s" target="_blank">@%1$s</a>?'), $this->twitter->tw_screen_name) ?></label>
		<textarea id="content" name="content" placeholder="<?php echo esc_attr($this->_('Enter your tweet.')) ?>"><?php echo esc_textarea($post->post_content) ?></textarea>
		<p class="description">
			<?php $this->e('HTML tags are not allowed. But you can use short codes. Gianism original short codes are below.') ?>
			<?php printf($this->_('And ofcourse You can register your own shortcode with <a href="%s">Shortcode API</a>.'), 'http://codex.wordpress.org/Shortcode_API') ?>
		</p>
		<hr />
		<h4>gianism_limit</h4>
		<p>
			<em><?php $this->e('Attributes') ?></em>:
			<code>limit</code> <small><?php $this->e('Date time. This will convert to rest time which is relative to current time.') ?>
			 |
			<code>placeholder</code> <small><?php printf($this->_('Place holder. Default is %s.'), $this->_('%s left')) ?></small>
		</p>
<pre>
[gianism_limit limit='2016-08-16'] => <?php echo do_shortcode('[gianism_limit limit=\'2016-08-16\']') ?>
</pre>
	</div>

	<hr />

	<div class="form-row">
		<table class="date-table" id="gianism-bot-schedule">
			<caption><i class="lsf lsf-time"></i> <?php $this->e('Schedule') ?></caption>
			<thead>
				<tr>
					<th>&nbsp;</th>
					<?php for($i = 0; $i < 7; $i++): ?>
						<th scope="col"><?php echo date_i18n('D', strtotime('Last Monday', current_time('timestamp')) + 60 * 60 * 24 * $i ) ?></th>
					<?php endfor ?>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>&nbsp;</th>
					<td colspan="8">
						<select id="g-row-time">
							<?php for($i = 0; $i < 24; $i++): ?>
							<option><?php printf('%02d', $i) ?></option>
							<?php endfor; ?>
						</select>:
						<select id="g-row-minute">
							<?php for($i = 0; $i < 6; $i++): ?>
								<option><?php printf('%02d', $i * 10) ?></option>
							<?php endfor; ?>
						</select>
						<a class="button add-row" href="#"><?php $this->e('Add Time Row') ?></a>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php foreach( $this->get_time_line($post) as $time => $dates ): ?>
					<tr>
						<th><?php echo substr($time, 0, 5) ?></th>
						<?php for($i = 1; $i <= 7; $i++): ?>
							<td><input type="checkbox" name="gianism_bot_schedule[<?php echo $time ?>][]" value="<?php echo $i ?>"<?php checked(false !== array_search($i, $dates)) ?> /></td>
						<?php endfor ?>
						<td><a class="button row-delete" href="#">&times;</a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<script type="text/javascript">
			//<![CDATA[
			window.GianismBotSchedule = <?php echo json_encode($this->get_schedule($post)) ?>;
			//]]>
		</script>
	</div>

	<?php
		$limit = $this->cron_limit($post);
		if( !empty($limit) && !preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/u', $limit) ):
	?>
	<div class="error">
		<p><?php $this->e('End datetime is mal-formed: YYYY-MM-DD') ?></p>
	</div>
	<?php endif; ?>

	<hr />

	<div class="form-row">
		<label for="tweet_ends"><i class="lsf lsf-dailycalendar"></i> <?php $this->e('End Datetime') ?></label>
		<p>
			<input type="date" class="regular-text" name="tweet_ends" id="tweet_ends" value="<?php echo esc_html($limit) ?>" placeholder="ex. 2014-08-16" autocomplete="on">
		</p>
		<p class="description">
			<?php $this->e('Passing this limit, This bot will be private. Format is YYYY-MM-DD') ?>
		</p>
	</div>


</div><!-- //.gianism-post-container -->