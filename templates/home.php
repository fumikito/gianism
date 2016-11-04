<?php

defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\SettingScreen $this */

?>

<div class="gianism-home">

	<blockquote class="gianism-home-quote">
		<i class="lsf lsf-quote"></i>
		<p>
			<?php $this->e( 'What you have is mine, what I have is also mine!' ); ?>
		</p>
		<cite>- <?php $this->e( 'Takeshi Gōda' ) ?> -</cite>
	</blockquote>

	<p class="gianism-home-lead">
		<?php $this->e( 'Gianism let your site <strong>more social</strong>. Your users can log in to your site via popular SNS.' ) ?>
		<?php $this->e( 'Besides that, some powerful add-ons are available. Your user can be your evangelist. You can even automate your own WordPress. Interact with APIs and make something great!' ) ?>
	</p>

	<table class="gianism-home-table">
		<caption><?php $this->e( 'Available Services' ) ?></caption>
		<thead>
			<tr>
				<th><?php $this->e( 'Name' ) ?></th>
				<th class="status"><?php $this->e( 'Status' ) ?></th>
				<th class="type"><?php $this->e( 'Type' ) ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $this->service->service_list() as $service ) : ?>
			<tr>
				<td><?php echo esc_html( $service['label'] ) ?></td>
				<td class="status">
					<?php if ( $service['enabled'] ) : ?>
						<i class="lsf lsf-check" style="color: green; font-size: 1.4em;"></i>
					<?php else : ?>
						<i class="lsf lsf-ban" style="color: lightgrey; font-size: 1.4em;"></i>
					<?php endif; ?>
				</td>
				<td class="type">
					<?php if ( $service['default'] ) : ?>
						<?php $this->e( 'Default' ) ?>
					<?php else : ?>
						<?php $this->e( 'Add on' ) ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<p class="description">
		<?php printf(
			$this->_( 'You can enable all services via <a href="%1$s">Setting</a> and find more services at <a href="%2$s">gianism.info</a>.' ),
			$this->setting_url( 'setting' ),
			gianism_utm_link( 'https://gianism.info/', [ 'utm-medium' => 'dashboard' ] )
		); ?>
	</p>


	<table class="gianism-home-table">
		<caption><?php $this->e( 'Add-ons' ) ?></caption>
		<thead>
		<tr>
			<th><?php $this->e( 'Name' ) ?></th>
			<th><?php $this->e( 'Description' ) ?></th>
			<th class="status"><?php $this->e( 'Status' ) ?></th>
			<th class="type"><?php $this->e( 'Type' ) ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $this->service->get_plugins() as $plugin ) : /** @var \Gianism\Plugins\PluginBase $plugin */ ?>
			<tr>
				<td><?php echo esc_html( $plugin->plugin_name ) ?></td>
				<td>
					<?php echo esc_html( $plugin->plugin_description() ) ?>
				</td>
				<td class="status">
					<?php if ( $plugin->plugin_enabled() ) : ?>
						<i class="lsf lsf-check" style="color: green; font-size: 1.4em;"></i>
					<?php else : ?>
						<i class="lsf lsf-ban" style="color: lightgrey; font-size: 1.4em;"></i>
					<?php endif; ?>
				</td>
				<td class="type">
					<?php if ( $this->service->is_default_plugin( $plugin->plugin_name ) ) : ?>
						<?php $this->e( 'Default' ) ?>
					<?php else : ?>
						<?php $this->e( 'Add on' ) ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<p class="description">
		<?php printf(
			$this->_( 'Are you interested with add-ons? Find more at <a href="%s">gianism.info</a>!' ),
			gianism_utm_link( 'https://gianism.info/', [ 'utm-medium' => 'dashboard' ] )
		); ?>
	</p>

</div>
