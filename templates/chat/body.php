<?php

defined('ABSPATH') or die();

/** @var \Gianism\Chat\Main $this */

?>
<div class="wrap" id="wpg-chat-wrap">
    <a id="contact-opener" href="#"><i></i></a>

    <h2><i></i> <?php $this->e('Message') ?></h2>

    <div id="main">
        <?php if($this->get('thread_id')): ?>
            <?php include dirname(__FILE__).'/thread.php'; ?>
        <?php else: ?>
            <?php include dirname(__FILE__).'/list.php'; ?>
        <?php endif; ?>
    </div><!-- main -->

    <div id="contact-list">

    </div><!-- #contact-list -->

</div><!-- //#wpg-chat-wrap -->