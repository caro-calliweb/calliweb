<?php
/*
Plugin Name:  Simple Post Redirect
Plugin URI: https://wordpress.org/plugins/simple-post-redirect/
Description: Quickly redirect individual posts, pages, and custom post types to any internal or external URL.
Author: Mohit Agarwal
Author URI: https://simpleproplugins.com/product/simple-page-redirect/
Version: 1.8.1
Text Domain: simple-post-redirect
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


Simple Post Redirect is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Simple Post Redirect is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Simple Post Redirect. If not, see http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * @package Simple Post Redirect
 * @version 1.8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Returns a list of excluded post types where redirection is not applicable.
 *
 * @return array
 */
function me_spr_get_excluded_post_types() {
    return array(
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'wp_navigation',
        'wp_font_family',
        'wp_font_face',
    );
}

/**
 * Checks if a given post type is supported for redirects.
 *
 * @param string $post_type
 * @return bool
 */
function me_spr_is_post_type_supported($post_type) {
    if (empty($post_type) || !is_string($post_type)) {
        return false;
    }
    return !in_array($post_type, me_spr_get_excluded_post_types(), true);
}

/**
 * Register metabox on all supported post types.
 */
function me_spr_redirect_add_meta_boxes() {
    foreach (array_keys($GLOBALS['wp_post_types']) as $post_type) {
        if (!me_spr_is_post_type_supported($post_type)) {
            continue;
        }

        add_meta_box(
            'me_spr_redirect_url',
            __('Simple Redirect to:', 'simple-post-redirect'),
            'me_spr_redirect_callback_function',
            $post_type,
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'me_spr_redirect_add_meta_boxes');

/**
 * Render redirect metabox content.
 *
 * @param WP_Post|null $post
 */
function me_spr_redirect_callback_function($post) {
    $post_id = is_object($post) ? $post->ID : get_the_ID();
    $saved_url = get_post_meta($post_id, 'me_spr_post_redirect', true);

    wp_nonce_field('me_spr_redirect_nonce_action', 'me_spr_redirect_nonce');

    echo '<p style="margin:4px 0 6px 0;font-size:13px;">' . esc_html__('Enter redirect destination:', 'simple-post-redirect') . '</p>';
    echo '<input style="width:100%;margin-bottom:8px;padding:6px;" placeholder="' .
        esc_attr__('https://example.com/new-page', 'simple-post-redirect') .
        '" type="text" name="me_spr_post_redirect" value="' . esc_attr($saved_url) . '">';

    echo '<div style="margin-top:8px;padding:8px 10px;background:#f6f7f7;border-left:3px solid #2271b1;font-size:12px;line-height:1.4;">' .
        '<strong>' . esc_html__('Simple Page Redirect Premium', 'simple-post-redirect') . '</strong><br>' .
        esc_html__('Manage redirects from a Central Redirect Dashboard, get hit tracking, bulk editing, labels, expiration dates, exports, and priority support.', 'simple-post-redirect') .
        '<br><a href="https://simpleproplugins.com/product/simple-page-redirect/" target="_blank" rel="noopener noreferrer" style="display:inline-block;margin-top:6px;font-weight:600;text-decoration:none;">' .
        esc_html__('Learn more about Premium &rarr;', 'simple-post-redirect') .
        '</a></div>';
}

/**
 * Load plugin textdomain.
 */
function me_spr_redirect_load_plugin_textdomain() {
    load_plugin_textdomain('simple-post-redirect', false, basename(dirname(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'me_spr_redirect_load_plugin_textdomain');

/**
 * Safely sanitize and validate a redirect destination URL.
 * Supports absolute HTTP/HTTPS URLs, protocol-relative URLs, and relative paths.
 *
 * @param string $url
 * @return string Sanitized URL or empty string if invalid.
 */
function me_spr_sanitize_redirect_url($url) {
    if (!is_string($url)) {
        return '';
    }

    $url = trim($url);
    if ($url === '' || $url === 'http://' || $url === 'https://') {
        return '';
    }

    // Standalone anchor fragments (e.g. #section) cannot serve as a page redirect
    if (strpos($url, '#') === 0) {
        return '';
    }

    $clean_url = esc_url_raw($url, array('http', 'https'));

    if ($clean_url === '' || $clean_url === 'http://' || $clean_url === 'https://') {
        return '';
    }

    return $clean_url;
}

/**
 * Normalizes a URL for comparison during self-redirect detection.
 *
 * @param string $url
 * @return string
 */
function me_spr_normalize_url_for_comparison($url) {
    if (empty($url) || !is_string($url)) {
        return '';
    }

    $url = trim($url);

    // Protocol-relative URL
    if (strpos($url, '//') === 0) {
        $url = (is_ssl() ? 'https:' : 'http:') . $url;
    }

    $parsed = wp_parse_url($url);
    if (!$parsed) {
        return '';
    }

    $home_parsed = wp_parse_url(home_url());

    if (empty($parsed['host'])) {
        // Relative path: resolve against site home
        $scheme = !empty($home_parsed['scheme']) ? strtolower($home_parsed['scheme']) : 'http';
        $host   = !empty($home_parsed['host']) ? strtolower($home_parsed['host']) : '';
        $port   = !empty($home_parsed['port']) ? (int) $home_parsed['port'] : null;

        $raw_path  = !empty($parsed['path']) ? $parsed['path'] : '/';
        $home_path = !empty($home_parsed['path']) ? rtrim($home_parsed['path'], '/') : '';

        if ($home_path !== '' && strpos($raw_path, $home_path) !== 0) {
            $path = $home_path . '/' . ltrim($raw_path, '/');
        } else {
            $path = $raw_path;
        }
    } else {
        $scheme = !empty($parsed['scheme']) ? strtolower($parsed['scheme']) : 'http';
        $host   = strtolower($parsed['host']);
        $port   = !empty($parsed['port']) ? (int) $parsed['port'] : null;
        $path   = !empty($parsed['path']) ? $parsed['path'] : '/';
    }

    if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
        $port = null;
    }

    $path = rtrim($path, '/');
    if ($path === '') {
        $path = '/';
    }

    $query_str = '';
    if (!empty($parsed['query'])) {
        wp_parse_str($parsed['query'], $query_args);
        ksort($query_args);
        $query_str = http_build_query($query_args);
    }

    $normalized = $scheme . '://' . $host . ($port ? ':' . $port : '') . $path;
    if ($query_str !== '') {
        $normalized .= '?' . $query_str;
    }

    return $normalized;
}

/**
 * Checks whether a target redirect URL resolves back to the same post (self-redirect loop).
 *
 * @param int $post_id
 * @param string $target_url
 * @return bool
 */
function me_spr_is_self_redirect($post_id, $target_url) {
    if (empty($target_url) || !is_string($target_url)) {
        return false;
    }

    $permalink = get_permalink($post_id);
    if (!$permalink) {
        return false;
    }

    $norm_target    = me_spr_normalize_url_for_comparison($target_url);
    $norm_permalink = me_spr_normalize_url_for_comparison($permalink);

    if (empty($norm_target) || empty($norm_permalink)) {
        return false;
    }

    if ($norm_target === $norm_permalink) {
        return true;
    }

    $home_parsed   = wp_parse_url(home_url());
    $target_parsed = wp_parse_url($target_url);

    // If target has a host and it's different from site host, it's external - not a self-redirect
    if (!empty($target_parsed['host'])) {
        $site_host   = !empty($home_parsed['host']) ? strtolower($home_parsed['host']) : '';
        $target_host = strtolower($target_parsed['host']);
        if ($target_host !== $site_host) {
            return false;
        }
    }

    $permalink_parsed = wp_parse_url($permalink);
    $permalink_path   = isset($permalink_parsed['path']) ? rtrim($permalink_parsed['path'], '/') : '';
    $target_path      = isset($target_parsed['path']) ? rtrim($target_parsed['path'], '/') : '';

    $home_path = isset($home_parsed['path']) ? rtrim($home_parsed['path'], '/') : '';
    if ($home_path !== '' && strpos($target_path, $home_path) !== 0) {
        $alt_target_path = rtrim($home_path . '/' . ltrim($target_path, '/'), '/');
    } else {
        $alt_target_path = $target_path;
    }

    $target_query = array();
    if (!empty($target_parsed['query'])) {
        wp_parse_str($target_parsed['query'], $target_query);
        ksort($target_query);
    }

    $permalink_query = array();
    if (!empty($permalink_parsed['query'])) {
        wp_parse_str($permalink_parsed['query'], $permalink_query);
        ksort($permalink_query);
    }

    if (($target_path === $permalink_path || $alt_target_path === $permalink_path) && $target_query === $permalink_query) {
        return true;
    }

    return false;
}

/**
 * Returns current requested URL.
 *
 * @return string
 */
function me_spr_get_current_url() {
    $scheme = is_ssl() ? 'https://' : 'http://';
    $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
    $uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (empty($host)) {
        return home_url($uri);
    }
    return $scheme . $host . $uri;
}

/**
 * Save metabox data.
 * Cleans empty entries to prevent saving unnecessary empty meta rows.
 *
 * @param int $post_id
 */
function me_spr_redirect_metabox_save($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (!isset($_POST['me_spr_redirect_nonce']) || !wp_verify_nonce($_POST['me_spr_redirect_nonce'], 'me_spr_redirect_nonce_action')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['me_spr_post_redirect'])) {
        return;
    }

    $raw_url   = wp_unslash($_POST['me_spr_post_redirect']);
    $clean_url = me_spr_sanitize_redirect_url($raw_url);

    // If cleared or points back to itself, remove the meta value
    if ($clean_url === '' || me_spr_is_self_redirect($post_id, $clean_url)) {
        delete_post_meta($post_id, 'me_spr_post_redirect');
    } else {
        update_post_meta($post_id, 'me_spr_post_redirect', $clean_url);
    }
}
add_action('save_post', 'me_spr_redirect_metabox_save');

/**
 * Handle frontend redirects.
 * Excludes admin, ajax, cron, and preview workflows to avoid disrupting editing.
 */
function me_spr_permalink_redirect() {
    if (is_admin() || wp_doing_ajax() || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }

    if (is_preview() || is_customize_preview()) {
        return;
    }

    if (!is_singular()) {
        return;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
        return;
    }

    $url = get_post_meta($post_id, 'me_spr_post_redirect', true);
    if (empty($url)) {
        return;
    }

    $url = me_spr_sanitize_redirect_url($url);
    if (empty($url)) {
        return;
    }

    // Prevent redirect loop to same post permalink
    if (me_spr_is_self_redirect($post_id, $url)) {
        return;
    }

    // Prevent redirect loop to current request URL
    $current_url = me_spr_get_current_url();
    if (!empty($current_url) && me_spr_normalize_url_for_comparison($url) === me_spr_normalize_url_for_comparison($current_url)) {
        return;
    }

    wp_redirect($url, 301);
    exit;
}
add_action('template_redirect', 'me_spr_permalink_redirect');

/**
 * Enqueue editor-only CSS styles.
 * Uses enqueue_block_editor_assets so styles never leak onto the frontend.
 */
function me_spr_enqueue_block_editor_assets() {
    wp_enqueue_style(
        'me-spr-block-styles',
        plugin_dir_url(__FILE__) . 'css/block-styles.min.css',
        array(),
        '1.8.1'
    );
}
add_action('enqueue_block_editor_assets', 'me_spr_enqueue_block_editor_assets');

/**
 * Add Redirect column to post, page, and custom post type list tables.
 *
 * @param array $columns
 * @param string $post_type
 * @return array
 */
function me_spr_add_redirect_column($columns, $post_type = '') {
    if (!empty($post_type) && !me_spr_is_post_type_supported($post_type)) {
        return $columns;
    }

    $columns['me_spr_redirect'] = __('Redirect', 'simple-post-redirect');
    return $columns;
}
add_filter('manage_posts_columns', 'me_spr_add_redirect_column', 10, 2);
add_filter('manage_pages_columns', 'me_spr_add_redirect_column');

/**
 * Render Redirect column content in admin list tables.
 *
 * @param string $column_name
 * @param int $post_id
 */
function me_spr_render_redirect_column($column_name, $post_id) {
    if ($column_name !== 'me_spr_redirect') {
        return;
    }

    $redirect_url = get_post_meta($post_id, 'me_spr_post_redirect', true);

    // Hidden value storage for Quick Edit JavaScript
    echo '<span class="me-spr-redirect-val" style="display:none;">' . esc_html($redirect_url) . '</span>';

    if (!empty($redirect_url)) {
        $display_url = $redirect_url;
        if (strlen($display_url) > 42) {
            $display_url = substr($display_url, 0, 39) . '...';
        }
        echo '<a href="' . esc_url($redirect_url) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr($redirect_url) . '" style="text-decoration:none;">' .
            '<span style="color:#2271b1;font-weight:500;">&rarr; ' . esc_html($display_url) . '</span>' .
            '</a>';
    } else {
        echo '<span style="color:#888;">' . esc_html__('No redirect', 'simple-post-redirect') . '</span>';
    }
}
add_action('manage_posts_custom_column', 'me_spr_render_redirect_column', 10, 2);
add_action('manage_pages_custom_column', 'me_spr_render_redirect_column', 10, 2);

/**
 * Add Quick Edit field for redirect URL on list tables.
 *
 * @param string $column_name
 * @param string $post_type
 */
function me_spr_quick_edit_custom_box($column_name, $post_type) {
    if ($column_name !== 'me_spr_redirect' || !me_spr_is_post_type_supported($post_type)) {
        return;
    }

    wp_nonce_field('me_spr_redirect_nonce_action', 'me_spr_redirect_nonce');
    ?>
    <fieldset class="inline-edit-col-right inline-edit-me-spr">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <span class="title"><?php esc_html_e('Redirect URL', 'simple-post-redirect'); ?></span>
                <span class="input-text-wrap">
                    <input type="text" name="me_spr_post_redirect" class="me-spr-quick-edit-url" value="" placeholder="<?php esc_attr_e('https://example.com/new-page', 'simple-post-redirect'); ?>" />
                </span>
            </label>
            <p class="description" style="font-size:11px;color:#666;margin:3px 0 0 0;">
                <?php esc_html_e('Leave blank to remove redirect.', 'simple-post-redirect'); ?>
                <span style="display:inline-block;margin-left:6px;">
                    <?php esc_html_e('Need bulk redirects?', 'simple-post-redirect'); ?>
                    <a href="https://simpleproplugins.com/product/simple-page-redirect/" target="_blank" rel="noopener noreferrer">
                        <?php esc_html_e('Premium provides bulk editing', 'simple-post-redirect'); ?> &rarr;
                    </a>
                </span>
            </p>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'me_spr_quick_edit_custom_box', 10, 2);

/**
 * Admin JavaScript to populate Quick Edit field from row data.
 *
 * @param string $hook
 */
function me_spr_quick_edit_scripts($hook) {
    if ($hook !== 'edit.php') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        if (typeof inlineEditPost === 'undefined') {
            return;
        }
        var wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            wp_inline_edit.apply(this, arguments);
            var postId = 0;
            if (typeof id === 'object') {
                postId = parseInt(this.getId(id), 10);
            }
            if (postId > 0) {
                var editRow = $('#edit-' + postId);
                var postRow = $('#post-' + postId);
                var val = postRow.find('.me-spr-redirect-val').text();
                editRow.find('input[name="me_spr_post_redirect"]').val(val);
            }
        };
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'me_spr_quick_edit_scripts');

/**
 * Add settings/overview menu page under Settings.
 */
function me_spr_add_options_page() {
    add_options_page(
        __('Simple Page Redirect', 'simple-post-redirect'),
        __('Simple Page Redirect', 'simple-post-redirect'),
        'manage_options',
        'simple-page-redirect',
        'me_spr_render_settings_page'
    );
}
add_action('admin_menu', 'me_spr_add_options_page');

/**
 * Render settings/overview admin page.
 */
function me_spr_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap" style="max-width:960px;">
        <h1><?php esc_html_e('Simple Page Redirect', 'simple-post-redirect'); ?></h1>
        <p class="description" style="font-size:14px;margin-bottom:20px;">
            <?php esc_html_e('Quickly redirect individual posts, pages, and custom post types to any destination URL.', 'simple-post-redirect'); ?>
        </p>

        <div style="background:#fff;border:1px solid #ccd0d4;padding:20px 24px;border-radius:4px;margin-bottom:24px;">
            <h2 style="margin-top:0;"><?php esc_html_e('How to Use (Free Version)', 'simple-post-redirect'); ?></h2>
            <ol style="line-height:1.8;font-size:14px;margin-left:20px;">
                <li><strong><?php esc_html_e('Edit any Post or Page:', 'simple-post-redirect'); ?></strong> <?php esc_html_e('Open the WordPress editor and locate the "Simple Redirect to:" box on the right sidebar.', 'simple-post-redirect'); ?></li>
                <li><strong><?php esc_html_e('Enter the Target URL:', 'simple-post-redirect'); ?></strong> <?php esc_html_e('Type any internal path (/new-page) or full external URL (https://example.com).', 'simple-post-redirect'); ?></li>
                <li><strong><?php esc_html_e('Quick Edit from Post Lists:', 'simple-post-redirect'); ?></strong> <?php esc_html_e('View the "Redirect" column on your post/page tables and update destinations instantly via Quick Edit.', 'simple-post-redirect'); ?></li>
                <li><strong><?php esc_html_e('To Remove a Redirect:', 'simple-post-redirect'); ?></strong> <?php esc_html_e('Simply clear the input field and save the post.', 'simple-post-redirect'); ?></li>
            </ol>
        </div>

        <div style="background:#fff;border:1px solid #ccd0d4;padding:20px 24px;border-radius:4px;">
            <h2 style="margin-top:0;"><?php esc_html_e('Simple Page Redirect Free vs. Premium', 'simple-post-redirect'); ?></h2>
            <p style="font-size:14px;color:#555;">
                <?php esc_html_e('The Free plugin provides lightweight individual redirects. For sites managing larger redirect collections, Simple Page Redirect Premium unlocks advanced management tools.', 'simple-post-redirect'); ?>
            </p>

            <table class="widefat striped" style="margin-top:16px;max-width:850px;">
                <thead>
                    <tr>
                        <th style="font-size:14px;width:55%;"><strong><?php esc_html_e('Feature', 'simple-post-redirect'); ?></strong></th>
                        <th style="font-size:14px;text-align:center;width:22%;"><strong><?php esc_html_e('Free', 'simple-post-redirect'); ?></strong></th>
                        <th style="font-size:14px;text-align:center;width:23%;background:#f0f6fc;color:#0969da;"><strong><?php esc_html_e('Premium', 'simple-post-redirect'); ?></strong></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Individual Post/Page Redirects', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Redirect any single post, page, or custom post type.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;">&#10004;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Internal & External URLs', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Redirect to any URL with query parameters or fragments.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;">&#10004;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Loop & Self-Redirect Protection', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Prevents accidental self-redirect loops.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;">&#10004;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Admin List Table Column & Quick Edit', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('View and quick-edit individual redirect destinations.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;">&#10004;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Central Redirect Dashboard', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('View, search, filter, and manage all site redirects from one central hub.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Redirect Hit Tracking', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Track redirect visits and last-accessed timestamps.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Bulk Editing Across Posts', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Apply, edit, or remove redirects on multiple posts simultaneously.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Custom Redirect Labels', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Organize and tag redirects for easy filtering.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Expiration Dates', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Set scheduled end dates to automatically disable temporary redirects.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Export Tools', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Export redirect configurations for backups and migration.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Priority Support', 'simple-post-redirect'); ?></strong><br><small style="color:#666;"><?php esc_html_e('Fast email support from the plugin developers.', 'simple-post-redirect'); ?></small></td>
                        <td style="text-align:center;color:#888;">&mdash;</td>
                        <td style="text-align:center;font-size:16px;color:#00a32a;background:#f8fafc;">&#10004;</td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top:24px;">
                <a href="https://simpleproplugins.com/product/simple-page-redirect/" target="_blank" rel="noopener noreferrer" class="button button-primary button-large" style="font-size:14px;height:38px;line-height:36px;padding:0 20px;">
                    <?php esc_html_e('Learn More About Simple Page Redirect Premium &rarr;', 'simple-post-redirect'); ?>
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Add settings action link on plugins list page.
 *
 * @param array $links
 * @return array
 */
function me_spr_plugin_action_links($links) {
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=simple-page-redirect')) . '">' . esc_html__('Settings', 'simple-post-redirect') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'me_spr_plugin_action_links');

/**
 * Add Premium link to plugin row meta on plugins list page.
 *
 * @param array $links
 * @param string $file
 * @return array
 */
function me_spr_add_premium_support_link($links, $file) {
    if (strpos($file, 'simple-post-redirect.php') !== false) {
        $links[] = '<a style="color:#d63638;font-weight:600;" href="https://simpleproplugins.com/product/simple-page-redirect/" target="_blank" rel="noopener noreferrer">' . esc_html__('Upgrade to Pro', 'simple-post-redirect') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'me_spr_add_premium_support_link', 10, 2);
