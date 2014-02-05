<?php

defined('ABSPATH') or die();

/** @var \Gianism\Message $this */

?>
<div class="wrap" id="wpg-chat-wrap">

    <h2><i></i> <?php $this->e('Message') ?></h2>

    <p class="description"><?php printf($this->_('These messages are send to your email address, but yours is pseudo. Please register valid email on <a href="%s">profile page</a>.'), admin_url('profile.php')) ?></p>

    <div id="main">

        <?php
        $nonce = wp_create_nonce('gianism_chat_more_'.get_current_user_id());
        global $wpdb;$wpdb->show_errors();
        $chats = $this->get_message_box(get_current_user_id());
        if( !empty($chats)):
            $oldest = 0;
            ?>
            <ol class="chat-container" data-nonce="<?php echo $nonce ?>" data-url="<?php echo admin_url('admin-ajax.php') ?>" data-action="gianism_chat_delete">
                <?php foreach($chats as $chat): $oldest = $chat->umeta_id; ?>
                    <?php echo $this->render_message($chat); ?>
                <?php endforeach; ?>
            </ol>
            <p class="loader"><i class="gianism-loader"></i></p>
            <a class="button button-large" id="chat-more" data-action="gianism_chat_older" data-nonce="<?php echo $nonce ?>" data-chat-oldest="<?php echo $oldest; ?>" href="<?php echo admin_url('admin-ajax.php') ?>"><?php $this->e('Load older') ?></a>
        <?php endif; ?>
        <p class="description no-message"><?php $this->e('No message.') ?></p>

    </div><!-- main -->

</div><!-- //#wpg-chat-wrap -->
