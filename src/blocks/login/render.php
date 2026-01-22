<?php
/**
 * Server-side rendering of the `gianism/login` block.
 *
 * @package Gianism
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

$redirect_to        = $attributes['redirectTo'] ?? '';
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo $wrapper_attributes; ?>>
	<?php gianism_login( '', '', $redirect_to ); ?>
</div>
