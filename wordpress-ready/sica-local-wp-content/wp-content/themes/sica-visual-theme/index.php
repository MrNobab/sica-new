<?php
/**
 * Fallback template.
 *
 * @package SicaVisualTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

if (have_posts()) {
    while (have_posts()) {
        the_post();
        require get_template_directory() . '/page.php';
    }
    return;
}

status_header(404);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('sica-empty-page'); ?>>
    <main style="font-family:system-ui,sans-serif;padding:40px;">
        <h1><?php esc_html_e('Page not found', 'sica-visual-theme'); ?></h1>
    </main>
    <?php wp_footer(); ?>
</body>
</html>
