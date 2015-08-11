<?php

defined('ABSPATH') or die();

/** @var \Gianism\Admin $this */
/** @var \Gianism\Option $option */
?>

<div class="wrap gianism-wrap">

<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1&appId=264573556888294";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<h2 class="nav-tab-wrapper">
	<a class="nav-tab<?php if( !isset($_REQUEST['view']) ) echo ' nav-tab-active'; ?>" href="<?php echo $this->setting_url();?>">
		<i class="lsf lsf-paramater"></i> <?php $this->e('Gianism Setting'); ?>
	</a>
	<?php
		$pages = array(
			'setup' => array($this->_('How to set up'), 'help'),
			'customize' => array($this->_('Customize'), 'wrench'),
			'advanced' => array($this->_('Advanced Usage'), 'magic'),
		);
		if( $option->fb_use_api ){
			$pages['fb-api'] = array($this->_('Facebook API'), 'facebook');
		}
		foreach( $pages as $key => $val ):
	?>
	<a class="nav-tab<?php if( $this->is_view($key) ) echo ' nav-tab-active'; ?>" href="<?php echo $this->setting_url($key);?>">
        <i class="lsf lsf-<?php echo $val[1] ?>"></i> <?php echo $val[0] ?>
	</a>
	<?php endforeach; ?>
</h2>

<br class="clear" />

<?php include __DIR__.'/sidebar.php' ?>

<div class="main-content">

    <?php if( $this->is_view('setting') ): ?>
        <img class="gian" src="<?php echo $this->url; ?>assets/compass/img/gian.png" width="128" height="128" alt="gian" />
        <q class="copy">&ldquo;<?php $this->e('What you have is mine, what I have is also mine!'); ?>&rdquo;<cite> - <?php $this->e('Takeshi Gōda') ?></cite></q>
        <p class="lead"><?php echo gianism_description(); ?></p>
    <?php endif; ?>

    <p class="last-updated">
        Version <?php echo $this->version; ?><br />
        <?php printf($this->_('Last Updated: %s'), mysql2date(get_option('date_format'), GIANISM_DOC_UPDATED)); ?>
    </p>

    <?php if( !$this->is_view('setting') ): ?>
        <p><?php printf($this->_('API document is also available at <a href="%s">Developer\'s site</a>. You can use various functions.'), $this->ga_link('http://takahashifumiki.com/api/gianism/')) ?></p>
    <?php endif; ?>


    <?php
	switch($this->request('view')):
		case 'setup':
            $this->get_template('how-to-setup');
			break;
		case 'customize':
            $this->get_template('customize');
			break;
		case 'advanced':
            $this->get_template('advanced');
			break;
		case 'fb-api':
			if( $option->fb_use_api ){
				$this->get_template('fbapi');
			}else{
				printf('<div class="error"><p>%s</p></div>', $this->_('To see this page, you should turn <strong>Facebook API</strong> on.'));
			}
			break;
		default:
            $this->get_template('setting');
			break;
	endswitch;
?>

</div><!-- //.main-content -->
	
<br class="clear" />

</div><!-- //.gianism-wrap -->
