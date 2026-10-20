<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class JVM_Richtext_icons_settings {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {
        add_action('after_setup_theme', array($this, 'try_add_settings'));
        add_filter('jvm_richtext_icons_process_uploaded_svg', [$this, 'sanitize_uploaded_svg'], 10, 3);
    }

    /**
     * Only load the settings screen if it was not disabled by a hook.
     */
    public function try_add_settings() {
        $user = wp_get_current_user();
        // Only admin users have access to the settings.
        if (in_array( 'administrator', (array) $user->roles ) ) {

            $show_settings = apply_filters('jvm_richtext_icons_show_settings', true);
            if ($show_settings) {
                add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
                add_action( 'admin_init', array( $this, 'page_init' ) );
                add_action( 'admin_init', array( $this, 'register_technology_field' ), 30 );
                add_action( 'admin_init', array( $this, 'register_sanitizer_fields' ), 40 );
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));

                // Ajax calls
                if (current_user_can( 'upload_files' )) {
                    add_action('wp_ajax_jvm-rich-text-icons-delete-icon', array( $this, 'ajax_delete_icon'));
                    add_action('wp_ajax_jvm-rich-text-icons-upload-icon', array( $this, 'ajax_upload_icon'));
                    add_action('wp_ajax_jvm-rich-text-icons-bulk-sanitize', array( $this, 'ajax_bulk_sanitize'));
                }

                // Notice on settings screen if a custom icon set is loaded
                add_action('admin_notices', array($this, 'admin_notice'));
            }
        }
    }

    public function admin_notice() {
        $current_screen = get_current_screen();
        // Only if we are in the settings page
        if ( $current_screen->base == 'settings_page_jvm-rich-text-icons' ) {
            if (isset($this->options['icon_set'])) {
                if ($this->options['icon_set'] != 'default') {
                    $iconFileDefault = plugin_dir_path( __DIR__ ).'src/icons.json';
                    $iconFileLoaded = apply_filters('jvm_richtext_icons_iconset_file', $iconFileDefault);

                    $show_custom_notice = apply_filters('jvm_richtext_icons_show_custom_iconset_notice', $iconFileDefault != $iconFileLoaded, $iconFileLoaded);
                    if ($show_custom_notice) {
                        echo '<div class="notice notice-warning">';
                        echo '<p>'.sprintf(__("A custom icon set is being loaded from: %s. Keep your setting set to the default Font Awsome icon set to keep this working. The custom icon set can't be loaded if you are creating a SVG icon set from this page.", 'jvm-rich-text-icons'), $iconFileLoaded).'</p>';
                        echo '</div>';
                    }
                }
            }
        }
    }

    public function enqueue_scripts() {
        $current_screen = get_current_screen();
        // Only if we are in the settings page
        if ( $current_screen->base == 'settings_page_jvm-rich-text-icons' ) {
            wp_enqueue_script( 'jvm-rich-text-icons-dropzone', plugins_url( '/dist/dropzone.min.js', dirname( __FILE__ ) ) );
            wp_enqueue_script( 'jquery-ui-dialog' ); // jquery and jquery-ui should be dependencies, didn't check though...
            wp_enqueue_style( 'wp-jquery-ui-dialog' );
            wp_enqueue_style(
                'jvm-rich-text-icons-admin-settings', // Handle.
                plugins_url( 'dist/css/admin-settings.css', dirname( __FILE__ ) ),
                array(),
                filemtime( plugin_dir_path( __DIR__ ) . 'dist/css/admin-settings.css' ) // Version: File modification time.
            );

            // Register the settings script for the backend.
            wp_enqueue_script(
                'jvm-rich-text-icons-settings-js', // Handle.
                plugins_url( '/dist/settings.js', dirname( __FILE__ ) ),
                array( 'jquery-ui-dialog'), // Dependencies, defined above.
                '1.2.5', // filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: filemtime — Gets file modification time.
                true // Enqueue the script in the footer.
            );

            wp_localize_script(
                'jvm-rich-text-icons-settings-js',
                'jvm_richtext_icon_settings', // Array containing dynamic data for a JS Global.
                [
                    'ajax_url'            => admin_url( 'admin-ajax.php' ),
                    'max_upload_size'     => wp_max_upload_size(),
                    'bulk_sanitize_nonce' => wp_create_nonce( 'jvm-rich-text-icons-bulk-sanitize' ),
                    'text' => [
                        'delete_icon'          => __('Delete Icon', 'jvm-rich-text-icons'),
                        'delete_icon_confirm'  => __("You are about to permanently delete this icon from your site. This action cannot be undone.\n'Cancel' to stop 'OK' to delete.", 'jvm-rich-text-icons'),
                        'bulk_sanitize_running'=> __('Sanitizing...', 'jvm-rich-text-icons'),
                        'bulk_sanitize_done'   => __('Done! %d icons processed.', 'jvm-rich-text-icons'),
                        'bulk_sanitize_errors' => __('Some icons could not be processed:', 'jvm-rich-text-icons'),
                    ]
                ]
            );
        }
    }

    /**
     * Remove an icon
     */
    public function ajax_delete_icon() {
        if (!current_user_can('upload_files')) {
            wp_send_json(["success" => false]);
            exit;
        }

        if (isset($_POST['file']) && wp_verify_nonce($_POST['nonce'], 'jvm-rich-text-icons-delete-icon' )) {
            $file = sanitize_file_name($_POST['file']);
            $base = JVM_Richtext_icons::get_svg_directory();

            if (file_exists($base.$file)) {
                if (unlink($base.$file)) {
                    wp_send_json(["success" => true]);
                    exit;
                }
            }
        }

        wp_send_json(["success" => false]);
        exit;
    }

    /**
     * Upload an icon
     */
    public function ajax_upload_icon() {
        if (!current_user_can('upload_files')) {
            wp_send_json(["success" => false]);
            exit;
        }

        if (isset($_FILES['file']) && wp_verify_nonce($_GET['nonce'], 'jvm-rich-text-icons-upload-icon' )) {
            // Check if file is SVG as we only accept SVG files
            $pi = pathinfo($_FILES['file']['name']);
            if ($_FILES['file']['type'] == 'image/svg+xml' && strtolower($pi['extension']) == 'svg') {

                $base = JVM_Richtext_icons::get_svg_directory();
                $new_file_name = $this->generate_unique_svg_file_name($_FILES['file']['name']);
                if (move_uploaded_file($_FILES['file']['tmp_name'], $base.$new_file_name)) {
                    $pi = pathinfo($new_file_name);
                    $css_class = JVM_Richtext_icons::get_class_prefix();
                    $icon_class = sanitize_title($pi['filename']);
                    $svg_content = file_get_contents($base.$new_file_name);

                    /**
                     * Allow plugins (e.g. the pro plugin) to sanitize the uploaded SVG.
                     *
                     * @param null|string|\WP_Error $result      Return null to skip, a clean SVG string to replace
                     *                                           the file contents, or a WP_Error to reject the upload.
                     * @param string                $svg_content Raw SVG content as read from disk.
                     * @param string                $file_path   Absolute path to the uploaded file.
                     */
                    $sanitize_result = apply_filters( 'jvm_richtext_icons_process_uploaded_svg', null, $svg_content, $base . $new_file_name );

                    if ( is_wp_error( $sanitize_result ) ) {
                        @unlink( $base . $new_file_name );
                        wp_send_json( [ "success" => false, "message" => $sanitize_result->get_error_message() ] );
                        exit();
                    }

                    if ( is_string( $sanitize_result ) && $sanitize_result !== '' ) {
                        file_put_contents( $base . $new_file_name, $sanitize_result );
                        $svg_content = $sanitize_result;
                    }

                    wp_send_json([
                        "success" => true,
                        "icon_class_full" => $css_class.' '.$icon_class,
                        "icon_class" => $icon_class,
                        "file" => $new_file_name,
                        "nonce" => wp_create_nonce('jvm-rich-text-icons-delete-icon'),
                        "svg" => $svg_content,
                        'css_code' => JVM_Richtext_icons::parse_dynamic_css()
                    ]);
                    exit();
                }
            }
        }

        wp_send_json(["success" => false]);
        exit();
    }

    /**
     * Sanitize an uploaded SVG using the base security sanitizer.
     *
     * Hooked on 'jvm_richtext_icons_process_uploaded_svg' at priority 10.
     * Skips if a previous handler (e.g. the pro plugin at priority 5) already
     * returned a result.
     *
     * @param  null|string|\WP_Error $result      Previous filter value.
     * @param  string                $svg_content Raw SVG content.
     * @param  string                $file_path   Absolute path to the uploaded file.
     * @return string|\WP_Error  Clean SVG string, or WP_Error on failure.
     */
    public function sanitize_uploaded_svg($result, string $svg_content, string $file_path)
    {
        if ($result !== null) {
            return $result; // Already handled (e.g. by pro plugin at priority 5).
        }

        $settings  = JVM_Richtext_icons::get_settings();
        $sanitizer = new JVM_RTI_SVG_Sanitizer([
            'normalize_colors'    => (bool) $settings['sanitizer_normalize_colors'],
            'normalize_viewbox'   => (bool) $settings['sanitizer_normalize_viewbox'],
            'square_viewbox'      => (bool) $settings['sanitizer_square_viewbox'],
            'remove_ids'          => (bool) $settings['sanitizer_remove_ids'],
            'convert_white_masks' => (bool) $settings['sanitizer_convert_white_masks'],
        ]);
        $r = $sanitizer->process($svg_content);

        return $r->has_error() ? $r->get_error() : $r->svg;
    }

    /**
     * Bulk sanitize uploaded SVG icons using current sanitizer settings.
     * Processes files in batches of 20 to avoid PHP timeouts.
     */
    public function ajax_bulk_sanitize()
    {
        if (!current_user_can('upload_files')) {
            wp_send_json(['success' => false, 'message' => __('Permission denied.', 'jvm-rich-text-icons')]);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jvm-rich-text-icons-bulk-sanitize')) {
            wp_send_json(['success' => false, 'message' => __('Invalid nonce.', 'jvm-rich-text-icons')]);
        }

        $svg_dir = JVM_Richtext_icons::get_svg_directory();
        if (!is_writable($svg_dir)) {
            wp_send_json([
                'success' => false,
                'message' => sprintf(
                    __('The SVG uploads directory is not writable: %s', 'jvm-rich-text-icons'),
                    $svg_dir
                ),
            ]);
        }

        $offset     = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        $batch_size = 20;
        $files      = JVM_Richtext_icons::get_svg_file_list();
        $total      = count($files);
        $batch      = array_slice($files, $offset, $batch_size);

        $settings  = JVM_Richtext_icons::get_settings();
        $sanitizer = new JVM_RTI_SVG_Sanitizer([
            'normalize_colors'    => (bool) $settings['sanitizer_normalize_colors'],
            'normalize_viewbox'   => (bool) $settings['sanitizer_normalize_viewbox'],
            'square_viewbox'      => (bool) $settings['sanitizer_square_viewbox'],
            'remove_ids'          => (bool) $settings['sanitizer_remove_ids'],
            'convert_white_masks' => (bool) $settings['sanitizer_convert_white_masks'],
        ]);

        $processed = 0;
        $errors    = [];

        foreach ($batch as $file_path) {
            $raw = @file_get_contents($file_path);
            if ($raw === false) {
                $errors[] = basename($file_path) . ': ' . __('could not read file', 'jvm-rich-text-icons');
                $processed++;
                continue;
            }
            $r = $sanitizer->process($raw);
            if ($r->has_error()) {
                $errors[] = basename($file_path) . ': ' . $r->get_error()->get_error_message();
            } else {
                file_put_contents($file_path, $r->svg);
            }
            $processed++;
        }

        $done = ($offset + $processed) >= $total;

        if ($done) {
            delete_transient('jvm_rti_css_v1');
        }

        wp_send_json([
            'success'   => true,
            'processed' => $offset + $processed,
            'total'     => $total,
            'done'      => $done,
            'errors'    => $errors,
        ]);
    }

    /**
     * Generate a unuique file name for a SVG upload to prevent duplicates
     */
    private function generate_unique_svg_file_name($name, $addon='') {
        $base = JVM_Richtext_icons::get_svg_directory();
        $pi = pathinfo($name);
        $namecheck= $pi['filename'].$addon.'.'.$pi['extension'];
        if (file_exists($base.$namecheck)) {
            if(empty($addon)) {
                $addon = 1;
            }else {
                $addon = str_replace('-', '', $addon);
                $addon = (int) $addon;
                $addon ++;
            }

            return $this->generate_unique_svg_file_name($name, '-'.$addon);
        }

        return $namecheck;
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin',
            __('JVM rich text icons', 'jvm-rich-text-icons'),
            'manage_options',
            'jvm-rich-text-icons',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Taken from media-new.php
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
        }

        echo JVM_Richtext_icons::render_view('settings.php');
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        // Set class property
        $this->options = JVM_Richtext_icons::get_settings();

        add_settings_section(
            'general', // ID
            '', // Title
            '',
            'jvm-rich-text-icons' // Page
        );

        add_settings_field(
            'icon_set', // ID
            __('Icon set', 'jvm-rich-text-icons'), // Title
            function () {
                $icon_sets = [
                    'default'    => __('Font Awesome 4.7', 'jvm-rich-text-icons'),
                    'fa-5'       => __('Font Awesome Free 5.15.4', 'jvm-rich-text-icons'),
                    'fa-6'       => __('Font Awesome Free 6.7.2', 'jvm-rich-text-icons'),
                    'custom-svg' => __('My SVG uploads', 'jvm-rich-text-icons'),
                ];
                $icon_sets = apply_filters('jvm_richtext_icons_available_icon_sets', $icon_sets);

                echo '<select id="jvm-rich-text-icons_icon_set" name="jvm-rich-text-icons[icon_set]">';
                foreach ($icon_sets as $value => $label) {
                    $selected = selected($this->options['icon_set'], $value, false);
                    echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
            },
            'jvm-rich-text-icons', // Page
            'general' // Section
        );

        register_setting(
            'jvm-rich-text-icons', // Option group
            'jvm-rich-text-icons', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );
    }

    /**
     * Register the technology field at priority 30 so it appears after
     * any pro plugin fields (style, stroke width) registered at priority 20.
     */
    public function register_technology_field() {
        $this->options = JVM_Richtext_icons::get_settings();

        add_settings_field(
            'technology', // ID
            __('Render technology', 'jvm-rich-text-icons'), // Title
            function () {
                echo '<select name="jvm-rich-text-icons[technology]">';

                $checked = $this->options['technology'] == 'inline-svg' ? ' selected' : '';
                echo '<option value="inline-svg"'.$checked.'>'.__('Inline SVG', 'jvm-rich-text-icons').'</option>';

                $checked = $this->options['technology'] == 'html-css' ? ' selected' : '';
                echo '<option value="html-css"'.$checked.'>'.__('HTML + CSS', 'jvm-rich-text-icons').'</option>';
                $checked = $this->options['technology'] == 'html-css-before' ? ' selected' : '';
                echo '<option value="html-css-before"'.$checked.'>'.__('HTML + CSS ::before pseudo-element', 'jvm-rich-text-icons').'</option>';

                $checked = $this->options['technology'] == 'html-css-after' ? ' selected' : '';
                echo '<option value="html-css-after"'.$checked.'>'.__('HTML + CSS ::after pseudo-element', 'jvm-rich-text-icons').'</option>';

                echo '</select>';
            },
            'jvm-rich-text-icons', // Page
            'general', // Section
            [ 'class' => 'jvm-rti-technology-row' ]
        );
    }

    public function validation_notice($text) {
        return '<div id="setting-error-empty" class="error settings-error notice"><p><strong>'.esc_html($text).'</strong></p></div>';
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        // Start from the current saved values so each tab only updates its own fields,
        // leaving the other tab's settings untouched.
        $current   = get_option( 'jvm-rich-text-icons', [] );
        $sanitized = is_array( $current ) ? $current : [];

        $tab = isset( $input['_tab'] ) ? sanitize_key( $input['_tab'] ) : 'icon-set';

        if ( $tab === 'icon-set' ) {
            $valid_icon_sets = ['default', 'fa-5', 'fa-6', 'fa-7', 'custom-svg'];
            $valid_icon_sets = apply_filters('jvm_richtext_icons_valid_icon_sets', $valid_icon_sets);
            if (isset($input['icon_set']) && in_array($input['icon_set'], $valid_icon_sets, true)) {
                $sanitized['icon_set'] = $input['icon_set'];
            }

            $valid_technologies = ['html-css', 'html-css-before', 'html-css-after', 'inline-svg'];
            if (isset($input['technology']) && in_array($input['technology'], $valid_technologies, true)) {
                $sanitized['technology'] = $input['technology'];
            }
        }

        if ( $tab === 'tools' ) {
            foreach (['sanitizer_normalize_colors', 'sanitizer_normalize_viewbox', 'sanitizer_square_viewbox',
                      'sanitizer_remove_ids', 'sanitizer_convert_white_masks'] as $key) {
                $sanitized[$key] = !empty($input[$key]);
            }
        }

        return $sanitized;
    }

    /**
     * Register sanitizer option fields on the Tools & Settings tab.
     * Uses page slug 'jvm-rich-text-icons-tools' so these fields only appear
     * when do_settings_sections('jvm-rich-text-icons-tools') is called.
     */
    public function register_sanitizer_fields()
    {
        $this->options = JVM_Richtext_icons::get_settings();

        add_settings_section('sanitizer', '', '', 'jvm-rich-text-icons-tools');

        $fields = [
            'sanitizer_normalize_colors' => [
                'label' => __('Normalize colors', 'jvm-rich-text-icons'),
                'desc'  => __('Replace hardcoded fill and stroke colors with currentColor so icons automatically inherit the surrounding text color. White is excluded.', 'jvm-rich-text-icons'),
            ],
            'sanitizer_normalize_viewbox' => [
                'label' => __('Normalize viewBox', 'jvm-rich-text-icons'),
                'desc'  => __('Derive a viewBox from width/height attributes when missing, then remove explicit width/height so the icon scales freely with CSS.', 'jvm-rich-text-icons'),
            ],
            'sanitizer_square_viewbox' => [
                'label' => __('Square viewBox', 'jvm-rich-text-icons'),
                'desc'  => __('Pad non-square viewBoxes to a square canvas by centering the content. Useful when icons need uniform dimensions in a grid.', 'jvm-rich-text-icons'),
            ],
            'sanitizer_remove_ids' => [
                'label' => __('Remove IDs', 'jvm-rich-text-icons'),
                'desc'  => __('Strip id attributes to prevent DOM collisions when the same icon appears multiple times on a page. IDs on mask elements are always preserved.', 'jvm-rich-text-icons'),
            ],
            'sanitizer_convert_white_masks' => [
                'label' => __('Convert white masks', 'jvm-rich-text-icons'),
                'desc'  => __('Auto-detect icons that use white shapes as cutouts and convert them to proper SVG mask elements. This ensures they render correctly with currentColor.', 'jvm-rich-text-icons'),
            ],
        ];

        foreach ($fields as $key => $config) {
            $opts = $this->options;
            add_settings_field(
                $key,
                $config['label'],
                function () use ($key, $config, $opts) {
                    $checked = !empty($opts[$key]);
                    echo '<label>';
                    echo '<input type="checkbox" name="jvm-rich-text-icons[' . esc_attr($key) . ']" value="1"' . checked($checked, true, false) . '>';
                    echo '</label>';
                    echo '<p class="description">' . esc_html($config['desc']) . '</p>';
                },
                'jvm-rich-text-icons-tools',
                'sanitizer'
            );
        }
    }
}

$JVM_Richtext_icons_settings = new JVM_Richtext_icons_settings();
