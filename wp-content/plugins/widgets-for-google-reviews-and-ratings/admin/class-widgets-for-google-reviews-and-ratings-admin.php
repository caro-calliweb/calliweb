<?php

class Widgets_For_Google_Reviews_And_Ratings_Admin {

    private $plugin_name;
    private $version;
    public $place_details;
    public $admin_html;
    public $is_min;
    public $star_color;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = esc_html($plugin_name);
        $this->version = esc_html($version);
        $this->place_details = get_option('wgrr_g_place_details', '');
        $this->is_min = !(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
        $this->star_color = get_option('repocean_star_color', '#F6BB06');
        add_action('admin_footer', array($this, 'repocean_add_deactivation_feedback_form'));
        add_action('admin_enqueue_scripts', array($this, 'repocean_add_deactivation_feedback_form_scripts'));
        add_action('wp_ajax_repocean_send_deactivation', array($this, 'repocean_handle_plugin_deactivation_request'));
    }

    public function enqueue_styles() {
        if (isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'google-reviews-settings') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/widgets-for-google-reviews-and-ratings-admin.css', [], $this->version, 'all');
        }
    }

    public function enqueue_scripts() {
        if (isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'google-reviews-settings') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/widgets-for-google-reviews-and-ratings-admin.js', ['jquery'], $this->version, false);
            wp_localize_script($this->plugin_name, 'wgrr_ajax', ['nonce' => wp_create_nonce('wgrr_nonce')]);
        }
    }

    public function wgrr_reviews_menu() {
        add_menu_page(
                esc_html__('Google Reviews Settings', 'widgets-for-google-reviews-and-ratings'),
                esc_html__('Google Reviews', 'widgets-for-google-reviews-and-ratings'),
                'manage_options',
                'google-reviews-settings',
                [$this, 'wgrr_reviews_settings_page'],
                esc_url(WGRR_ASSET_URL . 'admin/css/images/star.png'),
                15
        );
    }

    public function wgrr_reviews_settings_page() {
        $allowed_html = [
            'span' => ['style' => [], 'class' => []],
            'svg' => ['class' => [], 'viewBox' => [], 'xmlns' => []],
            'polygon' => ['points' => [], 'fill' => []],
            'defs' => [],
            'linearGradient' => ['id' => []],
            'stop' => ['offset' => [], 'stop-color' => []],
        ];

        $opt_key = 'wgrr_activation_ts';
        $activation_ts = get_option($opt_key, null);

        $cutoff_ts = (int) strtotime('2026-02-18 00:00:00');

        if (null === $activation_ts) {
            $activation_ts = $cutoff_ts - DAY_IN_SECONDS;
            add_option($opt_key, (int) $activation_ts, '', false);
        } else {
            $activation_ts = (int) $activation_ts;
            if ($activation_ts <= 0) {
                $activation_ts = $cutoff_ts - DAY_IN_SECONDS;
                update_option($opt_key, (int) $activation_ts, false);
            }
        }
        $show_get_pro = ($activation_ts >= $cutoff_ts);

        $sub_items = [
            'connect_google' => wp_kses(sprintf('<span>1</span> %s', __('Connect Google', 'widgets-for-google-reviews-and-ratings')), $allowed_html),
            'select_layout' => wp_kses(sprintf('<span>2</span> %s', __('Select Layout', 'widgets-for-google-reviews-and-ratings')), $allowed_html),
            'get_settings' => wp_kses(sprintf('<span class="dashicons dashicons-admin-generic"></span> %s', __('Settings', 'widgets-for-google-reviews-and-ratings')), $allowed_html),
        ];

        if ($show_get_pro) {
            $sub_items['get_pro'] = wp_kses(
                    sprintf('<span class="dashicons dashicons-star-filled"></span> %s', __('Get Pro', 'widgets-for-google-reviews-and-ratings')),
                    $allowed_html
            );
        }

        $setting_tabs = apply_filters('wgrr_setting_tab', [
            'widget_customizer' => [
                'label' => esc_html__('Widget Customizer', 'widgets-for-google-reviews-and-ratings'),
                'sub_items' => $sub_items,
            ],
            'get_reviews' => ['label' => esc_html__('Get Reviews', 'widgets-for-google-reviews-and-ratings')],
        ]);

        $default_subtab = empty($this->place_details) ? 'connect_google' : 'select_layout';

        $current_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'widget_customizer'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $current_subtab = isset($_GET['subtab']) ? sanitize_text_field(wp_unslash($_GET['subtab'])) : $default_subtab; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!$show_get_pro && 'get_pro' === $current_subtab) {
            $current_subtab = $default_subtab;
        }
        ?>
        <div id="wgrr-plugin-settings-page">
            <h2 class="nav-tab-wrapper" style="display: none;">
                <?php foreach ($setting_tabs as $name => $tab) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=google-reviews-settings&tab=' . $name)); ?>"
                       class="nav-tab <?php echo ($current_tab === $name) ? 'nav-tab-active' : ''; ?>">
                           <?php echo esc_html($tab['label']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <?php
            if (!empty($setting_tabs[$current_tab])) {
                $tab_data = $setting_tabs[$current_tab];

                do_action('wgrr_' . esc_attr($current_tab) . '_setting_save_field');
                do_action('wgrr_' . esc_attr($current_tab) . '_setting', $setting_tabs);

                if (!empty($tab_data['sub_items']) && isset($tab_data['sub_items'][$current_subtab])) {
                    do_action('wgrr_' . esc_attr($current_tab) . '_' . esc_attr($current_subtab) . '_setting_save_field');
                    do_action('wgrr_' . esc_attr($current_tab) . '_' . esc_attr($current_subtab) . '_setting');
                }
            }
            ?>
        </div>
        <?php
    }

    public function wgrr_widget_customizer_setting($setting_tabs) {
        try {
            $sub_tab = empty($this->place_details) ? 'connect_google' : 'select_layout';
            $current_subtab = isset($_GET['subtab']) ? sanitize_text_field(wp_unslash($_GET['subtab'])) : $sub_tab; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            ?>
            <h2 class="nav-tab-wrapper">
            <?php
            foreach ($setting_tabs['widget_customizer']['sub_items'] as $name => $tab) {
                $class = 'done';
                if ($name === 'connect_google' && empty($this->place_details)) {
                    $class = '';
                } elseif (($name === 'select_layout' || $name === 'get_settings' || $name === 'get_pro') && empty($this->place_details)) {
                    $class = 'disabled';
                }
                echo '<a href="' . esc_url(admin_url('admin.php?page=google-reviews-settings&tab=widget_customizer&subtab=' . $name)) . '" class="nav-tab ' . ($current_subtab == $name ? 'nav-tab-active ' . esc_attr($class) : esc_attr($class)) . '">' . wp_kses_post($tab) . '</a>';
            }
            ?>
                <div id="link-support">
                    <a href="https://wordpress.org/support/plugin/widgets-for-google-reviews-and-ratings/">Support</a>
                </div>

            </h2>
            <?php
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_widget_customizer_connect_google_setting() {
        try {
            if (empty($this->place_details)) {
                echo '<div class="wgrr-connect-card"><iframe id="wrrr_google_connect" src="' . esc_url(REPOCEAN_URL . 'place-ui-unify.html') . '" width="100%" height="900px" scrolling="yes" frameborder="0" allowfullscreen></iframe></div>';
            } else {
                $this->wgrr_display_place_details($this->place_details);
            }
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_widget_customizer_get_settings_setting_save_field() {
        try {
            // Code for saving shortcode settings
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_save_place_details() {
        try {
            if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wgrr_nonce')) {
                wp_send_json_error(esc_html__('Nonce verification failed', 'widgets-for-google-reviews-and-ratings'));
            }
            if (isset($_POST['place']['placeId'])) {
                $place_data = json_decode(sanitize_text_field(wp_unslash($_POST['place']['placeId'])), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    update_option('wgrr_g_place_details', $place_data);
                    wp_send_json(['success' => true, 'return_url' => admin_url('admin.php?page=google-reviews-settings&tab=widget_customizer&subtab=select_layout')]);
                } else {
                    wp_send_json_error(esc_html__('Error decoding JSON.', 'widgets-for-google-reviews-and-ratings'));
                }
            } else {
                wp_send_json_error(esc_html__('Error: Place data not received.', 'widgets-for-google-reviews-and-ratings'));
            }
        } catch (Exception $ex) {
            wp_send_json_error(esc_html__('An error occurred: ', 'widgets-for-google-reviews-and-ratings') . esc_html($ex->getMessage()));
        }
    }

    public function generate_star_rating($rating) {
        try {
            $fullStars = floor($rating);
            $partialStar = $rating - $fullStars;
            $emptyStars = 5 - ceil($rating);
            $starHtml = '';
            for ($i = 0; $i < $fullStars; $i++) {
                $starHtml .= '<svg class="star" viewBox="0 0 24 24"><polygon points="12,2 15,8 22,9 17,14 18,21 12,17 6,21 7,14 2,9 9,8" fill="#fb8e28"/></svg>';
            }
            if ($partialStar > 0) {
                $partialStarOffset = esc_attr($partialStar * 100);
                $starHtml .= '
                <svg class="star" viewBox="0 0 24 24">
                    <defs>
                        <linearGradient id="partial-grad">
                            <stop offset="' . $partialStarOffset . '%" stop-color="#fb8e28" />
                            <stop offset="' . $partialStarOffset . '%" stop-color="#ccc" />
                        </linearGradient>
                    </defs>
                    <polygon points="12,2 15,8 22,9 17,14 18,21 12,17 6,21 7,14 2,9 9,8" fill="url(#partial-grad)"/>
                </svg>';
            }
            for ($i = 0; $i < $emptyStars; $i++) {
                $starHtml .= '<svg class="star" viewBox="0 0 24 24"><polygon points="12,2 15,8 22,9 17,14 18,21 12,17 6,21 7,14 2,9 9,8" fill="#ccc"/></svg>';
            }
            return $starHtml;
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_display_place_details($place) {
        try {
            /* phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage  */
            $rating = $place['rating'] ?? 0;
            echo '<div id="place-details" class="">
              <div class="place-info">
                <img id="place-img" src="' . esc_url(WGRR_ASSET_URL . 'admin/image/bussiness-logo.png') . '" 
                     alt="' . esc_attr__('Place Image', 'widgets-for-google-reviews-and-ratings') . '" width="50" height="50">
                <div class="place-details-wrapper">
                    <div id="place-name">' . esc_html($place['name']) . '</div>
                    <div class="place-meta">
                        <div class="review-box" style="--rating: ' . esc_attr($rating) . '; --star-color: ' . esc_attr($this->star_color) . ';"></div>
                        <span id="place-reviews">' . esc_html($place['user_ratings_total'] ?? 0) . ' ' . esc_html__('reviews', 'widgets-for-google-reviews-and-ratings') . '</span>
                    </div>
                    <div id="place-address">' . esc_html($place['formatted_address']) . '</div>
                </div>
            </div>
            <div id="additional-details"></div>
            <button id="wgrr-disconnect-btn-red" class="btn-primary">' . esc_html__('Disconnect', 'widgets-for-google-reviews-and-ratings') . '</button>
          </div>';
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_delete_place_details() {
        try {
            check_ajax_referer('wgrr_nonce', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error();
            }
            delete_option('wgrr_g_place_details');
            delete_transient('repocean_google_review');
            set_transient('wgrr_place_disconnected_message', esc_html__('Place disconnected successfully. Connect a new place.', 'widgets-for-google-reviews-and-ratings'), 60);
            wp_send_json_success();
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_display_message() {
        try {
            if ($message = get_transient('wgrr_place_disconnected_message')) {
                echo '<div class="notice notice-success is-dismissible wgrr-auto-notice"><p>' . esc_html($message) . '</p></div>';
                delete_transient('wgrr_place_disconnected_message');
            }
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_widget_customizer_select_layout_setting() {
        try {
            ?>
            <div id="wgrr_accordion">
                <h2 class="select-layout-title"></h2>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Slider', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="slider"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_slider_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Slider I.', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="slider_v1"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_slider_v1_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Slider II.', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="slider_v2"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_slider_v2_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Slider III.', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="slider_v3"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_slider_v3_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Slider IIII.', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="slider_v4"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_slider_v4_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Grid', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="grid"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_grid_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('List', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="list"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_list_widget()); ?>
                    </div>
                </div>
                <div class="layout-section">
                    <div class="layout-header-with-shodtcode">
                        <div class="layout-row">
                            <h3><?php esc_html_e('Sidebar', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            <div class="shortcode-container">
                                <span><?php esc_html_e('Shortcode:', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                <code class="repocean-shortcode" id="repocean-shortcode-id">[repocean_reviews layout="sidebar"]</code>
                                <button class="repocean-btn repocean-tooltip">
            <?php esc_html_e('Copy to Clipboard', 'widgets-for-google-reviews-and-ratings'); ?>
                                    <span class="repocean-tooltip-message" style="opacity: 0;"><?php esc_html_e('Copied!', 'widgets-for-google-reviews-and-ratings'); ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-content">
            <?php echo esc_html($this->wgrr_display_sidebar_widget()); ?>
                    </div>
                </div>
            </div>
            <?php
        } catch (Exception $ex) {
            // Handle exception
        }
    }

    public function wgrr_display_slider_widget() {
        echo do_shortcode('[repocean_reviews layout="slider"]');
    }

    public function wgrr_display_slider_v1_widget() {
        echo do_shortcode('[repocean_reviews layout="slider_v1"]');
    }

    public function wgrr_display_slider_v2_widget() {
        echo do_shortcode('[repocean_reviews layout="slider_v2"]');
    }

    public function wgrr_display_slider_v3_widget() {
        echo do_shortcode('[repocean_reviews layout="slider_v3"]');
    }

    public function wgrr_display_slider_v4_widget() {
        echo do_shortcode('[repocean_reviews layout="slider_v4"]');
    }

    public function wgrr_display_sidebar_widget() {
        echo do_shortcode('[repocean_reviews layout="sidebar"]');
    }

    public function wgrr_display_list_widget() {
        echo do_shortcode('[repocean_reviews layout="list"]');
    }

    public function wgrr_display_grid_widget() {
        echo do_shortcode('[repocean_reviews layout="grid"]');
    }

    public function register_repocean_reviews_widget() {
        register_widget('Widgets_For_Google_Reviews_And_Ratings_Widget');
    }

    public function repocean_get_user_status() {
        $activation_time = get_option('repocean_activation_time', false);
        $release_date = strtotime('2025-03-19 00:00:00');
        if (!$activation_time || $activation_time >= $release_date) {
            return 'new';
        }
        return 'existing';
    }

    public function repocean_get_user_status_4_july() {
        $activation_time = get_option('repocean_activation_time', false);
        $release_date = strtotime('2025-07-04 00:00:00');
        if (!$activation_time || $activation_time >= $release_date) {
            return 'new';
        }
        return 'existing';
    }

    public function wgrr_widget_customizer_get_settings_setting() {
        try {
            $repocean_show_verified_symbol_default_option = ($this->repocean_get_user_status_4_july() === 'existing') ? 'no' : 'yes';
            $repocean_show_verified_by_default_option = ($this->repocean_get_user_status() === 'existing') ? 'no' : 'yes';
            if (isset($_POST['repocean_submit'])) {
                if (!isset($_POST['google_reviews_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['google_reviews_nonce'])), 'google_reviews_settings_nonce')) {
                    wp_die(esc_html__('Security check failed. Nonce verification failed.', 'widgets-for-google-reviews-and-ratings'));
                }
                $options = [
                    'repocean_hide_date' => 'no',
                    'repocean_hide_profile_picture' => 'no',
                    'repocean_hide_google_logo' => 'no',
                    'repocean_hide_rating_text' => 'no',
                    'repocean_hide_prev_next_buttons' => 'no',
                    'repocean_hide_review_photos' => 'no',
                    'repocean_hide_review_us_button' => 'no',
                    'repocean_show_verified_symbol' => $repocean_show_verified_symbol_default_option,
                    'repocean_star_color' => '251,183,1',
                    'repocean_shorten_reviewer_names' => 'no',
                    'repocean_enable_dark_mode' => 'no',
                    'repocean_show_verified_by' => $repocean_show_verified_by_default_option,
                    'repocean_bg_color' => '#f6f6f6',
                    'repocean_border_color' => '#f6f6f6',
                    'repocean_hide_empty_reviews' => 'no',
                ];
                foreach ($options as $key => $default) {
                    $value = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : 'no';
                    update_option($key, $value);
                }
                $min_star_rating = isset($_POST['repocean_min_star_rating']) ? (int) $_POST['repocean_min_star_rating'] : 5;
                update_option('repocean_min_star_rating', $min_star_rating);
                $card_border_radius = isset($_POST['repocean_card_border_radius']) ? max(0, min(40, (int) $_POST['repocean_card_border_radius'])) : 12;
                update_option('repocean_card_border_radius', $card_border_radius);
                $review_text_lines = isset($_POST['repocean_review_text_lines']) ? (int) $_POST['repocean_review_text_lines'] : 5;
                if ($review_text_lines < 2 || $review_text_lines > 10) {
                    $review_text_lines = 5;
                }
                update_option('repocean_review_text_lines', (string) $review_text_lines);
                echo '<div class="notice notice-success is-dismissible wgrr-auto-notice"><p>' . esc_html__('Settings saved successfully!', 'widgets-for-google-reviews-and-ratings') . '</p></div>';
            }
            $hide_date = get_option('repocean_hide_date', 'no');
            $hide_profile_picture = get_option('repocean_hide_profile_picture', 'no');
            $hide_google_logo = get_option('repocean_hide_google_logo', 'no');
            $hide_rating_text = get_option('repocean_hide_rating_text', 'no'); // New Option
            $hide_prev_next_buttons = get_option('repocean_hide_prev_next_buttons', 'no'); // New Option
            $hide_review_photos = get_option('repocean_hide_review_photos', 'no'); // New Option
            $hide_review_us_button = get_option('repocean_hide_review_us_button', 'no');
            $show_verified_symbol = get_option('repocean_show_verified_symbol', $repocean_show_verified_symbol_default_option); // New Option
            $repocean_show_verified_by = get_option('repocean_show_verified_by', $repocean_show_verified_by_default_option);
            $star_color = get_option('repocean_star_color', '#FBB701'); // New Option
            $shorten_reviewer_names = get_option('repocean_shorten_reviewer_names', 'no');
            $enable_dark_mode = get_option('repocean_enable_dark_mode', 'no');
            $bg_color = get_option('repocean_bg_color', '#f6f6f6');
            $border_color = get_option('repocean_border_color', '#f6f6f6');
            $card_border_radius = (int) get_option('repocean_card_border_radius', 12);
            $min_star_rating = (int) get_option('repocean_min_star_rating', 5);
            $hide_empty_reviews = get_option('repocean_hide_empty_reviews', 'no');
            $review_text_lines = (int) get_option('repocean_review_text_lines', 5);
            if ($review_text_lines < 2 || $review_text_lines > 10) {
                $review_text_lines = 5;
            }
            ?>
            <div class="repocean-settings-wrapper">
                <form method="post" action="">
                    <div class="repocean-settings-panels">

                        <!-- Panel: Review Filters -->
                        <div class="repocean-panel">
                            <div class="repocean-panel-header">
                                <h3><?php esc_html_e('Review Filters', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            </div>
                            <div class="repocean-panel-body">
                                <table class="repocean-form-table">
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px;">
                                            <label for="repocean_min_star_rating"><?php esc_html_e('Minimum Star Rating', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <select id="repocean_min_star_rating" name="repocean_min_star_rating" style="min-width:160px; border:1px solid #ccc; border-radius:4px;">
                                                <option value="5" <?php selected($min_star_rating, 5); ?>><?php esc_html_e('5 Stars Only', 'widgets-for-google-reviews-and-ratings'); ?></option>
                                                <option value="4" <?php selected($min_star_rating, 4); ?>><?php esc_html_e('4 Stars and Up', 'widgets-for-google-reviews-and-ratings'); ?></option>
                                                <option value="3" <?php selected($min_star_rating, 3); ?>><?php esc_html_e('3 Stars and Up', 'widgets-for-google-reviews-and-ratings'); ?></option>
                                                <option value="2" <?php selected($min_star_rating, 2); ?>><?php esc_html_e('2 Stars and Up', 'widgets-for-google-reviews-and-ratings'); ?></option>
                                                <option value="1" <?php selected($min_star_rating, 1); ?>><?php esc_html_e('1 Star and Up', 'widgets-for-google-reviews-and-ratings'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_empty_reviews" name="repocean_hide_empty_reviews" value="yes" <?php checked($hide_empty_reviews, 'yes'); ?>>
                                            <label for="repocean_hide_empty_reviews"><?php esc_html_e('Hide Reviews Without Text', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Panel: Display Options -->
                        <div class="repocean-panel">
                            <div class="repocean-panel-header">
                                <h3><?php esc_html_e('Display Options', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            </div>
                            <div class="repocean-panel-body">
                                <table class="repocean-form-table">
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px;">
                                            <label for="repocean_review_text_lines"><?php esc_html_e('Review Text Lines', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <select id="repocean_review_text_lines" name="repocean_review_text_lines" style="min-width:60px; border:1px solid #ccc; border-radius:4px;">
                                                <?php for ($i = 2; $i <= 10; $i++) : ?>
                                                    <option value="<?php echo esc_attr($i); ?>" <?php selected($review_text_lines, $i); ?>><?php echo esc_html($i); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_date" name="repocean_hide_date" value="yes" <?php checked($hide_date, 'yes'); ?>>
                                            <label for="repocean_hide_date"><?php esc_html_e('Hide Review Date', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_profile_picture" name="repocean_hide_profile_picture" value="yes" <?php checked($hide_profile_picture, 'yes'); ?>>
                                            <label for="repocean_hide_profile_picture"><?php esc_html_e('Hide Reviewer Profile Picture', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_google_logo" name="repocean_hide_google_logo" value="yes" <?php checked($hide_google_logo, 'yes'); ?>>
                                            <label for="repocean_hide_google_logo"><?php esc_html_e('Hide Google Logo', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_rating_text" name="repocean_hide_rating_text" value="yes" <?php checked($hide_rating_text, 'yes'); ?>>
                                            <label for="repocean_hide_rating_text"><?php esc_html_e('Hide Widget Rating Summary (e.g. EXCELLENT XXX reviews)', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_prev_next_buttons" name="repocean_hide_prev_next_buttons" value="yes" <?php checked($hide_prev_next_buttons, 'yes'); ?>>
                                            <label for="repocean_hide_prev_next_buttons"><?php esc_html_e('Hide "Previous" and "Next" Buttons', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_review_photos" name="repocean_hide_review_photos" value="yes" <?php checked($hide_review_photos, 'yes'); ?>>
                                            <label for="repocean_hide_review_photos"><?php esc_html_e('Hide Review Photos', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_hide_review_us_button" name="repocean_hide_review_us_button" value="yes" <?php checked($hide_review_us_button, 'yes'); ?>>
                                            <label for="repocean_hide_review_us_button"><?php esc_html_e('Hide "Review Us on Google" Button', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_shorten_reviewer_names" name="repocean_shorten_reviewer_names" value="yes" <?php checked($shorten_reviewer_names, 'yes'); ?>>
                                            <label for="repocean_shorten_reviewer_names"><?php esc_html_e('Shorten Reviewer Names (e.g., John Smith → John S.)', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_show_verified_by" name="repocean_show_verified_by" value="yes" <?php checked($repocean_show_verified_by, 'yes'); ?>>
                                            <label for="repocean_show_verified_by"><?php esc_html_e('Show Verified By RepOcean', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="checkbox" id="repocean_show_verified_symbol" name="repocean_show_verified_symbol" value="yes" <?php checked($show_verified_symbol, 'yes'); ?>>
                                            <label for="repocean_show_verified_symbol"><?php esc_html_e('Show Verified Symbol for Reviews', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <span class="verified-icon-box"><span class="repocean-verified-tooltip" style="width: 101px;"><?php esc_html_e('RepOcean verifies that the original source of the review is Google.', 'widgets-for-google-reviews-and-ratings'); ?></span></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Panel: Style -->
                        <div class="repocean-panel">
                            <div class="repocean-panel-header">
                                <h3><?php esc_html_e('Style', 'widgets-for-google-reviews-and-ratings'); ?></h3>
                            </div>
                            <div class="repocean-panel-body">
                                <table class="repocean-form-table">
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px;">
                                            <label for="repocean_star_color"><?php esc_html_e('Star Color', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" id="star-icon" style="fill: <?php echo esc_attr(strtoupper($star_color) ?: '#FBB701'); ?>;">
                                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" />
                                                </svg>
                                                <input type="color" id="repocean_star_color" name="repocean_star_color" value="<?php echo esc_attr(strtoupper($star_color) ?: '#FBB701'); ?>" style="width:36px;height:36px;border:none;cursor:pointer;" oninput="const color=this.value.toUpperCase();document.getElementById('star-icon').style.fill=color;document.getElementById('repocean_star_color_text').value=color;" onchange="const color=this.value.toUpperCase();document.getElementById('star-icon').style.fill=color;document.getElementById('repocean_star_color_text').value=color;"/>
                                                <input type="text" id="repocean_star_color_text" name="repocean_star_color_text" value="<?php echo esc_attr(strtoupper($star_color) ?: '#FBB701'); ?>" style="padding:5px;width:100px;border:1px solid #ccc;text-align:center;" placeholder="#FBB701" oninput="let color=this.value.toUpperCase();if(!color.startsWith('#'))color='#'+color;if(color.length===7){document.getElementById('star-icon').style.fill=color;document.getElementById('repocean_star_color').value=color;}" onchange="if(!this.value.trim()){this.value='#FBB701';document.getElementById('repocean_star_color').value='#FBB701';document.getElementById('star-icon').style.fill='#FBB701';}"/>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px;">
                                            <label for="repocean_bg_color"><?php esc_html_e('Card Background Color', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <input type="color" id="repocean_bg_color" name="repocean_bg_color" value="<?php echo esc_attr(strtoupper($bg_color) ?: '#F6F6F6'); ?>" style="width:36px;height:36px;border:none;cursor:pointer;" oninput="document.getElementById('repocean_bg_color_text').value=this.value.toUpperCase();"/>
                                                <input type="text" id="repocean_bg_color_text" value="<?php echo esc_attr(strtoupper($bg_color) ?: '#F6F6F6'); ?>" placeholder="#F6F6F6" style="padding:5px;width:100px;border:1px solid #ccc;text-align:center;" oninput="let c=this.value.toUpperCase();if(!c.startsWith('#'))c='#'+c;if(c.length===7)document.getElementById('repocean_bg_color').value=c;" onchange="if(!this.value.trim()){this.value='#F6F6F6';document.getElementById('repocean_bg_color').value='#F6F6F6';}"/>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px;">
                                            <label for="repocean_border_color"><?php esc_html_e('Card Border Color', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <input type="color" id="repocean_border_color" name="repocean_border_color" value="<?php echo esc_attr(strtoupper($border_color) ?: '#F6F6F6'); ?>" style="width:36px;height:36px;border:none;cursor:pointer;" oninput="document.getElementById('repocean_border_color_text').value=this.value.toUpperCase();"/>
                                                <input type="text" id="repocean_border_color_text" value="<?php echo esc_attr(strtoupper($border_color) ?: '#F6F6F6'); ?>" placeholder="#F6F6F6" style="padding:5px;width:100px;border:1px solid #ccc;text-align:center;" oninput="let c=this.value.toUpperCase();if(!c.startsWith('#'))c='#'+c;if(c.length===7)document.getElementById('repocean_border_color').value=c;" onchange="if(!this.value.trim()){this.value='#F6F6F6';document.getElementById('repocean_border_color').value='#F6F6F6';}"/>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="display:flex; align-items:center; gap:10px;">
                                            <label for="repocean_card_border_radius"><?php esc_html_e('Card Border Radius', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <input type="range" id="repocean_card_border_radius" name="repocean_card_border_radius" min="0" max="40" value="<?php echo esc_attr($card_border_radius); ?>" oninput="document.getElementById('repocean_card_border_radius_val').textContent=this.value+'px';"/>
                                                <span id="repocean_card_border_radius_val" style="min-width:38px;"><?php echo esc_attr($card_border_radius); ?>px</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="display:block;">
                                            <input type="checkbox" id="repocean_enable_dark_mode" name="repocean_enable_dark_mode" value="yes" <?php checked($enable_dark_mode, 'yes'); ?>>
                                            <label for="repocean_enable_dark_mode"><?php esc_html_e('Enable Dark Mode (Dark background with light text)', 'widgets-for-google-reviews-and-ratings'); ?></label>
                                            <p style="margin:6px 0 0 26px; font-size:12px; color:#646970;"><?php esc_html_e('When enabled, the background and border colors will use dark-mode values.', 'widgets-for-google-reviews-and-ratings'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                    </div><!-- .repocean-settings-panels -->
                    <div class="repocean-submit-container">
            <?php wp_nonce_field('google_reviews_settings_nonce', 'google_reviews_nonce'); ?>
                        <button type="submit" name="repocean_submit" class="repocean-btn"><?php esc_html_e('Save Changes', 'widgets-for-google-reviews-and-ratings'); ?></button>
                    </div>
                </form>
            </div>
            <?php
        } catch (Exception $ex) {
            echo '<div class="notice notice-error"><p>' . esc_html__('An error occurred while saving the settings. Please try again.', 'widgets-for-google-reviews-and-ratings') . '</p></div>';
        }
    }

    public function plugin_action_links($actions) {
        $base_url = admin_url('admin.php?page=google-reviews-settings');
        $configure_url = $base_url;
        $configure = sprintf('<a href="%s">%s</a>', $configure_url, __('Settings', 'widgets-for-google-reviews-and-ratings'));
        $custom_actions = array(
            'settings' => $configure,
        );
        return array_merge($custom_actions, $actions);
    }

    public function add_plugin_meta_links($meta, $file) {
        if (basename($file) === basename(WGRR_PLUGIN_FILE)) {
            $meta[] = '<a href="https://wordpress.org/support/plugin/widgets-for-google-reviews-and-ratings/">' . __('Community support', 'widgets-for-google-reviews-and-ratings') . '</a>';
            $meta[] = '<a href="https://wordpress.org/support/plugin/widgets-for-google-reviews-and-ratings/reviews/#new-post" target="_blank" rel="noopener noreferrer">' . __('Rate our plugin', 'widgets-for-google-reviews-and-ratings') . '</a>';
        }
        return $meta;
    }

    public function wrrr_plugin_redirect_after_activation() {
        if (get_transient('wrrr_plugin_activation_redirect')) {
            delete_transient('wrrr_plugin_activation_redirect');
            if (is_network_admin() || isset($_GET['activate-multi'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
            wp_redirect(admin_url('admin.php?page=google-reviews-settings'));
            exit;
        }
    }

    public function repocean_add_deactivation_feedback_form() {
        global $pagenow;
        if ('plugins.php' != $pagenow) {
            return;
        }
        include_once(WGRR_PLUGIN_DIR . '/admin/feedback/deactivation-feedback-form.php');
    }

    public function repocean_add_deactivation_feedback_form_scripts() {
        global $pagenow;
        if ('plugins.php' != $pagenow) {
            return;
        }
        wp_enqueue_script('jquery-blockui');
        wp_enqueue_style('deactivation-feedback-modal-repocean', WGRR_ASSET_URL . 'admin/feedback/css/deactivation-feedback-modal.css', null, WGRR_VERSION);
        wp_enqueue_script('deactivation-feedback-modal-repocean', WGRR_ASSET_URL . 'admin/feedback/js/deactivation-feedback-modal.js', null, WGRR_VERSION, true);
        wp_localize_script('deactivation-feedback-modal-repocean', 'repocean_feedback_form_ajax_data', array('nonce' => wp_create_nonce('repocean-ajax')));
    }

    public function repocean_handle_plugin_deactivation_request() {
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        $reason_details = isset($_POST['reason_details']) ? sanitize_text_field($_POST['reason_details']) : '';
        $url = 'https://api.airtable.com/v0/appxxiU87VQWG6rOO/Sheet1';
        $api_key = 'patgeqj8DJfPjqZbS.9223810d432db4efccf27354c08513a7725e4a08d11a85fba75de07a539c8aeb';
        $data = array(
            'reason' => $reason . ' : ' . $reason_details,
            'plugin' => 'Google Business Reviews',
            'php_version' => phpversion(),
            'wp_version' => get_bloginfo('version'),
            'wc_version' => (!defined('WC_VERSION')) ? '' : WC_VERSION,
            'locale' => get_locale(),
            'theme' => wp_get_theme()->get('Name'),
            'theme_version' => wp_get_theme()->get('Version'),
            'multisite' => is_multisite() ? 'Yes' : 'No',
            'plugin_version' => WGRR_VERSION
        );
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'records' => array(
                    array(
                        'fields' => array(
                            'reason' => json_encode($data),
                            'date' => date('M d, Y h:i:s A')
                        ),
                    ),
                ),
            )),
            'method' => 'POST'
        );
        $response = wp_remote_post($url, $args);
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => 'Error communicating with Airtable',
                'error' => $response->get_error_message()
            ));
        } else {
            wp_send_json_success(array(
                'message' => 'Deactivation feedback submitted successfully',
                'response' => json_decode(wp_remote_retrieve_body($response), true)
            ));
        }
    }

    public function wgrr_widget_customizer_get_pro_setting() {
        $place_id = is_array($this->place_details) && !empty($this->place_details['place_id']) ? $this->place_details['place_id'] : '';
        $pricing_url = add_query_arg('place_id', rawurlencode($place_id), REPOCEAN_URL . 'widget/pro.php');
        ?> <iframe src="<?php echo esc_url($pricing_url); ?>" title="Embedded Content from repocean.com" width="100%" height="900px" scrolling="yes" frameborder="0" allowfullscreen></iframe><?php
    }
}
