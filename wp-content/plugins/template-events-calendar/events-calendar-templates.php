<?php
/*
Plugin Name:Events Shortcodes For The Events Calendar
Plugin URI:https://eventscalendaraddons.com/plugin/events-shortcodes-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugin_uri
Description:<a href="http://wordpress.org/plugins/the-events-calendar/">📅 The Events Calendar Addon</a> - Shortcodes to show The Events Calendar plugin events list on any page or post in different layouts.
Version:2.8.4
Requires PHP:7.2
Author:Cool Plugins
Author URI: https://coolplugins.net/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: template-events-calendar
Requires Plugins: the-events-calendar
*/

if (! defined('ABSPATH')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}
if (! defined('ECT_VERSION')) {
	define('ECT_VERSION', '2.8.4');//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
}

/*** Defined constent for later use */
define('ECT_PLUGIN_URL', plugin_dir_url(__FILE__));//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define('ECT_PLUGIN_DIR', plugin_dir_path(__FILE__));//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define('ECT_PLUGIN_FILE', __FILE__);//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
define('ECT_FEEDBACK_URL','https://feedback.coolplugins.net/');//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound

/*** EventsCalendarTemplates main class by CoolPlugins.net */
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
if (! class_exists('EventsCalendarTemplates')) {
	final class EventsCalendarTemplates
	{

		/**
		 * The unique instance of the plugin.
		 */
		private static $instance;

		/**
		 * Gets an instance of our plugin.
		 */
		public static function get_instance()
		{
			if (null === self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {}

		// register all hooks
		public function register_hooks()
		{
			
			if (file_exists(plugin_dir_path(__DIR__) . 'the-events-calendar-templates-and-shortcode/the-events-calendar-templates-and-shortcode.php')) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if (is_plugin_active('the-events-calendar-templates-and-shortcode/the-events-calendar-templates-and-shortcode.php')) {
					deactivate_plugins(plugin_basename(__FILE__));
					return;
				}
			}


			/*** Installation and uninstallation hooks */
			register_activation_hook(__FILE__, array('EventsCalendarTemplates', 'activate'));
			register_deactivation_hook(__FILE__, array('EventsCalendarTemplates', 'deactivate'));
			 
			add_action('admin_init', array(self::$instance, 'ect_settings_migration'));

			if ( is_admin() ) {
				require_once ECT_PLUGIN_DIR . 'admin/class-ect-eca-integration.php';
				ECT_ECA_Integration::boot_admin();
			}

			/*** Check The Event Calendar is installed or not */
			add_action('plugins_loaded', array(self::$instance, 'ect_check_event_calender_installed'));

			/*** Load required files */
			add_action('plugins_loaded', array(self::$instance, 'ect_load_files'));
			add_action('after_setup_theme', array(self::$instance, 'ect_load_bricks_integration'), 11);
			add_action('init', array(self::$instance, 'ect_load_textdomain'));
			add_action('admin_enqueue_scripts', array(self::$instance, 'ect_tc_css'));
			/*** Template Setting Page Link */
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(self::$instance, 'ect_template_settings_page'));
			add_action('plugin_row_meta', array(self::$instance, 'ect_addMeta_Links'), 10, 2);
			foreach (array('post.php', 'post-new.php') as $hook) {
				add_action("admin_head-$hook", array(self::$instance, 'ect_rest_url'));
			}

			/*** Include Gutenberg Block */
			require_once ECT_PLUGIN_DIR . 'admin/gutenberg-block/ect-block.php';

			/***Include Share Buttons*/
			require_once ECT_PLUGIN_DIR . '/includes/ect-share-functions.php';
			$this->cpfm_feedback_cron_init();
			add_action('init', array($this, 'register_cpfm_notices'), 999);
			add_action('cpfm_after_opt_in_ect', array($this, 'ect_handle_cpfm_opt_in'));
			add_action('admin_print_scripts', [$this, 'ect_hide_unrelated_notices']);

			add_action( 'plugin_opt_in_template-events-calendar', function () {
				$ects_options = get_option( 'ects_options' );
				if ( ! is_array( $ects_options ) ) {
					$ects_options = array();
				}
				$ects_options['ect_cpfm_feedback_data'] = true;
				update_option( 'ects_options', $ects_options );

				$this->ect_register_cpfm_usage_cron();

				if ( class_exists( '\CPFM_Usage_Cron' ) ) {
					\CPFM_Usage_Cron::cpfm_schedule_event( 'ect_extra_data_update' );
				}
			} );
		}

		public function ect_hide_unrelated_notices() {
			$events_pages      = $this->ect_is_events_admin_page();
			$is_post_type_page = $this->ect_is_events_post_type();

			if ( $events_pages ) {
				$this->ect_strip_foreign_notices();
			}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			if ( ! $events_pages && ! $is_post_type_page ) {
				if ( ! defined( 'ECT_ADMIN_NOTICE_HOOKED' ) ) {
					define( 'ECT_ADMIN_NOTICE_HOOKED', true );

					add_action(
						'admin_notices',
						array( $this, 'ect_dash_admin_notices' ),
						PHP_INT_MAX
					);
				}
			}
		}

		/**
		 * Check whether the current admin screen is an events addon page.
		 *
		 * @return bool
		 */
		private function ect_is_events_admin_page() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking page parameter to conditionally hide notices, no data processing
			if ( ! isset( $_GET['page'] ) ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking page parameter to conditionally hide notices, no data processing
			$page_param = sanitize_key( wp_unslash( $_GET['page'] ) );

			$allowed_pages = array(
				'cool-plugins-events-addon',
				'cool-events-registration',
				'tribe-events-shortcode-template-settings',
				'tribe_events-events-template-settings',
				'countdown_for_the_events_calendar',
				'esas-speaker-sponsor-settings',
				'esas_speaker',
				'esas_sponsor',
				'ewpe',
				'epta',
			);

			if ( class_exists( 'ECT_ECA_Integration' ) ) {
				$allowed_pages = array_merge( $allowed_pages, ECT_ECA_Integration::admin_page_slugs() );
			}

			$allowed_pages = array_values( array_unique( $allowed_pages ) );

			return in_array( $page_param, $allowed_pages, true );
		}

		/**
		 * Check whether the current admin screen is an events addon post type.
		 *
		 * @return bool
		 */
		private function ect_is_events_post_type() {
			$current_screen = get_current_screen();

			if ( ! $current_screen || empty( $current_screen->post_type ) ) {
				return false;
			}

			$allowed_post_types = array(
				'esas_speaker',
				'esas_sponsor',
				'epta',
				'ewpe',
			);

			return in_array( $current_screen->post_type, $allowed_post_types, true );
		}

		/**
		 * Remove third-party admin notices on events addon pages.
		 */
		private function ect_strip_foreign_notices() {
			global $wp_filter;

			$rules = array(
				'user_admin_notices' => array(),
				'admin_notices'      => array(),
				'all_admin_notices'  => array(),
				'admin_footer'       => array(
					'render_delayed_admin_notices',
				),
			);

			foreach ( array_keys( $rules ) as $notice_type ) {
				if ( empty( $wp_filter[ $notice_type ] ) || empty( $wp_filter[ $notice_type ]->callbacks ) || ! is_array( $wp_filter[ $notice_type ]->callbacks ) ) {
					continue;
				}

				$remove_all_filters = empty( $rules[ $notice_type ] );

				foreach ( $wp_filter[ $notice_type ]->callbacks as $priority => $hooks ) {
					foreach ( $hooks as $name => $arr ) {
						if ( is_object( $arr['function'] ) && is_callable( $arr['function'] ) ) {
							if ( $remove_all_filters ) {
								unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
							}
							continue;
						}

						$class = ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) ? strtolower( get_class( $arr['function'][0] ) ) : '';

						if ( $remove_all_filters && strpos( $class, 'wpforms' ) === false ) {
							unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
							continue;
						}

						$cb = is_array( $arr['function'] ) ? $arr['function'][1] : $arr['function'];

						if ( ! $remove_all_filters && in_array( $cb, $rules[ $notice_type ], true ) ) {
							unset( $wp_filter[ $notice_type ]->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}


		}

		/**
		 * Boot CPFM loader, usage cron registration, and onboarding telemetry filter.
		 * Loaded unconditionally so WP-Cron requests can execute data sharing without is_admin().
		 *
		 * @return void
		 */
		public function cpfm_feedback_cron_init() {
			if ( ! class_exists( 'CPFM_Loader' ) ) {
				$file = ECT_PLUGIN_DIR . 'admin/cpfm-feedback/class-cpfm-loader.php';
				if ( file_exists( $file ) ) {
					require_once $file;
				}
			}

			if ( class_exists( 'CPFM_Loader' ) ) {
				CPFM_Loader::load();
			}

			$this->ect_register_cpfm_usage_cron();

			// Always load the onboarding data class so the cpfm_environment filter
			// is wired on every request type — including WP-Cron and AJAX — not just
			// admin page views. Without this, onboarding selections are never appended
			// to the cron payload because the filter callback is never registered.
			if ( ! class_exists( 'ECT_Onboarding_Cpfm_Data', false ) ) {
				$onboarding_file = ECT_PLUGIN_DIR . 'admin/ect-onboarding/includes/class-ect-onboarding-cpfm-data.php';
				if ( file_exists( $onboarding_file ) ) {
					require_once $onboarding_file;
				}
			}
		}

		/**
		 * Register the shared usage-data cron (admin + WP-Cron).
		 *
		 * @return void
		 */
		public function ect_register_cpfm_usage_cron() {
			static $usage_cron_registered = false;

			if ( $usage_cron_registered || ! class_exists( 'CPFM_Usage_Cron' ) ) {
				return;
			}

			$usage_cron_registered = true;

			CPFM_Usage_Cron::cpfm_register(
				array(
					'id'                     => 'ect',
					'plugin_name'            => 'Events Shortcodes For The Events Calendar',
					'version'                => defined( 'ECT_VERSION' ) ? ECT_VERSION : '',
					'api'                    => defined( 'ECT_FEEDBACK_URL' ) ? ECT_FEEDBACK_URL : 'https://feedback.coolplugins.net/',
					'cron_hook'              => 'ect_extra_data_update',
					'consent_master_option'  => 'cpfm_opt_in_choice_cool_events',
					'consent_callback'       => array( $this, 'ect_has_usage_tracking_consent' ),
					'install_date_option'    => 'ect-install-date',
					'initial_version_option' => 'ect-initial-save-version',
					'site_key'               => '20',
					'onboarding_data'        => 'cpfm_onboarding_preferences_cool_events',
				)
			);
		}

		/**
		 * Whether usage-data sharing is enabled for Events Shortcodes Free.
		 *
		 * @return bool
		 */
		public function ect_has_usage_tracking_consent() {
			$data = get_option( 'ects_options' );

			if ( is_array( $data ) && ! empty( $data['ect_cpfm_feedback_data'] ) ) {
				return true;
			}

			return ( 'yes' === get_option( 'cpfm_opt_in_choice_cool_events' ) );
		}

		/**
		 * Schedule the usage tracking cron when it is not already scheduled.
		 *
		 * @return void
		 */
		public function ect_maybe_schedule_tracking_cron() {
			$this->ect_register_cpfm_usage_cron();

			if ( class_exists( 'CPFM_Usage_Cron' ) ) {
				CPFM_Usage_Cron::cpfm_schedule_event( 'ect_extra_data_update' );
			}
		}

		/**
		 * Opt-in handler: persist consent, send first payload, schedule cron.
		 *
		 * @param string $category Notice category key.
		 * @return void
		 */
		public function ect_handle_cpfm_opt_in( $category ) {
			if ( 'cool_events' !== $category ) {
				return;
			}

			$this->ect_register_cpfm_usage_cron();

			$ects_options = get_option( 'ects_options' );
			if ( ! is_array( $ects_options ) ) {
				$ects_options = array();
			}
			$ects_options['ect_cpfm_feedback_data'] = true;
			update_option( 'ects_options', $ects_options );

			do_action( 'ect_extra_data_update' );
			$this->ect_maybe_schedule_tracking_cron();
		}

		/**
		 * Register CPFM notices (opt-in notice panel, review prompt, and deactivation survey).
		 *
		 * @return void
		 */
		public function register_cpfm_notices() {
			if ( ! is_admin() ) {
				return;
			}

			static $registered = false;
			if ( $registered ) {
				return;
			}
			$registered = true;

			add_action(
				'cpfm_register_notice',
				function () {
					if ( ! class_exists( 'CPFM_Feedback_Notice' ) || ! current_user_can( 'manage_options' ) ) {
						return;
					}

					$notice_pages = array( 'cool-plugins-events-addon', 'tribe_events-events-template-settings', 'ect-onboarding' );
					if ( class_exists( 'ECT_ECA_Integration' ) ) {
						$notice_pages = array_merge( $notice_pages, ECT_ECA_Integration::admin_page_slugs() );
					}
					$notice_pages = array_values( array_unique( $notice_pages ) );

					$notice = array(
						'title'          => __( 'Events Addons By Cool Plugins', 'template-events-calendar' ),
						'message'        => __( 'Help us make this plugin more compatible with your site by sharing non-sensitive site data.', 'template-events-calendar' ),
						'pages'          => $notice_pages,
						'always_show_on' => $notice_pages,
						'plugin_name'    => 'ect',
					);

					CPFM_Feedback_Notice::cpfm_register_notice( 'cool_events', $notice );

					if ( ! isset( $GLOBALS['cool_plugins_feedback'] ) ) {
						$GLOBALS['cool_plugins_feedback'] = array(); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					}

					$GLOBALS['cool_plugins_feedback']['cool_events'][] = $notice; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
				}
			);

			if ( class_exists( 'CPFM_Deactivation_Feedback' ) ) {
				$name = 'Events Shortcodes For The Events Calendar';

				CPFM_Deactivation_Feedback::cpfm_register(
					array(
						'id'                     => 'ect',
						'slug'                   => 'template-events-calendar',
						'plugin_name'            => $name,
						'version'                => defined( 'ECT_VERSION' ) ? ECT_VERSION : '',
						'api'                    => defined( 'ECT_FEEDBACK_URL' ) ? ECT_FEEDBACK_URL : 'https://feedback.coolplugins.net/',
						'site_key'               => '20',
						'install_date_option'    => 'ect-install-date',
						'initial_version_option' => 'ect-initial-save-version',
						'onboarding_data'        => 'cpfm_onboarding_preferences_cool_events',
						'reasons'                => array(
							'not_working'  => array(
								'title'       => __( "The plugin isn't working", 'template-events-calendar' ),
								'placeholder' => __( 'Which problem did you run into? We read every reply.', 'template-events-calendar' ),
							),
							'not_expected' => array(
								'title'       => __( "It didn't do what I expected", 'template-events-calendar' ),
								'placeholder' => __( 'What were you hoping it would do?', 'template-events-calendar' ),
							),
							'found_better' => array(
								'title'       => __( 'I found a better plugin', 'template-events-calendar' ),
								'placeholder' => __( 'Mind sharing which one?', 'template-events-calendar' ),
							),
							'temporary'    => array(
								'title'       => __( "It's a temporary deactivation", 'template-events-calendar' ),
								'placeholder' => '',
							),
							'other'        => array(
								'title'       => __( 'Another reason', 'template-events-calendar' ),
								'placeholder' => __( 'Please tell us more', 'template-events-calendar' ),
							),
						),
						'i18n'                   => array(
							'title'           => __( 'Before you go…', 'template-events-calendar' ),
							/* translators: %s: plugin name (bold). */
							'intro'           => __( 'What made you deactivate %s? Your answer helps us fix it.', 'template-events-calendar' ),
							'submit'          => __( 'Submit & Deactivate', 'template-events-calendar' ),
							'skip'            => __( 'Skip & Deactivate', 'template-events-calendar' ),
							'deactivating'    => __( 'Deactivating…', 'template-events-calendar' ),
							'pick_reason'     => __( 'Please choose a reason.', 'template-events-calendar' ),
							'close_label'     => __( 'Close', 'template-events-calendar' ),
							/* translators: %s: company name. */
							'byline'          => __( 'A plugin by %s', 'template-events-calendar' ),
							'consent'         => __( 'Submitting shares your reason plus your site URL, admin email and basic environment details (PHP, WordPress, active plugins). Skip & Deactivate sends nothing.', 'template-events-calendar' ),
						),
					)
				);
			}

			if ( ! class_exists( 'CPFM_Review' ) ) {
				$review = ECT_PLUGIN_DIR . 'admin/cpfm-feedback/class-cpfm-review.php';
				if ( file_exists( $review ) ) {
					require_once $review;
				}
			}

			if ( class_exists( 'CPFM_Review' ) ) {
				$name = 'Events Shortcodes';

				CPFM_Review::cpfm_register(
					array(
						'id'          => 'ect',
						'plugin_file' => __FILE__,
						'plugin_name' => $name,
						'review_url'  => 'https://wordpress.org/support/plugin/template-events-calendar/reviews/#new-post',
						'capability'  => 'activate_plugins',
						'quiet_days'  => 0,
						'own_screens' => array(
							'events-addons_page_tribe_events-events-template-settings',
							'cool-plugins-events-addon_page_tribe_events-events-template-settings',
							'toplevel_page_cool-plugins-events-addon',
						),
						'trigger'     => array(
							'type'  => 'install_age',
							'hours' => 24,
						),
						'notice'      => array(
							'enabled'        => true,
							'template'       => 'two_step',
							'screens'        => array(
								'plugins',
								'edit-tribe_events',
								'cool-plugins-events-addon',
								'events-addons_page_tribe_events-events-template-settings',
								'cool-plugins-events-addon_page_tribe_events-events-template-settings',
								'toplevel_page_cool-plugins-events-addon',
							),
							'inline_screens' => array(),
						),
						'row'         => array( 'enabled' => true ),
						'legacy'      => array(
							'done_options'   => array(
								'ect_review_prompt' => array( 'yes', 'done', 'dismissed' ),
								'ect_review_shown'  => array( 'yes', 'done', 'dismissed' ),
								'ect-ratingDiv'     => array( 'yes', 'done', 'dismissed' ),
							),
							'done_user_meta' => array(
								'ect_review_dismissed' => array( '1', 'yes', 'true' ),
							),
							'install_dates'  => array( 'ect-free-installDate', 'ect-install-date' ),
							'mirror_write'   => array( 'ect-ratingDiv' => 'yes' ),
						),
						'i18n'        => array(
							'like_question' => sprintf(
								/* translators: %s: plugin name. */
								__( 'Do you like the %s plugin?', 'template-events-calendar' ),
								$name
							),
							'yes_button'    => __( 'Yes, I like it', 'template-events-calendar' ),
							'dismiss_link'  => __( 'Not good, dismiss', 'template-events-calendar' ),
							'later_link'    => __( 'Ask me later', 'template-events-calendar' ),
							'thanks_line'   => __( 'That is great to hear! A quick review on WordPress.org would really help us.', 'template-events-calendar' ),
							'submit_button' => __( 'Submit review', 'template-events-calendar' ),
							'no_link'       => __( 'I do not like it, dismiss', 'template-events-calendar' ),
							'row_question'  => __( 'Do you like this plugin?', 'template-events-calendar' ),
							'inline_title'  => sprintf(
								/* translators: %s: plugin name. */
								__( 'Enjoying %s?', 'template-events-calendar' ),
								$name
							),
							'inline_text'   => __( 'A short review helps other event organisers find it.', 'template-events-calendar' ),
							'close_label'   => __( 'Close', 'template-events-calendar' ),
						),
					)
				);

				add_action(
					'ect_display_admin_notices',
					static function () {
						if ( class_exists( 'CPFM_Review_Notice' ) ) {
							CPFM_Review_Notice::cpfm_maybe_render( false );
						}
					}
				);
			}
		}
		
		public function ect_dash_admin_notices() {

			// ✅ Double render protection
			if (defined('ECT_ADMIN_NOTICE_RENDERED')) {
				return;
			}

			define('ECT_ADMIN_NOTICE_RENDERED', true);

			do_action('ect_display_admin_notices');
		}
		
		/*** Load Text domain */
		//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		public function ect_load_textdomain()
		{
			load_plugin_textdomain('template-events-calendar', false, basename(dirname(__FILE__)) . '/languages/');
			if (!get_option('ect-initial-save-version')) {
				add_option('ect-initial-save-version', ECT_VERSION);
			}
			if (!get_option('ect-install-date')) {
				add_option('ect-install-date', gmdate('Y-m-d h:i:s'));
			}
		}

		/**
		 * Whether the Bricks theme is active.
		 *
		 * @return bool
		 */
		public static function ect_is_bricks_theme_active()
		{
			return defined('BRICKS_VERSION') || get_template() === 'bricks';
		}

		/**
		 * Load Bricks Builder integration when Bricks theme is active.
		 *
		 * @return void
		 */
		public function ect_load_bricks_integration()
		{
			if (! self::ect_is_bricks_theme_active()) {
				return;
			}

			if (! class_exists('Tribe__Events__Main') && ! defined('Tribe__Events__Main::VERSION')) {
				return;
			}

			require_once ECT_PLUGIN_DIR . 'bricks/bricks.php';
		}

		/*** Load required files */
		public function ect_load_files()
		{
			if (class_exists('Tribe__Events__Main') or defined('Tribe__Events__Main::VERSION')) {
				if (defined('WPB_VC_VERSION')) {
					require_once ECT_PLUGIN_DIR . 'admin/visual-composer/ect-class-vc.php';
				}
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if (! is_plugin_active('events-block-for-the-events-calendar/events-block-for-the-event-calender.php')) {
					require ECT_PLUGIN_DIR . '/includes/events-shortcode-block/includes/ebec-functions.php';
					require ECT_PLUGIN_DIR . '/includes/events-shortcode-block/includes/ebec-block.php';
				}
			}

			if (is_admin()) {
				/*** Plugin marketing notice file */
				require_once ECT_PLUGIN_DIR . 'admin/marketing/ect-marketing.php';

				require_once ECT_PLUGIN_DIR . 'admin/codestar-framework/codestar-framework.php';
				require_once ECT_PLUGIN_DIR . 'admin/ect-codestar-settings.php';
				$settings_panel = new ECTSettings();
			}

			/*** Include helpers functions*/
			require_once ECT_PLUGIN_DIR . 'includes/ect-functions.php';

			require_once ECT_PLUGIN_DIR . 'includes/events-shortcode.php';
			EventsShortcode::registers();
			require_once ECT_PLUGIN_DIR . 'admin/ect-event-shortcode.php';
		}
		
		/*** Check The Events calender is installled or not. If user has not installed yet then show notice */
		public function ect_check_event_calender_installed()
		{
			if (! class_exists('Tribe__Events__Main') or ! defined('Tribe__Events__Main::VERSION')) {
				add_action('ect_display_admin_notices', array($this, 'Install_ECT_Notice'));
			}
		}
		public function Install_ECT_Notice()
		{

			if (current_user_can('activate_plugins')) {

				printf(
					'<div class="error CTEC_Msz"><p>%1$s %2$s</p></div>',
					esc_html__( 'In order to use this addon, Please first install the latest version of', 'template-events-calendar' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true' ),
						esc_html__( 'The Events Calendar', 'template-events-calendar' )
					)
				);
			}
		}
			
		/*** Admin side shortcode generator style CSS */
		public function ect_tc_css()
		{
			$current_screen = get_current_screen();
			$screen_name    = isset($current_screen->base) ? esc_html($current_screen->base) : '';
			if ($screen_name == 'events-addons_page_tribe_events-events-template-settings') {
				// Use WordPress core code editor (CodeMirror) instead of bundled library.
				wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
				$csf_script = wp_scripts()->query( 'csf' );
				if ( $csf_script && ! in_array( 'code-editor', $csf_script->deps, true ) ) {
					$csf_script->deps[] = 'code-editor';
				}
				wp_enqueue_script('ect-show-pro-setting', ECT_PLUGIN_URL . 'assets/js/ect-show-pro-setting.js', array(), ECT_VERSION, 'all');
				wp_enqueue_script('cpfm-settings-data-share', ECT_PLUGIN_URL . 'admin/cpfm-feedback/js/cpfm-admin-share-data.js', array('jquery'), ECT_VERSION, true);
			}
			wp_enqueue_style('sg-btn-css', plugins_url('assets/css/shortcode-generator.css', __FILE__), array(), ECT_VERSION,);
		}
		/*** Add links in plugin install list */
		public function ect_template_settings_page($links)
		{
			$links[] = '<a style="font-weight:bold" href="' . esc_url(get_admin_url(null, 'admin.php?page=tribe_events-events-template-settings')) . '">Shortcodes Settings</a>';
			// $links[] = '<a  style="font-weight:bold" href="https://eventscalendartemplates.com/" target="_blank">View Demos</a>';
			$plugin_visit_website = 'https://eventscalendaraddons.com/plugin/events-shortcodes-pro/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list';
			$links[]              = '<a  style="font-weight:bold" href="' . esc_url($plugin_visit_website) . '" target="_blank">' . __('Get Pro', 'template-events-calendar') . '</a>';
			return $links;
		}
		/**
		 * Add meta links to the Plugins list page.
		 *
		 * @param array  $links The current action links.
		 * @param string $file  The plugin to see if we are on Event Single Page.
		 *
		 * @return array The modified action links array.
		 */
		public function ect_addMeta_Links($links, $file)
		{
			if (strpos($file, basename(__FILE__))) {
				$ectanchor   = esc_html__('Video Tutorials', 'template-events-calendar');
				$ectvideourl = 'https://eventscalendaraddons.com/go/ect-video-tutorial/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=video_tutorial&utm_content=plugins_list';
				$links[]     = '<a href="' . esc_url($ectvideourl) . '" target="_blank">' . $ectanchor . '</a>';
			}

			return $links;
		}

		// set settings on plugin activation
		public static function activate()
		{
			// Decide the post-activation redirect BEFORE writing options so
			// fresh-install detection sees a pristine state.
			// Fresh install → Getting Started; reactivation → Dashboard.
			require_once ECT_PLUGIN_DIR . 'admin/ect-onboarding/class-ect-onboarding-page.php';
			if ( class_exists( 'ECT_Onboarding_Page' ) ) {
				ECT_Onboarding_Page::maybe_schedule_redirect();
			}

			update_option('ect-v', ECT_VERSION);
			update_option('ect-type', 'FREE');
			update_option('ect-free-installDate', gmdate('Y-m-d h:i:s'));
			update_option('ect-ratingDiv', 'no');			
			if (!get_option('ect-initial-save-version')) {
				add_option('ect-initial-save-version', ECT_VERSION);
			}
			if (!get_option('ect-install-date')) {
				add_option('ect-install-date', gmdate('Y-m-d h:i:s'));
			}
			$ects_options = get_option('ects_options');
			$val = !empty($ects_options['ect_cpfm_feedback_data'])?$ects_options['ect_cpfm_feedback_data']:'';
			if ( ! empty($val) && ! wp_next_scheduled('ect_extra_data_update') ) {
				wp_schedule_event(time(), 'every_30_days', 'ect_extra_data_update');
			}
		}

		public static function deactivate() {
			
			delete_option('settings_migration_status');
			delete_option('ect-v');
			delete_option('ect-type');
			delete_option('ect-free-installDate');
			delete_option('ect-ratingDiv');

			if (wp_next_scheduled('ect_extra_data_update')) {
				wp_clear_scheduled_hook('ect_extra_data_update');
			}
			
		}


		public function ect_rest_url()
		{
?>
			<!-- TinyMCE Shortcode Plugin -->
			<script type='text/javascript'>
				var ectRestUrl = '<?php echo esc_url(get_rest_url(null, '/tribe/events/v1/')); ?>'
			</script>
			<!-- TinyMCE Shortcode Plugin -->
<?php
		}

		/*
			Old settings migration
		*/

		// old titan settings panel fields data
		function get_titan_settings() {
			$new_settings = array();
		
			$titan_raw_data = get_option('ect_options', false);
		
			if ($titan_raw_data === false) {
				return false;
			}
		
			if (is_array($titan_raw_data)) {
				return $titan_raw_data;
			}
		
			$titan_settings = json_decode($titan_raw_data, true);
		
			if (json_last_error() === JSON_ERROR_NONE && is_array($titan_settings)) {
				return $titan_settings;
			}

			if (is_serialized($titan_raw_data)) {
				$titan_settings = @unserialize($titan_raw_data, ['allowed_classes' => false]);
				if (is_array($titan_settings)) {
					foreach ($titan_settings as $key => $val) {
						$new_settings[$key] = is_string($val) ? json_decode($val, true) ?? $val : $val;
					}
					return $new_settings;
				}
			}
		
			return false;
		}
		

		function ect_settings_migration()
		{
			if ( 'done' === get_option( 'settings_migration_status' ) ) {
				return;
			}

			if (version_compare(get_option('ect-v'), '1.8', '>')) {
				return;
			}

			$old_settings = $this->get_titan_settings();
			if ($old_settings == false) {
				return;
			}
			if (is_array($old_settings)) {

				$req_settings = array(
					'font-family',
					'font-size',
					'font-weight',
					'font-style',
					'line-height',
					'letter-spacing',
					'text-transform',
					'color',
					'font-type',
				);
				$webSafeFonts = array(
					'Arial, Helvetica, sans-serif'         => 'Arial',
					'"Arial Black", Gadget, sans-serif'    => 'Arial Black',
					'"Comic Sans MS", cursive, sans-serif' => 'Comic Sans MS',
					'"Courier New", Courier, monospace'    => 'Courier New',
					'Georgia, serif'                       => 'Geogia',
					'Impact, Charcoal, sans-serif'         => 'Impact',
					'"Lucida Console", Monaco, monospace'  => 'Lucida Console',
					'"Lucida Sans Unicode", "Lucida Grande", sans-serif' => 'Lucida Sans Unicode',
					'"Palatino Linotype", "Book Antiqua", Palatino, serif' => 'Palatino Linotype',
					'Tahoma, Geneva, sans-serif'           => 'Tahoma',
					'"Times New Roman", Times, serif'      => 'Times New Roman',
					'"Trebuchet MS", Helvetica, sans-serif' => 'Trebuchet MS',
					'Verdana, Geneva, sans-serif'          => 'Verdana',
				);
				$old_font_arr = array_flip($webSafeFonts);

				$new_settings = array();
				foreach ($old_settings as $key => $field_val) {
					if (is_array($field_val)) {
						foreach ($field_val as $index => $val) {
							if (in_array($index, $req_settings)) {
								if ($index == 'font-type') {
									$index = 'type';
								} elseif ($index == 'font-size') {
									$val = str_replace('px', '', $val);
								} elseif ($index == 'line-height') {
									$val = str_replace('em', '', $val);
								} elseif ($index == 'letter-spacing') {
									$val = str_replace('em', '', $val);
								} elseif ($index == 'font-family') {
									$found = array_search($val, $old_font_arr);
									$val   = $found ? $found : $val;
								}

								$new_settings[$key][$index] = $val;
							}
						}
						$new_settings[$key]['line_height_unit'] = 'em';
						$new_settings[$key]['unit']             = 'px';
						$new_settings[$key]['subset']           = '';
						$new_settings[$key]['text-align']       = '';
						$new_settings[$key]['font-variant']     = '';
					} else {
						$new_settings[$key] = $field_val;
					}
				}
				update_option('ects_options', $new_settings);
				update_option('settings_migration_status', 'done');
				delete_option('ect_options');
			}
		}
	}
}
/*** EventsCalendarTemplates main class - END */


/*** THANKS - CoolPlugins.net ) */
$ect = EventsCalendarTemplates::get_instance();
$ect->register_hooks();
