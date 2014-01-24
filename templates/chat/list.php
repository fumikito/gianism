<?php

defined('ABSPATH') or die();

/** @var \Gianism\Chat\Main $this */

?>

<?php
    $threads = $this->thread->get_all($this->id);
    if(empty($threads)):
?>
<p class="description">
    <?php $this->e('You have no threads.') ?>
</p>
<?php else: ?>
<ul class="thread-list">
    <?php foreach($threads as $thread): ?>
        <?php echo $this->thread->render_list($thread) ?>
    <?php endforeach; ?>
</ul>
<?php endif; ?>
