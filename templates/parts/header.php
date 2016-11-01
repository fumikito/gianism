<?php
defined( 'ABSPATH' ) or die();

/** @var \Gianism\UI\Screen $this */
?>

<div class="wrap gianism-wrap">

	<div id="fb-root"></div>
	<script>(function (d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s);
			js.id = id;
			js.src = "//connect.facebook.net/ja_JP/all.js#xfbml=1&appId=983379265125123";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>

	<header class="gianism-header">
		<p class="gianism-header-text">Gianism</p>
		<p class="gianism-header-lead"><?php $this->e( 'Make your WordPress more social. Simple but Extensible.' ) ?></p>
	</header>

	<?php if ( $this->views ) : ?>
		<?php if ( 1 == count( $this->views ) ) : ?>
			<?php foreach ( $this->views as $view ) : ?>
				<h2><?= $view ?></h2>
			<?php endforeach; ?>
		<?php else : ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $this->views as $key => $label ) :
					?>
					<a class="nav-tab<?php echo ( $this->is_view( $key ) ) ? ' nav-tab-active' : '' ?>"
					   href="<?php echo $this->setting_url( $key ); ?>">
						<?php echo $label ?>
					</a>
				<?php endforeach; ?>
			</h2>
		<?php endif; ?>
	<?php endif; ?>

	<div class="gianism-inner">

		<div class="main-content">
