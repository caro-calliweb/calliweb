<?php
if (!defined("ABSPATH")) {
    exit();
}
class JVM_Richtext_icons
{
    /**
     * This plugin's instance.
     *
     * @var JVM_Richttext_icons
     */
    private static $instance;

    /**
     * Registers the plugin.
     */
    public static function register()
    {
        if (null === self::$instance) {
            self::$instance = new JVM_Richtext_icons();
        }
    }

    /**
     * The Constructor.
     */
    private function __construct()
    {
        add_filter("block_editor_settings_all", [$this, "block_editor_settings"], 10, 2);
        // NOTE: icon CSS is loaded exclusively via enqueue_block_assets (which
        // covers the frontend, the editor canvas AND the editor iframe). Never
        // enqueue it on init/admin_enqueue_scripts: styles added outside
        // enqueue_block_assets are copied into the editor iframe incorrectly
        // ("was added to the iframe incorrectly" warning) and can go missing.
        add_action("enqueue_block_assets", [$this, "load_css"]);
        add_action("admin_enqueue_scripts", [$this, "load_admin_assets"]);
        add_filter("plugin_action_links", [$this, "plugin_action_links"], 10, 2);
        add_filter("plugin_row_meta", [$this, "plugin_row_meta"], 10, 2);
        add_action("template_redirect", [$this, "maybe_start_inline_svg_buffer"]);
        add_action("admin_notices", [$this, "maybe_show_review_notice"]);
        add_action("wp_ajax_jvm_richtext_dismiss_review", [$this, "dismiss_review_notice"]);

        // One-time content migrations (e.g. space -> U+200B inside icon tags).
        // Runs on admin_init so it also fires after a plugin update; the
        // version flag guarantees each migration runs exactly once.
        add_action("admin_init", [$this, "maybe_run_migrations"]);

        if (defined("WP_CLI") && WP_CLI && class_exists("WP_CLI")) {
            \WP_CLI::add_command("jvm-icons migrate", [$this, "cli_migrate"]);
        }

        // Allow "icon" and "iconColor" to be overridden per-instance via Pattern
        // Overrides / Block Bindings when this block is placed inside a synced pattern.
        add_filter("block_bindings_supported_attributes_jvm/single-icon", [$this, "block_bindings_supported_attributes"]);

        /**
         * Register Gutenberg block on server-side from block.json.
         *
         * @link https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/
         * @since 1.16.0
         */
        register_block_type(plugin_dir_path(__DIR__) . "src", [
            "editor_script" => "jvm-rich-text-icons-js",
            "editor_style" => "jvm-rich-text-icons-editor-css",
            "render_callback" => [$this, "render_single_icon_block"],
        ]);
    }

    /**
     * Server-side render for the Single icon block.
     *
     * The block also has a static JS `save()` for content portability, but a
     * render_callback is required for Pattern Overrides to actually take effect
     * on the front end: WordPress only merges override values into $attributes
     * for blocks with a render_callback. Without one, the front end just echoes
     * the static saved HTML verbatim and per-instance overrides are silently
     * ignored (overrides only patch HTML for attributes with a "source" in
     * block.json, which "icon"/"iconColor" intentionally are not).
     *
     * @param array $attributes Block attributes, already merged with any
     *                          Pattern Overrides values by WP_Block::render().
     * @return string
     */
    public function render_single_icon_block($attributes)
    {
        $class_prefix = self::get_class_prefix();
        $icon_class = trim($class_prefix . " " . ($attributes["icon"] ?? ""));
        $font_size = isset($attributes["fontSize"]) ? (int) $attributes["fontSize"] : 32;

        $style = "font-size:" . $font_size . "px";
        if (!empty($attributes["iconColor"])) {
            $style .= ";color:" . $attributes["iconColor"];
        }

        $icon_html = sprintf(
            '<i class="%s" aria-hidden="true" style="%s">' . "\u{200B}" . '</i>',
            esc_attr($icon_class),
            esc_attr($style),
        );

        if (!empty($attributes["url"])) {
            $link_attrs = 'href="' . esc_url($attributes["url"]) . '"';
            if (!empty($attributes["linkTarget"])) {
                $link_attrs .= ' target="' . esc_attr($attributes["linkTarget"]) . '"';
            }
            if (!empty($attributes["rel"])) {
                $link_attrs .= ' rel="' . esc_attr($attributes["rel"]) . '"';
            }
            $inner = "<a " . $link_attrs . ">" . $icon_html . "</a>";
        } else {
            $inner = $icon_html;
        }

        return sprintf("<div %s>%s</div>", get_block_wrapper_attributes(), $inner);
    }

    /**
     * Expose the Single icon block's "icon" and "iconColor" attributes for
     * Pattern Overrides (WP 6.9+ block bindings).
     *
     * @param string[] $attributes
     * @return string[]
     */
    public function block_bindings_supported_attributes($attributes)
    {
        return array_merge($attributes, ["icon", "iconColor"]);
    }

    /**
     * Add settings as link from the plugin screen
     */
    public function plugin_action_links($links, $plugin_file)
    {
        if ($plugin_file == "jvm-rich-text-icons/plugin.php" && apply_filters("jvm_richtext_icons_show_settings", true)) {
            $setting_link = ['<a href="' . admin_url("options-general.php?page=jvm-rich-text-icons") . '">' . esc_html__("Settings") . "</a>"];
            return array_merge($links, $setting_link);
        }

        return $links;
    }

    /**
     * Add settings as link from the plugin screen
     */
    public function plugin_row_meta($links, $plugin_file)
    {
        if ($plugin_file == "jvm-rich-text-icons/plugin.php") {
            $donate_link = ['<a href="https://www.paypal.com/donate/?hosted_button_id=VXZJG9GC34JJU" target="_blank">' . esc_html__("Donate to Support", "jvm-rich-text-icons") . "</a>"];
            return array_merge($links, $donate_link);
        }
        return $links;
    }

    /**
     * Filters the settings to pass to the block editor.
     *
     * @param array  $editor_settings The editor settings.
     * @param object $post The post being edited.
     *
     * @return array Returns updated editors settings.
     */
    public function block_editor_settings($editor_settings, $post)
    {
        if (!isset($editor_settings["jvm_richtext_icons"])) {
            $editor_settings["jvm_richtext_icons"] = [
                "formats" => [
                    "name" => "formats",
                    "label" => __("Formats", "block-options"),
                    "items" => [
                        "icons" => [
                            "name" => "icon",
                            "label" => __("Insert icon", "jvm-rich-text-icons"),
                            "value" => true,
                        ],
                    ],
                ],
            ];
        }

        return $editor_settings;
    }

    /**
     * Enqueue Gutenberg block assets for both admin backend.
     */
    public function load_admin_assets($hook_suffix)
    {
        if ("post.php" == $hook_suffix || "post-new.php" == $hook_suffix || "widgets.php" == $hook_suffix || "site-editor.php" == $hook_suffix) {
            // Register block editor script for backend.
            $asset_file = include plugin_dir_path(__DIR__) . "dist/blocks.asset.php";
            wp_enqueue_script("jvm-rich-text-icons-js", plugins_url("/dist/blocks.js", dirname(__FILE__)), $asset_file["dependencies"], $asset_file["version"], true);
            wp_set_script_translations("jvm-rich-text-icons-js", "jvm-rich-text-icons", plugin_dir_path(__DIR__) . "languages");

            // Register block editor styles for backend.
            wp_enqueue_style(
                "jvm-rich-text-icons-editor-css", // Handle.
                plugins_url("dist/editor.css", dirname(__FILE__)), // Block editor CSS.
                ["wp-edit-blocks"], // Dependency to include the CSS after it.
                filemtime(plugin_dir_path(__DIR__) . "dist/editor.css"), // Version: File modification time.
            );

            $icons = JVM_Richtext_icons::get_icons();
            $base_class = JVM_Richtext_icons::get_class_prefix();
            wp_localize_script(
                "jvm-rich-text-icons-js",
                "jvm_richtext_icon_settings", // Array containing dynamic data for a JS Global.
                [
                    "iconset" => $icons,
                    "base_class" => $base_class,
                    "text" => [
                        "delete_icon" => __("Delete Icon", "jvm-rich-text-icons"),
                    ],
                ],
            );
        }
    }

    /**
     * Enqueue icon CSS on the frontend as well as in the block editor
     * (canvas + iframe). Runs on enqueue_block_assets only, so the editor
     * iframe picks the styles up through the supported path.
     */
    public static function load_css()
    {
        $settings = self::get_settings();
        $technology = isset($settings["technology"]) ? $settings["technology"] : "html-css";

        // Base CSS — always loaded (frontend + editor iframe) regardless of
        // pro override or technology.
        wp_register_style("jvm-rich-text-icons-svg", false);
        wp_enqueue_style("jvm-rich-text-icons-svg");
        $base_css = ".wp-block-jvm-single-icon{line-height:1}";
        if ($technology === "inline-svg") {
            $base_css = 'svg.icon{width:1em;height:1em;display:inline-block;vertical-align:-0.125em;fill:currentColor}svg.icon[fill="none"]{fill:none}' . $base_css;
        }
        wp_add_inline_style("jvm-rich-text-icons-svg", $base_css);

        $load_default_css = apply_filters("jvm_richtext_icons_load_default_css", true, $settings);
        if (!$load_default_css) {
            return;
        }

        $icons = [];
        $icon_set = "default";
        if (isset($settings["icon_set"])) {
            $icon_set = $settings["icon_set"];
        }

        if ($icon_set == "custom-svg") {
            if ($technology !== "inline-svg" || is_admin()) {
                static $custom_svg_css_loaded = false;
                if (!$custom_svg_css_loaded) {
                    $custom_svg_css_loaded = true;
                    wp_register_style("jvm-rich-text-icons-svg", false);
                    wp_enqueue_style("jvm-rich-text-icons-svg");
                    // In admin/editor always load mask-based CSS so <i> tags render correctly
                    wp_add_inline_style("jvm-rich-text-icons-svg", JVM_Richtext_icons::parse_dynamic_css());
                }
            }
        } else {
            $folder = $icon_set;
            if ($icon_set == "default") {
                $fontCssFile = plugins_url("dist/fa-4.7/font-awesome.min.css", dirname(__FILE__));
            } elseif ($icon_set == "fa-5") {
                $fontCssFile = plugins_url("dist/fa-5/css/all.min.css", dirname(__FILE__));
            } elseif ($icon_set == "fa-6") {
                $fontCssFile = plugins_url("dist/fa-6/css/all.min.css", dirname(__FILE__));
            }

            // Icon set CSS (font awesome 4.7 is shipped by default).
            $fontCssFile = apply_filters("jvm_richtext_icons_css_file", $fontCssFile);

            if (!empty($fontCssFile)) {
                wp_enqueue_style(
                    "jvm-rich-text-icons-icon-font-css", // Handle.
                    $fontCssFile,
                );
            }
        }
    }

    /**
     * Content DB version. Bump when adding a new one-time migration to
     * self::content_migrations().
     */
    const CONTENT_DB_VERSION = 1;

    /**
     * Run pending one-time content migrations (at most one batch per request;
     * large sites continue on subsequent admin loads). Each migration runs
     * exactly once thanks to the jvm_rti_content_version option.
     */
    public function maybe_run_migrations()
    {
        if (!current_user_can("update_plugins")) {
            return;
        }

        $done = (int) get_option("jvm_rti_content_version", 0);
        if ($done >= self::CONTENT_DB_VERSION) {
            return;
        }

        $migrations = $this->content_migrations();
        for ($v = $done + 1; $v <= self::CONTENT_DB_VERSION; $v++) {
            if (!isset($migrations[$v])) {
                continue;
            }
            $finished = call_user_func($migrations[$v]);
            if (!$finished) {
                // Batch not done yet; continue on the next admin request.
                return;
            }
            update_option("jvm_rti_content_version", $v);
        }
        delete_option("jvm_rti_migration_offset");
    }

    /**
     * Map of content DB version => migration callback.
     *
     * @return array<int, callable>
     */
    private function content_migrations()
    {
        return [
            // v1: whitespace-only icon content ("<i ...> </i>") is dropped by
            // the RichText parser (collapseWhiteSpace) which breaks block
            // validation. Replace it with a zero-width space (U+200B), which
            // survives parse + serialize exactly and renders invisibly.
            1 => [$this, "migrate_icon_content_to_zwsp"],
        ];
    }

    /**
     * Replace whitespace-only content of icon <i> tags with U+200B in
     * post_content (posts, pages, reusable blocks, template parts) and
     * revisions. Direct $wpdb update on purpose: the change is visually
     * identical, so post_modified dates and save_post side effects (SEO,
     * caches, revisions spam) must stay untouched.
     *
     * NOTE: postmeta/options are intentionally skipped: values are often
     * PHP-serialized and naive string replacement would corrupt the length
     * prefixes. Meta-stored icons keep working on the frontend through the
     * (backwards compatible) inline-SVG replacement.
     *
     * @return bool True when fully finished, false when more batches remain.
     */
    public function migrate_icon_content_to_zwsp()
    {
        global $wpdb;

        $batch_size = (int) apply_filters("jvm_richtext_icons_migration_batch_size", 500);
        $offset = (int) get_option("jvm_rti_migration_offset", 0);

        $prefix = self::get_class_prefix();
        $pattern = '#<i\b([^>]*\bclass="[^"]*\b' . preg_quote($prefix, "#") . '\b[^"]*")([^>]*)>[\s\x{200B}]+</i>#u';
        $zwsp = "\u{200B}";

        // IDs of posts/revisions whose content mentions an <i> tag.
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status NOT IN ('inherit', 'auto-draft', 'trash') ORDER BY ID ASC LIMIT %d OFFSET %d",
                '%<i%',
                $batch_size,
                $offset,
            )
        );
        $rev_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_content LIKE %s ORDER BY ID ASC LIMIT %d OFFSET %d",
                '%<i%',
                $batch_size,
                $offset,
            )
        );

        if (empty($ids) && empty($rev_ids)) {
            return true;
        }

        foreach (array_merge($ids, $rev_ids) as $id) {
            $content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $id));
            if (!is_string($content) || strpos($content, "<i") === false) {
                continue;
            }
            $new = preg_replace($pattern, '<i$1$2>' . $zwsp . '</i>', $content);
            if (is_string($new) && $new !== $content) {
                $wpdb->update($wpdb->posts, ["post_content" => $new], ["ID" => $id], ["%s"], ["%d"]);
                clean_post_cache($id);
            }
        }

        update_option("jvm_rti_migration_offset", $offset + $batch_size, false);
        return count($ids) < $batch_size && count($rev_ids) < $batch_size;
    }

    /**
     * WP-CLI: run pending content migrations manually.
     * Usage: wp jvm-icons migrate
     */
    public function cli_migrate()
    {
        $done = (int) get_option("jvm_rti_content_version", 0);
        if ($done >= self::CONTENT_DB_VERSION) {
            \WP_CLI::success("Nothing to do (content version {$done}).");
            return;
        }
        delete_option("jvm_rti_migration_offset");
        $loops = 0;
        do {
            $finished = $this->migrate_icon_content_to_zwsp();
            if (++$loops > 10000) {
                \WP_CLI::error("Migration did not finish after 10000 batches, aborting.");
                return;
            }
        } while (!$finished);
        update_option("jvm_rti_content_version", self::CONTENT_DB_VERSION);
        delete_option("jvm_rti_migration_offset");
        \WP_CLI::success("Content migration finished (version " . self::CONTENT_DB_VERSION . ").");
    }

    /**
     * Start output buffering for inline SVG replacement on the frontend.
     */
    public function maybe_start_inline_svg_buffer()
    {
        $settings = self::get_settings();
        $use_inline_svg = $settings["icon_set"] == "custom-svg" && $settings["technology"] == "inline-svg";
        $use_inline_svg = apply_filters("jvm_richtext_icons_use_inline_svg", $use_inline_svg, $settings);

        if ($use_inline_svg) {
            ob_start([$this, "replace_icons_with_inline_svg"]);
        }
    }

    /**
     * Replace <i class="icon icon-name"> tags with inline SVG elements.
     * @param string $html
     * @return string
     */
    public function replace_icons_with_inline_svg($html)
    {
        $prefix = self::get_class_prefix();
        $svg_dirs = apply_filters("jvm_richtext_icons_svg_directories", [
            "default" => self::get_svg_directory(),
        ]);
        static $svg_cache = [];

        $html = preg_replace_callback(
            // NOTE: icon content may be a plain space (legacy) or a zero-width
            // space U+200B (current). \s alone does not match U+200B, hence the
            // explicit \x{200B} alternative with the /u modifier.
            '/<i\b([^>]*)\bclass="' . preg_quote($prefix, "/") . ' ([^"]*)"([^>]*)>[\s\x{200B}]*<\/i>/u',
            function ($matches) use ($prefix, $svg_dirs, &$svg_cache) {
                $classes = $matches[2];
                $extra_attrs = trim($matches[1] . " " . $matches[3]);
                // Remove aria-hidden from extra attrs since we add it ourselves
                $extra_attrs = preg_replace('/\s*aria-hidden="[^"]*"/', "", $extra_attrs);
                $extra_attrs = trim($extra_attrs);

                // The last class is the icon name (e.g. "fas fa-location-dot" -> "fa-location-dot")
                $class_parts = explode(" ", $classes);
                $icon_name = end($class_parts);

                if (!isset($svg_cache[$icon_name])) {
                    $svg_cache[$icon_name] = false;
                    foreach ($svg_dirs as $dir) {
                        $file = $dir . $icon_name . ".svg";
                        if (file_exists($file)) {
                            $svg_cache[$icon_name] = file_get_contents($file);
                            break;
                        }
                    }
                }

                if ($svg_cache[$icon_name] === false) {
                    return $matches[0];
                }

                $svg = $svg_cache[$icon_name];
                // Add class, aria-hidden and any extra attributes (e.g. style) to the SVG element
                $attrs = 'class="' . esc_attr($prefix . " " . $classes) . '" aria-hidden="true"';
                if (!empty($extra_attrs)) {
                    $attrs .= " " . $extra_attrs;
                }
                $svg = preg_replace("/<svg\b/", "<svg " . $attrs, $svg, 1);

                return $svg;
            },
            $html,
        );

        return $html;
    }

    /**
     * Show a review notice after 14 days of use.
     */
    public function maybe_show_review_notice()
    {
        if (get_option("jvm_richtext_icons_review_dismissed")) {
            return;
        }

        $activated = get_option("jvm_richtext_icons_activated");
        if (!$activated) {
            // Existing installs that never triggered the activation hook
            update_option("jvm_richtext_icons_activated", time());
            return;
        }
        if (time() - $activated < 14 * DAY_IN_SECONDS) {
            return;
        }

        echo '<div class="notice notice-info is-dismissible jvm-richtext-review-notice">';
        echo "<p>";
        echo sprintf(
            __('Enjoying %1$s? Please consider %2$sleaving a review%3$s — it helps other WordPress users find this plugin.', "jvm-rich-text-icons"),
            "<strong>JVM Rich Text Icons</strong>",
            '<a href="https://wordpress.org/support/plugin/jvm-rich-text-icons/reviews/#new-post" target="_blank">',
            "</a>",
        );
        echo "</p>";
        echo "</div>";
        echo '<script>jQuery(document).on("click",".jvm-richtext-review-notice .notice-dismiss",function(){jQuery.post(ajaxurl,{action:"jvm_richtext_dismiss_review"})});</script>';
    }

    /**
     * AJAX handler to permanently dismiss the review notice.
     */
    public function dismiss_review_notice()
    {
        update_option("jvm_richtext_icons_review_dismissed", true);
        wp_send_json_success();
    }

    /**
     * Get the class prefix for the css
     * @return [string]
     */
    public static function get_class_prefix()
    {
        return apply_filters("jvm_richtext_icons_base_class", "icon");
    }

    /**
     * Get the icon config
     * @return [array]
     */
    public static function get_icons()
    {
        $settings = self::get_settings();
        $icons = [];
        $icon_set = "default";
        if (isset($settings["icon_set"])) {
            $icon_set = $settings["icon_set"];
        }

        if ($icon_set == "custom-svg") {
            $svg_files = self::get_svg_file_list();
            foreach ($svg_files as $file) {
                $pi = pathinfo($file);
                if ($pi["extension"] == "svg") {
                    $icon_class = sanitize_title($pi["filename"]);
                    $icons[] = $icon_class;
                }
            }
        } else {
            $folder = $icon_set;
            if ($icon_set == "default") {
                $folder = "fa-4.7";
            }

            // WP Localized globals. Use dynamic PHP stuff in JavaScript via `cgbGlobal` object.
            $iconFile = plugin_dir_path(__DIR__) . "dist/" . $folder . "/icons.json";
            $iconFile = apply_filters("jvm_richtext_icons_iconset_file", $iconFile);

            if (file_exists($iconFile)) {
                $iconData = file_get_contents($iconFile);
                $data = json_decode($iconData);
                if ($data === null) {
                    return $icons;
                }
                $icons = [];
                // Check if data is fontello format
                if (isset($data->glyphs)) {
                    foreach ($data->glyphs as $g) {
                        $icons[] = $data->css_prefix_text . $g->css;
                    }
                    // Pro/extended format: object with icons array of {name, tags, categories, styles}
                } elseif (is_object($data) && isset($data->icons) && is_array($data->icons)) {
                    foreach ($data->icons as $icon) {
                        if (is_object($icon) && isset($icon->name)) {
                            $icons[] = $icon->name;
                        } elseif (is_string($icon)) {
                            $icons[] = $icon;
                        }
                    }
                    // Legacy format: flat array of class name strings
                } else {
                    $icons = $data;
                }

                $icons = apply_filters("jvm_richtext_icons_iconset", $icons);
            }
        }

        return $icons;
    }

    /**
     * Get the plugin settings
     * @return [array] [options]
     */
    public static function get_settings()
    {
        $settings = get_option("jvm-rich-text-icons");

        // Array if no options
        if (false == $settings) {
            $settings = [];
        }

        if (!isset($settings["icon_set"])) {
            $settings["icon_set"] = "fa-6";
        }

        if (!isset($settings["technology"])) {
            $settings["technology"] = "inline-svg";
        }

        $sanitizer_defaults = [
            'sanitizer_normalize_colors'    => true,
            'sanitizer_normalize_viewbox'   => true,
            'sanitizer_square_viewbox'      => false,
            'sanitizer_remove_ids'          => true,
            'sanitizer_convert_white_masks' => true,
        ];
        foreach ($sanitizer_defaults as $key => $default) {
            if (!isset($settings[$key])) {
                $settings[$key] = $default;
            }
        }

        return $settings;
    }

    /**
     * Render a view file
     * @param  [string] $fileName
     * @param  array  $dataForView
     * @return [string] rendered view
     */
    public static function render_view($fileName, $dataForView = [])
    {
        if (!file_exists(plugin_dir_path(__DIR__) . "views/" . $fileName)) {
            return plugin_dir_path(__DIR__) . "views/" . $fileName . " not found.";
        } else {
            // Extract vars to local namespace
            extract($dataForView, EXTR_SKIP);
            ob_start();

            include plugin_dir_path(__DIR__) . "views/" . $fileName;

            $out = ob_get_clean();

            return $out;
        }
    }

    /**
     * Get the css for custom svg settings
     * @return [string] [css styling]
     */
    public static function parse_dynamic_css()
    {
        static $result = null;
        if ($result !== null) {
            return $result;
        }

        $settings = self::get_settings();
        $technology = isset($settings["technology"]) ? $settings["technology"] : "html-css";
        $files = self::get_svg_file_list();

        $base = self::get_svg_directory();
        $cache_meta = ["technology" => $technology, "count" => count($files), "mtime" => (int) @filemtime($base)];
        $cached = get_transient("jvm_rti_css_v1");
        if ($cached !== false && isset($cached["meta"]) && $cached["meta"] === $cache_meta) {
            $result = $cached["css"];
            return $result;
        }

        $prefix_class = self::get_class_prefix();
        $icons = [];
        foreach ($files as $file) {
            $pi = pathinfo($file);
            $icon_class = sanitize_title($pi["filename"]);
            $file_content = file_get_contents($file);
            if ($file_content === false) {
                continue;
            }

            $ratio = 1;
            $dom = new DOMDocument();
            @$dom->load($file);
            $svg = $dom->getElementsByTagName("svg");
            if ($svg && $svg->length > 0) {
                $viewBox = $svg[0]->getAttribute("viewBox");
                if ($viewBox) {
                    [$x, $y, $width, $height] = explode(" ", $viewBox);
                } else {
                    $width = str_replace("px", "", $svg[0]->getAttribute("width"));
                    $height = str_replace("px", "", $svg[0]->getAttribute("height"));
                }
                if (!empty($width) && !empty($height)) {
                    $ratio = $width / $height;
                }
            }

            $icons[] = ["class" => $icon_class, "svg" => $file_content, "ratio" => $ratio];
        }

        $result = JVM_RTI_Renderer::generate_css($icons, $technology);
        set_transient("jvm_rti_css_v1", ["meta" => $cache_meta, "css" => $result], DAY_IN_SECONDS);
        return $result;
    }

    /**
     * Get a list of uploaded custom SVG icons
     * @return [array] [icons]
     */
    public static function get_svg_file_list()
    {
        $base = self::get_svg_directory();
        $files = scandir($base);
        $files_out = [];
        foreach ($files as $file) {
            $pi = pathinfo($base . $file);

            if ($pi["extension"] == "svg") {
                $files_out[] = $base . $file;
            }
        }

        return $files_out;
    }

    /**
     * Get the icon upload base directory
     * @return [string]
     */
    public static function get_svg_directory()
    {
        $upload = wp_upload_dir();
        $base = $upload["basedir"] . "/jvm-rich-text-icons/";
        if (!is_dir($base)) {
            mkdir($base);
        }
        return $base;
    }
}

JVM_Richtext_icons::register();
