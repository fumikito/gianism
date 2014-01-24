<?php


defined('ABSPATH') or die();

/** @var \Gianism\Chat\Main $this */

?>
<p>
    <a class="button" href="<?php echo $this->thread_url() ?>"><i class="return"></i> <?php $this->e('Message list') ?></a>
</p>

<?php if($this->thread->is_allowed($this->id, $this->get('thread_id'))): ?>

    <form method="post" action="<?php echo admin_url('admin-ajax.php') ?>" id="chat-form">
        <?php echo get_avatar($this->id, 96) ?>
        <input type="hidden" name="action" value="gianism_chat" />
        <?php $this->nonce_field('chat') ?>
        <input type="hidden" name="thread_id" value="<?php echo esc_attr($this->get('thread_id')) ?>" />
        <textarea name="message" placeholder="<?php $this->e('Enter message here...') ?>"></textarea>
        <?php submit_button($this->_('Send'), 'primary', '', false); ?>
    </form>

    <?php
        $chats = $this->thread->get_chats($this->get('thread_id'));
        if( !empty($chats)):
    ?>

    <ol class="chat-container">
        <?php foreach($chats as $chat): ?>
            <?php echo $this->thread->render_chat($chat); ?>
        <?php endforeach; ?>
    </ol>
    <p class="center"><a class="button-primary button-large" id="chat-more" href="<?php echo admin_url('admin-ajax.php?action=gianism-more') ?>">でかいボタン</a></p>
    <?php else: ?>

    <?php endif; ?>
<?php else: ?>
<p class="banned-thread"><?php $this->e('You have no permission to access this thread.') ?></p>
<?php endif; ?>
