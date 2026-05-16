<?php
/**
 * Raw visual page renderer.
 *
 * Imported Sica pages keep their editable HTML in post_content. This template
 * provides the WordPress shell, then prints the stored page markup without
 * wpautop so the existing design and data-edit-id attributes remain intact.
 *
 * @package SicaVisualTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id    = get_the_ID();
$head_extra = (string) get_post_meta($post_id, '_sica_head_extra', true);
$content    = (string) get_post_field('post_content', $post_id);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <?php echo sica_rewrite_html_urls($head_extra); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</head>
<body <?php body_class('sica-visual-page'); ?>>
    <?php echo sica_prepare_page_content($content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php wp_footer(); ?>
</body>
</html>
