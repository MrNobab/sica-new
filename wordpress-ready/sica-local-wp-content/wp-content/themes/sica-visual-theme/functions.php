<?php
/**
 * Sica Visual Theme functions.
 *
 * @package SicaVisualTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SICA_VISUAL_THEME_VERSION', '1.0.0');
define('SICA_VISUAL_THEME_DIR', get_template_directory());
define('SICA_VISUAL_THEME_URI', get_template_directory_uri());

add_action('after_setup_theme', 'sica_visual_theme_setup');
add_action('after_switch_theme', 'sica_import_seed_pages');
add_action('admin_menu', 'sica_register_visual_editor_page');
add_action('rest_api_init', 'sica_register_rest_routes');
add_action('load-post.php', 'sica_redirect_page_editor');

add_filter('show_admin_bar', '__return_false');
add_filter('use_block_editor_for_post_type', 'sica_disable_block_editor_for_pages', 10, 2);

/**
 * Theme setup.
 */
function sica_visual_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}

/**
 * Disable Gutenberg for WordPress Pages while this theme is active.
 *
 * @param bool   $use_block_editor Current decision.
 * @param string $post_type        Post type name.
 * @return bool
 */
function sica_disable_block_editor_for_pages($use_block_editor, $post_type)
{
    if ($post_type === 'page') {
        return false;
    }

    return $use_block_editor;
}

/**
 * Register the fullscreen admin visual editor.
 */
function sica_register_visual_editor_page()
{
    $hook = add_menu_page(
        __('Sica Visual Editor', 'sica-visual-theme'),
        __('Sica Editor', 'sica-visual-theme'),
        'edit_pages',
        'sica-visual-editor',
        'sica_visual_editor_placeholder',
        'dashicons-edit-page',
        3
    );

    add_action('load-' . $hook, 'sica_render_visual_editor_raw');
}

/**
 * Placeholder. The real editor is emitted before the admin chrome loads.
 */
function sica_visual_editor_placeholder()
{
    echo '<div class="wrap"><h1>Sica Visual Editor</h1></div>';
}

/**
 * Render the editor as a standalone fullscreen document.
 */
function sica_render_visual_editor_raw()
{
    if (!current_user_can('edit_pages')) {
        wp_die(esc_html__('You do not have permission to access the Sica Visual Editor.', 'sica-visual-theme'));
    }

    require SICA_VISUAL_THEME_DIR . '/admin/visual-editor.php';
    exit;
}

/**
 * Redirect normal Page editing to the custom visual editor.
 */
function sica_redirect_page_editor()
{
    if (empty($_GET['post'])) {
        return;
    }

    $post_id = absint($_GET['post']);
    if (!$post_id || get_post_type($post_id) !== 'page') {
        return;
    }

    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    if (!empty($_GET['sica_native_editor'])) {
        return;
    }

    wp_safe_redirect(admin_url('admin.php?page=sica-visual-editor&sica_post=' . $post_id));
    exit;
}

/**
 * Register REST endpoints used by the editor.
 */
function sica_register_rest_routes()
{
    register_rest_route(
        'sica/v1',
        '/pages',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'sica_rest_get_pages',
            'permission_callback' => 'sica_rest_can_edit_pages',
        )
    );

    register_rest_route(
        'sica/v1',
        '/page',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'sica_rest_get_page',
            'permission_callback' => 'sica_rest_can_edit_pages',
            'args'                => array(
                'id' => array(
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ),
            ),
        )
    );

    register_rest_route(
        'sica/v1',
        '/apply-changes',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sica_rest_apply_changes',
            'permission_callback' => 'sica_rest_can_edit_pages',
        )
    );

    register_rest_route(
        'sica/v1',
        '/upload-image',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'sica_rest_upload_image',
            'permission_callback' => 'sica_rest_can_edit_pages',
        )
    );
}

/**
 * Permission callback for editor REST endpoints.
 *
 * @return bool
 */
function sica_rest_can_edit_pages()
{
    return current_user_can('edit_pages');
}

/**
 * REST: editor page tree.
 *
 * @return WP_REST_Response
 */
function sica_rest_get_pages()
{
    return rest_ensure_response(sica_get_editor_nav_tree());
}

/**
 * REST: single page metadata.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function sica_rest_get_page(WP_REST_Request $request)
{
    $post_id = absint($request->get_param('id'));
    $post    = get_post($post_id);

    if (!$post || $post->post_type !== 'page' || !current_user_can('edit_page', $post_id)) {
        return new WP_Error('sica_page_not_found', __('Page not found.', 'sica-visual-theme'), array('status' => 404));
    }

    return rest_ensure_response(sica_get_editor_page_payload($post));
}

/**
 * REST: apply data-edit-id changes to WordPress Page HTML.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function sica_rest_apply_changes(WP_REST_Request $request)
{
    $payload = $request->get_json_params();
    $changes = array();

    if (is_array($payload) && array_values($payload) === $payload) {
        $changes = $payload;
    } elseif (is_array($payload) && isset($payload['changes']) && is_array($payload['changes'])) {
        $changes = $payload['changes'];
    }

    if (!$changes) {
        return new WP_Error('sica_no_changes', __('No changes received.', 'sica-visual-theme'), array('status' => 400));
    }

    $grouped = array();
    $skipped = array();
    foreach ($changes as $change) {
        if (!is_array($change)) {
            continue;
        }

        $page     = isset($change['page']) ? $change['page'] : '';
        $edit_id  = isset($change['editId']) ? (string) $change['editId'] : '';
        $property = isset($change['property']) ? (string) $change['property'] : '';

        if ($page === '' || $edit_id === '' || $property === '') {
            $skipped[] = array('reason' => 'missing_required_fields', 'change' => $change);
            continue;
        }

        $post_id = sica_resolve_page_identifier($page);
        if (!$post_id) {
            $skipped[] = array('reason' => 'page_not_found', 'page' => $page);
            continue;
        }

        if (!isset($grouped[$post_id])) {
            $grouped[$post_id] = array();
        }

        $grouped[$post_id][] = $change;
    }

    $files  = array();
    $errors = array();
    $total  = 0;

    foreach ($grouped as $post_id => $page_changes) {
        if (!current_user_can('edit_page', $post_id)) {
            $errors[] = 'Permission denied for page ID ' . $post_id;
            continue;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'page') {
            $errors[] = 'Invalid page ID ' . $post_id;
            continue;
        }

        $original_html = (string) $post->post_content;
        $modified_html = $original_html;
        $applied       = 0;

        foreach ($page_changes as $change) {
            $edit_id  = isset($change['editId']) ? (string) $change['editId'] : '';
            $property = isset($change['property']) ? (string) $change['property'] : '';
            $value    = isset($change['newValue']) ? (string) $change['newValue'] : (isset($change['value']) ? (string) $change['value'] : '');
            $before   = $modified_html;

            if ($property === 'text') {
                $modified_html = sica_apply_text_by_edit_id($modified_html, $edit_id, $value);
            } elseif ($property === 'html') {
                $modified_html = sica_apply_html_by_edit_id($modified_html, $edit_id, $value);
            } elseif ($property === 'src') {
                $modified_html = sica_apply_src_by_edit_id($modified_html, $edit_id, $value);
            } elseif ($property === 'color') {
                $modified_html = sica_apply_style_by_edit_id($modified_html, $edit_id, 'color', $value);
            } elseif ($property === 'backgroundColor') {
                $modified_html = sica_apply_style_by_edit_id($modified_html, $edit_id, 'background-color', $value);
            } elseif ($property === 'backgroundImage') {
                if ($value !== '' && stripos($value, 'url(') !== 0) {
                    $value = 'url("' . $value . '")';
                }
                $modified_html = sica_apply_style_by_edit_id($modified_html, $edit_id, 'background-image', $value);
            }

            if ($before !== $modified_html) {
                $applied++;
            } else {
                $skipped[] = array(
                    'reason'   => 'not_applied',
                    'page'     => $post_id,
                    'editId'   => $edit_id,
                    'property' => $property,
                );
            }
        }

        if ($applied === 0) {
            continue;
        }

        $updated = wp_update_post(
            array(
                'ID'           => $post_id,
                'post_content' => wp_slash($modified_html),
            ),
            true
        );

        if (is_wp_error($updated)) {
            $errors[] = $updated->get_error_message();
            continue;
        }

        $files[(string) $post_id] = array(
            'applied' => $applied,
            'title'   => get_the_title($post_id),
            'url'     => get_permalink($post_id),
        );
        $total += $applied;
    }

    return rest_ensure_response(
        array(
            'success'  => count($errors) === 0,
            'files'    => $files,
            'errors'   => $errors,
            'total'    => $total,
            'received' => count($changes),
            'skipped'  => $skipped,
        )
    );
}

/**
 * REST: upload base64 image to the WordPress Media Library.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function sica_rest_upload_image(WP_REST_Request $request)
{
    $payload  = $request->get_json_params();
    $base64   = isset($payload['base64']) ? (string) $payload['base64'] : '';
    $filename = isset($payload['filename']) ? sanitize_file_name((string) $payload['filename']) : '';

    if ($base64 === '' || $filename === '') {
        return new WP_Error('sica_upload_missing_fields', __('Missing base64 or filename.', 'sica-visual-theme'), array('status' => 400));
    }

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (preg_match('/^data:image\/([a-z0-9+.-]+);base64,/i', $base64, $matches)) {
        $extension = strtolower($matches[1]);
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $extension = $extension === 'svg+xml' ? 'svg' : $extension;
        $base64    = preg_replace('/^data:image\/[a-z0-9+.-]+;base64,/i', '', $base64);
    }

    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif');
    if (!in_array($extension, $allowed, true)) {
        return new WP_Error('sica_upload_type', __('Unsupported image type.', 'sica-visual-theme'), array('status' => 400));
    }

    $binary = base64_decode($base64, true);
    if ($binary === false) {
        return new WP_Error('sica_upload_base64', __('Invalid base64 data.', 'sica-visual-theme'), array('status' => 400));
    }

    if (!preg_match('/\.' . preg_quote($extension, '/') . '$/i', $filename)) {
        $filename .= '.' . $extension;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $upload = wp_upload_bits($filename, null, $binary);
    if (!empty($upload['error'])) {
        return new WP_Error('sica_upload_failed', $upload['error'], array('status' => 500));
    }

    $filetype = wp_check_filetype($upload['file']);
    $attachment_id = wp_insert_attachment(
        array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ),
        $upload['file']
    );

    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }

    $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $metadata);

    return rest_ensure_response(
        array(
            'success'       => true,
            'attachment_id' => $attachment_id,
            'path'          => $upload['url'],
            'url'           => $upload['url'],
            'filename'      => basename($upload['file']),
        )
    );
}

/**
 * Build editor config for the fullscreen editor document.
 *
 * @param int $selected_id Selected page ID.
 * @return array
 */
function sica_get_editor_config($selected_id = 0)
{
    $nav_tree = sica_get_editor_nav_tree();
    $first_id = sica_get_first_nav_page_id($nav_tree);

    if (!$selected_id || !get_post($selected_id)) {
        $selected_id = $first_id;
    }

    return array(
        'nonce'      => wp_create_nonce('wp_rest'),
        'selectedId' => $selected_id,
        'rest'       => array(
            'pages'        => esc_url_raw(rest_url('sica/v1/pages')),
            'page'         => esc_url_raw(rest_url('sica/v1/page')),
            'applyChanges' => esc_url_raw(rest_url('sica/v1/apply-changes')),
            'uploadImage'  => esc_url_raw(rest_url('sica/v1/upload-image')),
        ),
        'navTree'    => $nav_tree,
    );
}

/**
 * Get the first page ID found in a nav tree.
 *
 * @param array $nav_tree Nav tree.
 * @return int
 */
function sica_get_first_nav_page_id($nav_tree)
{
    foreach ($nav_tree as $section) {
        foreach ($section['items'] as $item) {
            if (!empty($item['id'])) {
                return (int) $item['id'];
            }
            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (!empty($child['id'])) {
                        return (int) $child['id'];
                    }
                }
            }
        }
    }

    return 0;
}

/**
 * Build the editor sidebar tree from WordPress Pages.
 *
 * @return array
 */
function sica_get_editor_nav_tree()
{
    $pages = get_pages(
        array(
            'post_status' => array('publish', 'draft', 'private'),
            'sort_column' => 'menu_order,post_title',
            'sort_order'  => 'ASC',
        )
    );

    $children_by_parent = array();
    foreach ($pages as $page) {
        $children_by_parent[(int) $page->post_parent][] = $page;
    }

    $items = array();
    foreach ($children_by_parent[0] ?? array() as $page) {
        $children = array();
        foreach ($children_by_parent[(int) $page->ID] ?? array() as $child) {
            $children[] = sica_get_editor_page_payload($child);
        }

        if ($children) {
            $items[] = array(
                'label'    => html_entity_decode(get_the_title($page), ENT_QUOTES, get_bloginfo('charset')),
                'path'     => get_permalink($page),
                'id'       => (int) $page->ID,
                'children' => $children,
            );
        } else {
            $items[] = sica_get_editor_page_payload($page);
        }
    }

    return array(
        array(
            'section' => __('WordPress Pages', 'sica-visual-theme'),
            'items'   => $items,
        ),
    );
}

/**
 * Convert a WP_Post into an editor page payload.
 *
 * @param WP_Post $post Page post.
 * @return array
 */
function sica_get_editor_page_payload($post)
{
    return array(
        'id'         => (int) $post->ID,
        'label'      => html_entity_decode(get_the_title($post), ENT_QUOTES, get_bloginfo('charset')),
        'path'       => get_permalink($post),
        'previewUrl' => add_query_arg('sica_preview', '1', get_permalink($post)),
    );
}

/**
 * Import bundled static HTML files as WordPress Pages.
 */
function sica_import_seed_pages()
{
    if (get_option('sica_seed_imported_v1')) {
        return;
    }

    $seed_dir = SICA_VISUAL_THEME_DIR . '/seed/pages';
    if (!is_dir($seed_dir)) {
        return;
    }

    $files = array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($seed_dir));
    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
            continue;
        }
        $files[] = $file->getPathname();
    }
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    $front_page_id = 0;
    $menu_order    = 0;

    foreach ($files as $file_path) {
        $source_path = str_replace('\\', '/', substr($file_path, strlen($seed_dir) + 1));
        $source_html = file_get_contents($file_path);
        if ($source_html === false) {
            continue;
        }

        $parts = sica_extract_seed_html_parts($source_html, $source_path);
        $chain = sica_source_path_to_slug_chain($source_path);
        if (!$chain) {
            continue;
        }

        $parent_id = 0;
        if (count($chain) > 1) {
            for ($i = 0; $i < count($chain) - 1; $i++) {
                $parent_id = sica_get_or_create_seed_parent_page($chain[$i], $parent_id, $menu_order++);
            }
        }

        $slug = end($chain);
        $existing = sica_find_seed_page_by_source($source_path);
        $post_data = array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_title'     => $parts['title'],
            'post_name'      => $slug,
            'post_parent'    => $parent_id,
            'post_content'   => wp_slash($parts['body']),
            'post_excerpt'   => $parts['description'],
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'menu_order'     => $menu_order++,
            'meta_input'     => array(
                '_sica_source_path' => $source_path,
                '_sica_head_extra'  => $parts['head_extra'],
            ),
        );

        if ($existing) {
            $post_data['ID'] = $existing;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($post_id)) {
            continue;
        }

        if ($source_path === 'index.html') {
            $front_page_id = (int) $post_id;
        }
    }

    if ($front_page_id) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $front_page_id);
    }

    update_option('permalink_structure', '/%postname%/');
    update_option('sica_seed_imported_v1', time());
    flush_rewrite_rules();
}

/**
 * Extract title, description, head extras, and body from seed HTML.
 *
 * @param string $html        Source HTML.
 * @param string $source_path Source path.
 * @return array
 */
function sica_extract_seed_html_parts($html, $source_path)
{
    $title = sica_title_from_source_path($source_path);
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
        $title = trim(wp_strip_all_tags($matches[1]));
    }

    $description = '';
    if (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']*)["\'][^>]*>/is', $html, $matches)) {
        $description = html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
    }

    $head_extra = '';
    if (preg_match('/<head[^>]*>(.*?)<\/head>/is', $html, $matches)) {
        $head_extra = $matches[1];
        $head_extra = preg_replace('/<title[^>]*>.*?<\/title>/is', '', $head_extra);
        $head_extra = preg_replace('/<meta\s+charset=["\']?[^>"\']+["\']?\s*\/?>/is', '', $head_extra);
        $head_extra = preg_replace('/<meta\s+name=["\']viewport["\'][^>]*>/is', '', $head_extra);
    }

    $body = $html;
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
        $body = trim($matches[1]);
    }

    return array(
        'title'       => $title,
        'description' => $description,
        'head_extra'  => trim($head_extra),
        'body'        => $body,
    );
}

/**
 * Find an imported seed page by source path.
 *
 * @param string $source_path Source path.
 * @return int
 */
function sica_find_seed_page_by_source($source_path)
{
    $posts = get_posts(
        array(
            'post_type'      => 'page',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => '_sica_source_path',
            'meta_value'     => $source_path,
        )
    );

    return $posts ? (int) $posts[0] : 0;
}

/**
 * Create or reuse a hierarchy parent page.
 *
 * @param string $slug       Slug.
 * @param int    $parent_id  Parent ID.
 * @param int    $menu_order Menu order.
 * @return int
 */
function sica_get_or_create_seed_parent_page($slug, $parent_id, $menu_order)
{
    $existing = get_page_by_path($parent_id ? get_post_field('post_name', $parent_id) . '/' . $slug : $slug, OBJECT, 'page');
    if ($existing) {
        return (int) $existing->ID;
    }

    $post_id = wp_insert_post(
        array(
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_title'     => sica_title_from_slug($slug),
            'post_name'      => $slug,
            'post_parent'    => $parent_id,
            'post_content'   => '',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
            'menu_order'     => $menu_order,
        )
    );

    return is_wp_error($post_id) ? 0 : (int) $post_id;
}

/**
 * Convert a seed path to a WordPress slug hierarchy.
 *
 * @param string $source_path Source path.
 * @return array
 */
function sica_source_path_to_slug_chain($source_path)
{
    $source_path = trim(str_replace('\\', '/', $source_path), '/');
    $without_ext = preg_replace('/\.html$/i', '', $source_path);

    if ($source_path === 'index.html') {
        return array('home');
    }
    if ($source_path === 'pt/quiz/index.html') {
        return array('quiz');
    }
    if ($source_path === 'en/quiz/index.html') {
        return array('quiz-en');
    }

    $without_ext = preg_replace('#/index$#i', '', $without_ext);
    $parts = array_filter(explode('/', $without_ext), 'strlen');
    $slugs = array();
    foreach ($parts as $part) {
        $part = ltrim($part, '_');
        $slug = sanitize_title($part);
        if ($slug !== '') {
            $slugs[] = $slug;
        }
    }

    return $slugs;
}

/**
 * Title fallback from source path.
 *
 * @param string $source_path Source path.
 * @return string
 */
function sica_title_from_source_path($source_path)
{
    $chain = sica_source_path_to_slug_chain($source_path);
    return $chain ? sica_title_from_slug(end($chain)) : __('Sica Page', 'sica-visual-theme');
}

/**
 * Human title from slug.
 *
 * @param string $slug Slug.
 * @return string
 */
function sica_title_from_slug($slug)
{
    return ucwords(str_replace('-', ' ', $slug));
}

/**
 * Resolve page ID from editor change payload identifier.
 *
 * @param mixed $identifier Numeric ID, URL, or slug.
 * @return int
 */
function sica_resolve_page_identifier($identifier)
{
    if (is_numeric($identifier)) {
        return absint($identifier);
    }

    $identifier = trim((string) $identifier);
    if ($identifier === '') {
        return 0;
    }

    $path = wp_parse_url($identifier, PHP_URL_PATH);
    $path = $path ? trim($path, '/') : trim($identifier, '/');
    $path = preg_replace('/\.html$/i', '', $path);

    if ($path === '' || $path === 'index') {
        return (int) get_option('page_on_front');
    }
    if ($path === 'pt/quiz' || $path === 'pt/quiz/index') {
        $path = 'quiz';
    } elseif ($path === 'en/quiz' || $path === 'en/quiz/index') {
        $path = 'quiz-en';
    }

    $page = get_page_by_path($path, OBJECT, 'page');
    return $page ? (int) $page->ID : 0;
}

/**
 * Prepare page content for browser output.
 *
 * @param string $content Stored post content.
 * @return string
 */
function sica_prepare_page_content($content)
{
    return sica_rewrite_html_urls($content);
}

/**
 * Rewrite old static asset and page URLs for the WordPress package.
 *
 * @param string $html HTML fragment.
 * @return string
 */
function sica_rewrite_html_urls($html)
{
    if ($html === '') {
        return '';
    }

    $html = preg_replace_callback(
        '/\b(href|src)=([\'"])([^\'"]+)\2/i',
        function ($matches) {
            $url = sica_resolve_legacy_url($matches[3]);
            return $matches[1] . '=' . $matches[2] . esc_url($url) . $matches[2];
        },
        $html
    );

    $html = preg_replace_callback(
        '/url\((["\']?)([^)"\']+)\1\)/i',
        function ($matches) {
            $url = sica_resolve_legacy_url($matches[2]);
            return 'url("' . esc_url($url) . '")';
        },
        $html
    );

    return $html;
}

/**
 * Convert a legacy URL to the correct theme asset or WordPress page URL.
 *
 * @param string $url Legacy URL.
 * @return string
 */
function sica_resolve_legacy_url($url)
{
    $url = html_entity_decode(trim($url), ENT_QUOTES, 'UTF-8');
    if ($url === '') {
        return $url;
    }

    if (preg_match('#^(https?:)?//#i', $url) || preg_match('#^(mailto:|tel:|data:|javascript:)#i', $url) || str_starts_with($url, '#')) {
        return $url;
    }

    $hash = '';
    $hash_pos = strpos($url, '#');
    if ($hash_pos !== false) {
        $hash = substr($url, $hash_pos);
        $url  = substr($url, 0, $hash_pos);
    }

    $query = '';
    $query_pos = strpos($url, '?');
    if ($query_pos !== false) {
        $query = substr($url, $query_pos);
        $url   = substr($url, 0, $query_pos);
    }

    $clean = str_replace('\\', '/', $url);
    $clean = ltrim($clean, '/');
    while (str_starts_with($clean, '../')) {
        $clean = substr($clean, 3);
    }

    $static_files = array(
        'style.css',
        'blog.css',
        'artigo.css',
        'sobre.css',
        'clinicas-dentarias.css',
        'script.js',
        'blog.js',
        'artigo.js',
        'popup-newsletter.js',
        'popup-newsletter-en.js',
    );

    if (str_starts_with($clean, 'assets/') || str_starts_with($clean, 'cgi-bin/') || in_array($clean, $static_files, true)) {
        return SICA_VISUAL_THEME_URI . '/static/' . $clean . $query . $hash;
    }

    $path = trim(preg_replace('/\.html$/i', '', $clean), '/');
    $path = preg_replace('#/index$#i', '', $path);

    if ($path === '' || $path === 'index') {
        return home_url('/') . $hash;
    }
    if ($path === 'pt/quiz') {
        $path = 'quiz';
    } elseif ($path === 'en/quiz') {
        $path = 'quiz-en';
    }

    return user_trailingslashit(home_url('/' . $path)) . $query . $hash;
}

/**
 * Apply plain text to a data-edit-id element.
 *
 * @param string $html    HTML.
 * @param string $edit_id Edit ID.
 * @param string $value   New value.
 * @return string
 */
function sica_apply_text_by_edit_id($html, $edit_id, $value)
{
    $safe = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $pattern = '/(<[a-z0-9:-]+\b[^>]*\bdata-edit-id=["\']' . preg_quote($edit_id, '/') . '["\'][^>]*>)(.*?)(<\/[a-z0-9:-]+>)/is';
    return preg_replace_callback($pattern, fn($m) => $m[1] . $safe . $m[3], $html, 1);
}

/**
 * Apply inline HTML to a data-edit-id element.
 *
 * @param string $html    HTML.
 * @param string $edit_id Edit ID.
 * @param string $value   New value.
 * @return string
 */
function sica_apply_html_by_edit_id($html, $edit_id, $value)
{
    $safe = wp_kses_post($value);
    $pattern = '/(<[a-z0-9:-]+\b[^>]*\bdata-edit-id=["\']' . preg_quote($edit_id, '/') . '["\'][^>]*>)(.*?)(<\/[a-z0-9:-]+>)/is';
    return preg_replace_callback($pattern, fn($m) => $m[1] . $safe . $m[3], $html, 1);
}

/**
 * Apply image src to a data-edit-id element or child image.
 *
 * @param string $html    HTML.
 * @param string $edit_id Edit ID.
 * @param string $value   New value.
 * @return string
 */
function sica_apply_src_by_edit_id($html, $edit_id, $value)
{
    $safe = esc_url_raw($value);
    $pattern = '/(<[a-z0-9:-]+\b[^>]*\bdata-edit-id=["\']' . preg_quote($edit_id, '/') . '["\'][^>]*\bsrc=["\'])([^"\']*)(["\'][^>]*>)/i';
    $updated = preg_replace_callback($pattern, fn($m) => $m[1] . $safe . $m[3], $html, 1, $count);

    if ($count > 0) {
        return $updated;
    }

    $container_pattern = '/(<[a-z0-9:-]+\b[^>]*\bdata-edit-id=["\']' . preg_quote($edit_id, '/') . '["\'][^>]*>.*?<img\b[^>]*\bsrc=["\'])([^"\']*)(["\'][^>]*>.*?<\/[a-z0-9:-]+>)/is';
    return preg_replace_callback($container_pattern, fn($m) => $m[1] . $safe . $m[3], $html, 1);
}

/**
 * Replace a CSS declaration inside a style attribute.
 *
 * @param string $style_value Style attribute value.
 * @param string $property    CSS property.
 * @param string $new_value   CSS value.
 * @return string
 */
function sica_replace_style_property($style_value, $property, $new_value)
{
    $cleaned = preg_replace('/(^|;)\s*' . preg_quote($property, '/') . '\s*:\s*[^;]*/i', '', $style_value);
    $cleaned = trim(preg_replace('/;{2,}/', ';', $cleaned), " \t\n\r\0\x0B;");

    if ($new_value === '') {
        return $cleaned === '' ? '' : $cleaned . ';';
    }

    if ($cleaned === '') {
        return $property . ': ' . $new_value . ';';
    }

    return $cleaned . '; ' . $property . ': ' . $new_value . ';';
}

/**
 * Apply CSS style property to a data-edit-id element.
 *
 * @param string $html     HTML.
 * @param string $edit_id  Edit ID.
 * @param string $property CSS property.
 * @param string $value    CSS value.
 * @return string
 */
function sica_apply_style_by_edit_id($html, $edit_id, $property, $value)
{
    $safe_value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $tag_pattern = '/<([a-z0-9:-]+)\b[^>]*\bdata-edit-id=["\']' . preg_quote($edit_id, '/') . '["\'][^>]*>/i';

    if (!preg_match($tag_pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        return $html;
    }

    $full_tag = $matches[0][0];
    $offset   = $matches[0][1];
    $style_pattern = '/\bstyle=["\']([^"\']*)["\']/i';

    if (preg_match($style_pattern, $full_tag, $style_match)) {
        $new_style = sica_replace_style_property($style_match[1], $property, $safe_value);
        if ($new_style === '') {
            $new_tag = preg_replace($style_pattern, '', $full_tag, 1);
        } else {
            $new_tag = preg_replace_callback($style_pattern, fn() => 'style="' . $new_style . '"', $full_tag, 1);
        }
    } else {
        $new_tag = rtrim(substr($full_tag, 0, -1)) . ' style="' . $property . ': ' . $safe_value . ';">';
    }

    return substr_replace($html, $new_tag, $offset, strlen($full_tag));
}
