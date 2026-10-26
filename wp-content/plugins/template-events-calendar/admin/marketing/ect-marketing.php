<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
if (!class_exists('ECT_TEC_Notice')) {

    class ECT_TEC_Notice
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            $active_plugins = get_option('active_plugins', []);
            $all_plugins    = get_plugins();
            
             if (!is_plugin_active('timeline-widget-addon-for-elementor/timeline-widget-addon-for-elementor.php') &&
                !is_plugin_active('mask-form-elementor/index.php') &&
                !is_plugin_active('form-masks-for-elementor/form-masks-for-elementor.php') &&
                !is_plugin_active('country-code-field-for-elementor-form/country-code-field-for-elementor-form.php') &&
                !is_plugin_active('conditional-fields-for-elementor-form/class-conditional-fields-for-elementor-form.php')
                ) {
                if (did_action('elementor/loaded') && class_exists('\Elementor\Plugin') 
                    && !array_key_exists('events-widgets-pro/events-widgets-pro.php', $all_plugins)) {
                    add_action('ect_display_admin_notices', [$this, 'show_elementor_notice']);
                }
            }
            if (class_exists('EventsCalendarTemplates') && self::is_theme_activate('Divi') &&
                !in_array('events-calendar-modules-for-divi/events-calendar-modules-for-divi.php', $active_plugins, true) &&
                !in_array('events-calendar-modules-for-divi-pro/events-calendar-modules-for-divi-pro.php', $active_plugins, true) &&
                !in_array('cp-events-calendar-modules-for-divi-pro/cp-events-calendar-modules-for-divi-pro.php', $active_plugins, true)) {
                add_action('ect_display_admin_notices', [$this, 'show_divi_notice']);
            }
            add_action('wp_ajax_ect_install_plugin', [$this, 'ect_install_plugin']);
            add_action('wp_ajax_ect_dismiss_notice', [$this, 'ect_dismiss_notice']);
        }

        /**
         * Check if the theme is activated
         */
        public static function is_theme_activate($target)
		{
			$theme = wp_get_theme();
			if ($theme->name == $target || stripos($theme->parent_theme, $target) !== false) {
				return true;
			}
			return false;
		}
        /**
         * Enqueue marketing scripts
         */
        public function ect_enqueue_marketing_scripts() {
            wp_register_script(
                'ect-tec-notice-js',
                ECT_PLUGIN_URL . 'admin/marketing/js/ect-marketing.js',
                ['jquery'],
                ECT_VERSION,
                true
            );
            wp_enqueue_script('ect-tec-notice-js');
        }
        /**
         * Elementor Notice
         */
        public function show_elementor_notice()
        {
            if (!class_exists('Tribe__Events__Main') || get_option('ect_elementor_notice_dismissed')) {
                return;
            }

            $screen = get_current_screen();
            if (!$screen) {
                return;
            }

            $allowed_screens = [
                'edit-tribe_events',
                'tribe_events',                     
                'tribe_events_page_tec-events-settings',
                'events-addons_page_tribe_events-events-template-settings',
                'plugins'
            ];

            if (!in_array($screen->id, $allowed_screens, true)) {
                return; 
            }
            $this->ect_enqueue_marketing_scripts();
                ?>
                <div class="notice notice-info is-dismissible ect-tec-notice-elementor ect-required-plugin-notice"
                    data-notice="tec_notice_elementor"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('ect_dismiss_nonce_tec_elementor')); ?>">
                    <p class="ect-notice-widget">
                    <a href="https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=ectbe_inside_notice" 
                        target="_blank" class="button button-primary">
                            Try it now!
                        </a>
                        We have noticed that you are currently using the Elementor Page Builder. We would like to suggest trying out the latest 
                        <a href="https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=ectbe_inside_notice" target="_blank">
                        Events Widgets for Elementor
                        </a> plugin.
                    </p>
                </div>
                <?php
        }

        /**
         * Divi Notice
         */
        public function show_divi_notice()
        {
            if (!class_exists('Tribe__Events__Main') || get_option('ect_divi_notice_dismissed')) {
                return;
            }

            $screen = get_current_screen();
            if (!$screen) {
                return;
            }

            $allowed_screens = [
                'edit-tribe_events',
                'tribe_events',                     
                'tribe_events_page_tec-events-settings',
                'events-addons_page_tribe_events-events-template-settings',
                'plugins'
            ];

            if (!in_array($screen->id, $allowed_screens, true)) {
                return; 
            }
                $this->ect_enqueue_marketing_scripts();
                ?>
                <div class="notice notice-info is-dismissible ect-tec-notice-divi ect-required-plugin-notice"
                    data-notice="tec_notice_divi"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('ect_dismiss_nonce_tec_divi')); ?>">
                    <p class="ect-notice-widget">
                        <button class="button button-primary ect-install-plugin"
                            data-plugin="events-calendar-modules-for-divi"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('ect_install_nonce')); ?>">
                            Install Events Calendar Modules for Divi
                        </button>
                        Easily display The Events Calendar events on your Divi pages.
                    </p>
                </div>
                <?php
        }

        /**
         * Dismiss notice
         */
        public function ect_dismiss_notice()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }

            $notice_type = isset($_POST['notice']) ? sanitize_text_field(wp_unslash($_POST['notice'])) : '';
            $nonce       = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

            if ($notice_type === 'tec_notice_elementor' && wp_verify_nonce($nonce, 'ect_dismiss_nonce_tec_elementor')) {
                update_option('ect_elementor_notice_dismissed', true);
                wp_send_json_success();
            } elseif ($notice_type === 'tec_notice_divi' && wp_verify_nonce($nonce, 'ect_dismiss_nonce_tec_divi')) {
                update_option('ect_divi_notice_dismissed', true);
                wp_send_json_success();
            }

            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        /**
         * Install plugin via AJAX
         */
        public function ect_install_plugin()
        {
            check_ajax_referer('ect_install_nonce');

            if (!current_user_can('install_plugins')) {
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				$status['errorMessage'] = __( 'Sorry, you are not allowed to install plugins on this site.', 'template-events-calendar' );
                wp_send_json_error(['message' => 'Permission denied']);
            }
    
            if ( empty( $_POST['slug'] ) ) {
				wp_send_json_error( array(
					'slug'         => '',
					'errorCode'    => 'no_plugin_specified',
					// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'errorMessage' => __( 'No plugin specified.', 'template-events-calendar' ),
				));
			}
        
            $plugin_slug = sanitize_key( wp_unslash( $_POST['slug'] ) );

			// Only allow installation of known marketing plugins (ignore client-manipulated slugs).
			$allowed_slugs = array(
				'events-calendar-modules-for-divi'
			);
			if ( ! in_array( $plugin_slug, $allowed_slugs, true ) ) {
				wp_send_json_error( array(
					'slug'         => $plugin_slug,
					'errorCode'    => 'plugin_not_allowed',
					// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					'errorMessage' => __( 'This plugin cannot be installed from here.', 'template-events-calendar' ),
				));
			}


			$status = array(
                'install' => 'plugin',
                'slug'    => $plugin_slug,
            );

            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
            // API call correct slug se
            $api = plugins_api('plugin_information', [
                'slug'   => $plugin_slug,
                'fields' => ['sections' => false]
            ]);
        
            if (is_wp_error($api)) {
                wp_send_json_error(['message' => sanitize_text_field($api->get_error_message())]);
            }
        
            $skin     = new WP_Ajax_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);
            $result   = $upgrader->install($api->download_link);
        
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => sanitize_text_field($result->get_error_message())]);
            }
        
            $install_status = install_plugin_install_status($api);
            if (current_user_can('activate_plugin', $install_status['file'])) {
                $network_wide = is_multisite() && current_user_can( 'manage_network_plugins' );
                $activation_result = activate_plugin(
                    $install_status['file'],
                    '',
                    $network_wide
                );
                if (is_wp_error($activation_result)) {
                    wp_send_json_error(['message' => sanitize_text_field($activation_result->get_error_message())]);
                }
            }
        
            wp_send_json_success(['message' => 'Plugin installed and activated']);
        }
        
    }

    ECT_TEC_Notice::get_instance();
}
