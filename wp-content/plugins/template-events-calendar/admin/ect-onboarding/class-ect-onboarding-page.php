<?php
/**
 * ECT onboarding wizard admin page.
 *
 * @package Template_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Onboarding_Page' ) ) {

	/**
	 * Getting Started wizard for Events Shortcodes & Blocks.
	 */
	final class ECT_Onboarding_Page {

		const PAGE_SLUG          = 'ect-onboarding';
		const LEGACY_PAGE_SLUG   = 'ect-getting-started';
		const COMPLETED_OPTION   = 'ect_onboarding_completed';
		const REDIRECT_TRANSIENT = 'ect_onboarding_redirect';
		const PAGE_ID_OPTION     = 'ect_onboarding_page_id';
		const DATA_OPTION        = 'ect_onboarding_data';
		const WIZARD_TIER        = 'free';

		/**
		 * Editor/builder icon URLs — delegates to the shared vendor image map.
		 *
		 * @return array<string, string>
		 */
		public static function editor_icon_urls() {
			return class_exists( 'ECA_Dashboard_Registry' )
				? ECA_Dashboard_Registry::editor_icon_urls()
				: array();
		}

		/**
		 * Register hooks.
		 */
		public static function init() {
			require_once __DIR__ . '/includes/class-ect-onboarding-draft-page.php';

			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 15 );
			add_action( 'admin_init', array( __CLASS__, 'maybe_redirect' ) );
			add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
			add_action( 'admin_notices', array( __CLASS__, 'suppress_foreign_notices' ), PHP_INT_MIN );
			add_action( 'all_admin_notices', array( __CLASS__, 'suppress_foreign_notices' ), PHP_INT_MIN );
			add_action( 'wp_ajax_ect_onboarding_complete', array( __CLASS__, 'ajax_complete' ) );
			add_action( 'wp_ajax_ect_onboarding_create_page', array( __CLASS__, 'ajax_create_page' ) );
			add_action( 'wp_ajax_ect_onboarding_save_preferences', array( __CLASS__, 'ajax_save_preferences' ) );
			// Core WP.org installer (same pattern as other Cool Plugins dashboards).
			add_action( 'wp_ajax_ect_onboarding_plugin_install', 'wp_ajax_install_plugin' );
			add_action( 'wp_ajax_ect_onboarding_plugin_activate', array( __CLASS__, 'ajax_plugin_activate' ) );
		}

		/**
		 * Schedule a one-shot post-activation redirect.
		 * Call from the activation hook BEFORE writing install options.
		 *
		 * Fresh install  → Getting Started (onboarding).
		 * Reactivation   → shared Events Addons dashboard.
		 *
		 * @return void
		 */
		public static function maybe_schedule_redirect() {
			// Programmatic activate (dashboard / free-wizard AJAX) — UI owns navigation.
			if ( wp_doing_ajax() ) {
				return;
			}

			$is_fresh_install = ( false === get_option( 'ect-install-date', false ) )
				&& ( false === get_option( 'ect-v', false ) );

			$target = $is_fresh_install ? 'onboarding' : 'dashboard';
			// Short TTL: WP reloads admin immediately after Activate, then maybe_redirect()
			// consumes this. If it expires, the user stays on the dashboard (onboarding
			// remains available from the menu).
			set_transient( self::REDIRECT_TRANSIENT, $target, MINUTE_IN_SECONDS );
		}

		/**
		 * Consume the post-activation redirect transient (one shot).
		 *
		 * @return void
		 */
		public static function maybe_redirect() {
			$target = get_transient( self::REDIRECT_TRANSIENT );
			if ( ! $target ) {
				return;
			}
			delete_transient( self::REDIRECT_TRANSIENT );

			if ( wp_doing_ajax() || wp_doing_cron() || is_network_admin() ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- bulk-activation marker only.
			if ( isset( $_GET['activate-multi'] ) ) {
				return;
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Already opening Getting Started (e.g. Continue after Activate Pro) — don't bounce away.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- admin page slug only.
			$page_requested = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( self::PAGE_SLUG === $page_requested || self::LEGACY_PAGE_SLUG === $page_requested ) {
				return;
			}

			// Fresh installs that somehow already finished onboarding still land on the dashboard.
			if ( 'onboarding' === $target && get_option( self::COMPLETED_OPTION ) ) {
				$target = 'dashboard';
			}

			$page = ( 'onboarding' === $target )
				? self::PAGE_SLUG
				: ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' );

			wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
			exit;
		}

		/**
		 * Body class for fullscreen wizard (hides WP admin chrome via CSS).
		 *
		 * @param string $classes Space-separated admin body classes.
		 * @return string
		 */
		public static function admin_body_class( $classes ) {
			if ( ! self::is_onboarding_screen() ) {
				return $classes;
			}

			return $classes . ' ect-onboarding-page';
		}

		/**
		 * Whether the current request is the onboarding admin page.
		 *
		 * @return bool
		 */
		private static function is_onboarding_screen() {
			if ( ! is_admin() ) {
				return false;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

			return self::PAGE_SLUG === $page || self::LEGACY_PAGE_SLUG === $page;
		}

		/**
		 * Strip third-party admin notices on the fullscreen onboarding screen.
		 *
		 * @return void
		 */
		public static function suppress_foreign_notices() {
			if ( ! self::is_onboarding_screen() ) {
				return;
			}

			global $wp_filter;

			$current_hook = current_action();
			if ( empty( $current_hook ) || empty( $wp_filter[ $current_hook ] ) || ! ( $wp_filter[ $current_hook ] instanceof WP_Hook ) ) {
				return;
			}

			foreach ( $wp_filter[ $current_hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'] ?? null;

					if ( is_array( $function ) && isset( $function[0], $function[1] )
						&& $function[0] === __CLASS__ && 'suppress_foreign_notices' === $function[1] ) {
						continue;
					}

					if ( self::is_owned_notice_callback( $function ) ) {
						continue;
					}

					remove_action( $current_hook, $function, $priority );
				}
			}
		}

		/**
		 * @param callable|array|string|null $function Registered notice callback.
		 * @return bool
		 */
		private static function is_owned_notice_callback( $function ) {
			if ( ! is_array( $function ) || ! isset( $function[0] ) ) {
				return false;
			}

			$owner = $function[0];
			$class = is_object( $owner ) ? get_class( $owner ) : (string) $owner;

			return 0 === strpos( $class, 'ECT_' )
				|| 0 === strpos( $class, 'ECA_' )
				|| 0 === strpos( $class, 'EventsCalendarTemplates' );
		}

		/**
		 * Register a hidden admin page (reachable by URL / plugin action link only).
		 */
		public static function register_menu() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$hook = add_submenu_page(
				null,
				__( 'Setup — Events Shortcodes & Blocks', 'template-events-calendar' ),
				__( 'Getting Started', 'template-events-calendar' ),
				'manage_options',
				self::PAGE_SLUG,
				array( __CLASS__, 'render' )
			);
			if ( $hook ) {
				add_action( 'load-' . $hook, array( __CLASS__, 'set_admin_title' ) );
			}

			// Legacy REF slug bookmarked as ect-getting-started.
			add_submenu_page(
				null,
				__( 'Getting Started', 'template-events-calendar' ),
				__( 'Getting Started', 'template-events-calendar' ),
				'manage_options',
				self::LEGACY_PAGE_SLUG,
				array( __CLASS__, 'render_legacy_alias' )
			);
		}

		/**
		 * Give the hidden wizard page a real title before admin-header.php runs
		 * (avoids PHP 8.1+ strip_tags(null) deprecation).
		 *
		 * @return void
		 */
		public static function set_admin_title() {
			$GLOBALS['title'] = __( 'Getting Started', 'template-events-calendar' );
		}

		/**
		 * Redirect legacy slug to the current onboarding page.
		 *
		 * @return void
		 */
		public static function render_legacy_alias() {
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
			exit;
		}

		/**
		 * Render wizard markup.
		 */
		public static function render() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'template-events-calendar' ) );
			}

			$preview = ECA_Dashboard_Environment::wizard_preview_state();
			$markup  = self::get_wizard_markup();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted admin template from plugin package.
			echo $markup;

			unset( $preview );
		}

		/**
		 * @param string $hook_suffix Admin hook.
		 */
		public static function enqueue( $hook_suffix ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen gate only.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( self::PAGE_SLUG !== $page && false === strpos( (string) $hook_suffix, self::PAGE_SLUG ) ) {
				return;
			}

			$base = ECT_PLUGIN_URL . 'admin/ect-onboarding/assets/';
			$ver  = defined( 'ECA_DASHBOARD_VERSION' ) ? ECA_DASHBOARD_VERSION : ECT_VERSION;

			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'ect-onboarding-base', $base . 'css/ect-base.css', array(), $ver );
			wp_enqueue_style( 'ect-onboarding-wizard', $base . 'css/ect-wizard.css', array( 'ect-onboarding-base' ), $ver );
			wp_enqueue_style( 'ect-onboarding-fullscreen', $base . 'css/ect-admin-fullscreen.css', array( 'ect-onboarding-wizard' ), $ver );

			wp_enqueue_script( 'ect-onboarding-wizard', $base . 'js/ect-wizard.js', array(), $ver, true );
			wp_enqueue_script( 'ect-onboarding-logic', $base . 'js/ect-onboarding.js', array( 'ect-onboarding-wizard', 'updates' ), $ver, true );

			$images       = ECT_PLUGIN_URL . 'admin/ect-onboarding/assets/images/';
			$icons        = ECT_PLUGIN_URL . 'admin/eca-dashboard/assets/icons/';
			$editor_icons = self::editor_icon_urls();

			$config = array(
				'slug'               => 'shortcodes-blocks',
				'steps'              => array(
					array( 'id' => 'editor', 'label' => 'Editor' ),
					array( 'id' => 'layout-style', 'label' => 'Layout & Style' ),
					array( 'id' => 'query', 'label' => 'Events Query' ),
					array( 'id' => 'success', 'label' => 'Done' ),
				),
				'defaultTelemetry'   => true,
				'assetBase'          => $images,
				'editorIcons'        => $editor_icons,
				'siblingIconBase'    => $icons,
				'summaryLabels'      => array(
					'editor'     => 'Editor',
					'layout'     => 'Layout',
					'style'      => 'Style',
					'time-range' => 'Time range',
					'color'      => 'Color preset',
				),
			);

			$preview  = ECA_Dashboard_Environment::wizard_preview_state();
			$editors  = ECA_Dashboard_Environment::detect_editors();
			$classic  = ! empty( $editors['classicEditor'] );

			// WordPress: drive preview from live PHP detection (bootstrap_script).
			wp_add_inline_script(
				'ect-onboarding-wizard',
				'window.ECT_WIZARD = ' . wp_json_encode( $config ) . ';' . "\n" . self::bootstrap_script( $preview['preview_state'], $preview['default_editor'], $classic, self::WIZARD_TIER ),
				'before'
			);

			$telemetry = class_exists( 'ECT_Onboarding_Cpfm_Data' )
				? ECT_Onboarding_Cpfm_Data::get_telemetry_localize()
				: array(
					'show'    => true,
					'checked' => true,
					'choice'  => null,
				);

			wp_localize_script(
				'ect-onboarding-logic',
				'ECT_ONBOARDING',
				array(
					'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
					'nonceComplete'    => wp_create_nonce( 'ect_onboarding_complete' ),
					'nonceCreatePage'  => wp_create_nonce( 'ect_onboarding_create_page' ),
					'nonceSavePrefs'   => wp_create_nonce( 'ect_onboarding_save_preferences' ),
					'nonceActivate'    => wp_create_nonce( 'ect_onboarding_plugin' ),
					'nonceInstall'     => wp_create_nonce( 'updates' ),
					'dashboardUrl'     => admin_url( 'admin.php?page=' . ECA_Dashboard_Page::PAGE_SLUG ),
					'settingsUrl'      => admin_url( 'admin.php?page=tribe_events-events-template-settings' ),
					'previewState'     => $preview['preview_state'],
					'defaultEditor'    => $preview['default_editor'],
					'siblings'         => self::get_sibling_addons(),
					'telemetry'        => $telemetry,
					'pro'              => self::get_esb_pro_state(),
					'spb'              => self::get_spb_state(),
					'page'             => self::get_page_state(),
					'i18n'             => array(
						'creating'         => __( 'Creating page…', 'template-events-calendar' ),
						'updating'         => __( 'Updating page…', 'template-events-calendar' ),
						'createDraft'      => __( 'Create Draft Page', 'template-events-calendar' ),
						'updateDraft'      => __( 'Update Draft Page', 'template-events-calendar' ),
						'createNew'        => __( 'Create New Draft', 'template-events-calendar' ),
						'activate'         => __( 'Activate', 'template-events-calendar' ),
						'installActivate'  => __( 'Install & Activate', 'template-events-calendar' ),
						'setup'            => __( 'Setup', 'template-events-calendar' ),
						'spbInstalled'     => __( 'Events Single Page Builder installed — set it up now.', 'template-events-calendar' ),
						'spbActivated'     => __( 'Events Single Page Builder activated — set it up now.', 'template-events-calendar' ),
						'installConfirm'   => array(
							'elementor' => __( 'Events Widgets installed — click Get Started to continue.', 'template-events-calendar' ),
							'divi'      => __( 'Events Modules installed — click Get Started to continue.', 'template-events-calendar' ),
						),
					),
				)
			);
		}

		/**
		 * Apply server-detected preview state before wizard.js boots.
		 * Also clears stale prototype sim localStorage so it cannot fight PHP.
		 *
		 * @param string $preview_state  Wizard preview state slug.
		 * @param string $default_editor Default editor option value.
		 * @param bool   $classic_editor Classic Editor plugin active (orthogonal to preview state).
		 * @param string $wizard_tier    Wizard SKU tier (`free` or `pro`).
		 * @return string
		 */
		private static function bootstrap_script( $preview_state, $default_editor = 'shortcode', $classic_editor = false, $wizard_tier = 'free' ) {
			$ps = esc_js( $preview_state );
			$de = esc_js( $default_editor );
			$ce = $classic_editor ? 'true' : 'false';
			$tier = esc_js( $wizard_tier );
			$script = <<<'JS'
			(function () {
				var previewState = '__PREVIEW_STATE__';
				var defaultEditor = '__DEFAULT_EDITOR__';
				var classicEditor = '__CLASSIC_EDITOR__';
				var tier = '__WIZARD_TIER__';

				document.body.setAttribute('data-preview-state', previewState);
				document.body.setAttribute('data-classic-editor', classicEditor);
				document.body.setAttribute('data-tier', tier);
				document.body.setAttribute('data-editor-selected', defaultEditor);

				document.querySelectorAll('[data-states]').forEach(function (element) {
					var states = (element.dataset.states || '').split(/\s+/).filter(Boolean);
					element.hidden = states.indexOf(previewState) === -1;
				});

				try {
					localStorage.removeItem('ect-preview-state:wizard-shortcodes-blocks');
				} catch (error) {}

				try {
					var slug = window.ECT_WIZARD.slug;
					var storageKey = 'ect:wizard:' + slug + ':state';
					var rawState = localStorage.getItem(storageKey);
					var state;
					var editor;

					if (rawState) {
						state = JSON.parse(rawState);
						editor = state && state.selections && state.selections.editor;
					}

					if (!editor) {
						editor = defaultEditor;
					}
					if (classicEditor === 'true' && (editor === 'block' || editor === 'classic')) {
						editor = 'shortcode';
					}

					var option = document.querySelector('.ect-editor-option[data-value="' + editor + '"]');
					if (!option) {
						editor = defaultEditor;
						option = document.querySelector('.ect-editor-option[data-value="' + editor + '"]');
					}
					if (!option) {
						editor = 'shortcode';
						option = document.querySelector('.ect-editor-option[data-value="shortcode"]');
					}

					if (state && state.selections && state.selections.editor !== editor) {
						state.selections.editor = editor;
						localStorage.setItem(storageKey, JSON.stringify(state));
					}

					document.body.setAttribute('data-editor-selected', editor);
					if (option) {
						document.querySelectorAll('.ect-editor-option.is-selected').forEach(function (selectedOption) {
							selectedOption.classList.remove('is-selected');
						});
						option.classList.add('is-selected');
					}

					var displayMode = localStorage.getItem('ect:wizard:' + slug + ':displayMode');
					if (displayMode) {
						document.body.setAttribute('data-display-mode', displayMode);
					}

					var pageCreated = localStorage.getItem('ect:wizard:' + slug + ':pageCreated');
					if (pageCreated) {
						document.body.setAttribute('data-page-created', 'true');
					}
				} catch (error) {}
			})();
			JS;

			return strtr(
				$script,
				array(
					'__PREVIEW_STATE__'  => $ps,
					'__DEFAULT_EDITOR__' => $de,
					'__CLASSIC_EDITOR__' => $ce,
					'__WIZARD_TIER__'    => $tier,
				)
			);
		}

		/**
		 * Load wizard markup from the PHP view (dynamic URLs / categories).
		 *
		 * @return string
		 */
		private static function get_wizard_markup() {
			$path = ECT_PLUGIN_DIR . 'admin/ect-onboarding/views/wizard-markup.php';
			if ( ! file_exists( $path ) ) {
				return '<p>' . esc_html__( 'Wizard template missing.', 'template-events-calendar' ) . '</p>';
			}

			$preview = class_exists( 'ECA_Dashboard_Environment' )
				? ECA_Dashboard_Environment::wizard_preview_state()
				: array(
					'preview_state'  => 'default',
					'default_editor' => 'shortcode',
				);

			$dashboard_url = admin_url(
				'admin.php?page=' . ( class_exists( 'ECA_Dashboard_Page' ) ? ECA_Dashboard_Page::PAGE_SLUG : 'cool-plugins-events-addon' )
			);
			$icons         = ECT_PLUGIN_URL . 'admin/eca-dashboard/assets/icons/';
			$editor_icons  = self::editor_icon_urls();
			$images_fallback = ECT_PLUGIN_URL . 'admin/ect-onboarding/assets/images/';

			// Ensure every editor key has a URL (view echoes these keys directly).
			$editor_icons = array_merge(
				array(
					'block'     => $images_fallback . 'gutenberg-icon.png',
					'shortcode' => $images_fallback . 'shortcode-icon.png',
					'elementor' => $images_fallback . 'elementor-icon.png',
					'divi'      => $images_fallback . 'divi-icon.png',
					'bricks'    => $images_fallback . 'bricks-icon.png',
					'wpbakery'  => $images_fallback . 'wpbakery-icon.png',
				),
				is_array( $editor_icons ) ? $editor_icons : array()
			);

			$detected_editors = class_exists( 'ECA_Dashboard_Environment' )
				? ECA_Dashboard_Environment::detect_editors()
				: array();
			$category_options = self::render_category_options_html();
			$preview_state    = isset( $preview['preview_state'] ) ? (string) $preview['preview_state'] : 'default';
			$default_editor   = isset( $preview['default_editor'] ) ? (string) $preview['default_editor'] : 'shortcode';
			$classic_editor   = ! empty( $detected_editors['classicEditor'] );
			$show_telemetry   = ! ( class_exists( 'ECT_Onboarding_Cpfm_Data' ) && ! ECT_Onboarding_Cpfm_Data::should_show_telemetry() );
			$wizard_tier      = self::WIZARD_TIER;

			ob_start();
			include $path;
			return (string) ob_get_clean();
		}

		/**
		 * Build multiselect option markup from live tribe_events_cat terms.
		 *
		 * @return string
		 */
		private static function render_category_options_html() {
			$options = array(
				array(
					'value' => 'all',
					'label' => __( 'All events', 'template-events-calendar' ),
				),
			);

			if ( taxonomy_exists( 'tribe_events_cat' ) ) {
				$terms = get_terms(
					array(
						'taxonomy'   => 'tribe_events_cat',
						'hide_empty' => false,
						'orderby'    => 'name',
						'order'      => 'ASC',
					)
				);

				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$options[] = array(
							'value' => $term->slug,
							'label' => $term->name,
						);
					}
				}
			}

			$html = '';
			foreach ( $options as $opt ) {
				$html .= sprintf(
					'<a href="#" role="option" class="ect-multiselect__option" data-value="%1$s" data-multiselect-option>' .
					'<span class="ect-multiselect__check" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>' .
					'<span class="ect-multiselect__label">%2$s</span>' .
					'</a>',
					esc_attr( $opt['value'] ),
					esc_html( $opt['label'] )
				);
			}

			return $html;
		}

		/**
		 * Sibling addon metadata for Step 1 promo (Install / Activate / Open).
		 *
		 * @return array<string, array<string, string>>
		 */
		private static function get_sibling_addons() {
			$icons = ECT_PLUGIN_URL . 'admin/eca-dashboard/assets/icons/';

			return array(
				'elementor' => self::build_sibling_addon(
					'widgets',
					array(
						'addon'      => 'Events Widgets for Elementor',
						'addonShort' => 'Events Widgets',
						'icon'       => $icons . 'events-widgets-icon.svg',
						'desc'       => 'Get drag-drop event widgets directly inside Elementor with our dedicated addon.',
						'openUrl'    => admin_url( 'admin.php?page=' . ECA_Dashboard_Page::PAGE_SLUG ),
					)
				),
				'divi'      => self::build_sibling_addon(
					'divi',
					array(
						'addon'      => 'Events Modules for Divi',
						'addonShort' => 'Events Modules',
						'icon'       => $icons . 'events-calendar-modules-for-divi.svg',
						'desc'       => 'Get native Divi modules for event listings and single event pages with our dedicated addon.',
						'openUrl'    => admin_url( 'admin.php?page=' . ECA_Dashboard_Page::PAGE_SLUG ),
					)
				),
			);
		}

		/**
		 * ESB Pro on-disk state for Upgrade → Activate Pro relabeling.
		 *
		 * @return array{installedInactive: bool, init: string, slug: string, proUrl: string}
		 */
		private static function get_esb_pro_state() {
			$pro_url = 'https://eventscalendaraddons.com/plugin/events-shortcodes-pro/';
			$slug    = 'the-events-calendar-templates-and-shortcode';
			$init    = '';
			$inactive = false;

			if ( class_exists( 'ECA_Addon_Map' ) ) {
				$defs = ECA_Addon_Map::definitions();
				if ( ! empty( $defs['esb']['pro'] ) ) {
					$slug = (string) $defs['esb']['pro'];
				}
				$status   = ECA_Addon_Map::tier_status( 'esb', 'pro' );
				$init     = (string) ECA_Addon_Map::tier_init( 'esb', 'pro' );
				$inactive = ( 'inactive' === $status );
			} else {
				$file = self::find_plugin_basename( $slug );
				if ( $file ) {
					if ( ! function_exists( 'is_plugin_active' ) ) {
						require_once ABSPATH . 'wp-admin/includes/plugin.php';
					}
					$init     = $file;
					$inactive = ! is_plugin_active( $file );
				}
			}

			return array(
				'installedInactive' => $inactive,
				'init'              => $init,
				'slug'              => $slug,
				'proUrl'            => $pro_url,
			);
		}

		/**
		 * Events Single Page Builder state for the finish-step cross-sell.
		 *
		 * @return array{slug: string, state: string, init: string, setupUrl: string}
		 */
		private static function get_spb_state() {
			$defs = class_exists( 'ECA_Addon_Map' ) ? ECA_Addon_Map::definitions() : array();
			$slug = isset( $defs['spb']['free'] ) ? (string) $defs['spb']['free'] : 'event-page-templates-addon-for-the-events-calendar';
			$file = self::find_plugin_basename( $slug );

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$state = 'absent';
			$init  = $file ? $file : ( $slug . '/' . ( isset( $defs['spb']['free_main'] ) ? $defs['spb']['free_main'] : ( $slug . '.php' ) ) );
			if ( $file && is_plugin_active( $file ) ) {
				$state = 'active';
			} elseif ( $file ) {
				$state = 'inactive';
			}

			return array(
				'slug'     => $slug,
				'state'    => $state,
				'init'     => $init,
				'setupUrl' => admin_url( 'edit.php?post_type=epta' ),
			);
		}

		/**
		 * Draft page state so Step 4 can label Create / Update / Create New.
		 *
		 * @return array{exists: bool, isDraft: bool, isPublished: bool, id: int}
		 */
		private static function get_page_state() {
			$id     = (int) get_option( self::PAGE_ID_OPTION, 0 );
			$status = $id ? get_post_status( $id ) : false;

			return array(
				'exists'      => (bool) $status,
				'isDraft'     => ( 'draft' === $status ),
				'isPublished' => ( $status && 'draft' !== $status && 'trash' !== $status ),
				'id'          => $id,
			);
		}

		/**
		 * @param string               $env_key Resolver env key (widgets|divi).
		 * @param array<string,string> $meta    Display metadata.
		 * @return array<string, string>
		 */
		private static function build_sibling_addon( $env_key, $meta ) {
			$defs     = class_exists( 'ECA_Addon_Map' ) ? ECA_Addon_Map::definitions() : array();
			$free_slug = isset( $defs[ $env_key ]['free'] ) ? (string) $defs[ $env_key ]['free'] : '';
			$pro_slug  = isset( $defs[ $env_key ]['pro'] ) ? (string) $defs[ $env_key ]['pro'] : '';
			$free_file = $free_slug ? self::find_plugin_basename( $free_slug ) : '';
			$pro_file  = $pro_slug ? self::find_plugin_basename( $pro_slug ) : '';

			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$status = 'absent';
			$init   = $free_file ? $free_file : ( $free_slug ? $free_slug . '/' . $free_slug . '.php' : '' );

			// Pro counts as already set up — no Install/Activate for free.
			if ( $pro_file && is_plugin_active( $pro_file ) ) {
				$status = 'active';
				$init   = $pro_file;
			} elseif ( $free_file && is_plugin_active( $free_file ) ) {
				$status = 'active';
				$init   = $free_file;
			} elseif ( $free_file ) {
				$status = 'inactive';
				$init   = $free_file;
			} elseif ( $pro_file ) {
				$status = 'inactive';
				$init   = $pro_file;
			}

			return array_merge(
				$meta,
				array(
					'slug'   => $free_slug,
					'init'   => $init,
					'status' => $status,
				)
			);
		}

		/**
		 * @param string $dir_slug Plugin directory slug under wp-content/plugins.
		 * @return string Plugin basename (dir/file.php) or empty.
		 */
		private static function find_plugin_basename( $dir_slug ) {
			$dir = WP_PLUGIN_DIR . '/' . $dir_slug;
			if ( ! is_dir( $dir ) ) {
				return '';
			}

			$candidate = $dir_slug . '/' . $dir_slug . '.php';
			if ( file_exists( WP_PLUGIN_DIR . '/' . $candidate ) ) {
				return $candidate;
			}

			$files = glob( $dir . '/*.php' );
			if ( empty( $files ) ) {
				return '';
			}

			foreach ( $files as $file ) {
				$data = get_file_data( $file, array( 'Name' => 'Plugin Name' ) );
				if ( ! empty( $data['Name'] ) ) {
					return plugin_basename( $file );
				}
			}

			return plugin_basename( $files[0] );
		}

		/**
		 * Allowed sibling plugin basenames for activation (security allowlist).
		 *
		 * @return string[]
		 */
		private static function allowed_sibling_inits() {
			$allowed = array();
			$defs    = class_exists( 'ECA_Addon_Map' ) ? ECA_Addon_Map::definitions() : array();

			foreach ( array( 'widgets', 'divi', 'esb', 'spb' ) as $env_key ) {
				foreach ( array( 'free', 'pro' ) as $tier ) {
					if ( empty( $defs[ $env_key ][ $tier ] ) ) {
						continue;
					}
					$slug = (string) $defs[ $env_key ][ $tier ];
					$file = self::find_plugin_basename( $slug );
					if ( $file ) {
						$allowed[] = $file;
					}
					$allowed[] = $slug . '/' . $slug . '.php';
				}
			}

			foreach ( self::get_sibling_addons() as $addon ) {
				if ( ! empty( $addon['init'] ) ) {
					$allowed[] = $addon['init'];
				}
			}

			return array_values( array_unique( $allowed ) );
		}

		/**
		 * AJAX: activate a sibling addon after install (or when already present).
		 */
		public static function ajax_plugin_activate() {
			check_ajax_referer( 'ect_onboarding_plugin', 'security' );

			if ( ! current_user_can( 'activate_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'template-events-calendar' ) ) );
			}

			if ( empty( $_POST['init'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Plugin file missing.', 'template-events-calendar' ) ) );
			}

			$init = sanitize_text_field( wp_unslash( $_POST['init'] ) );
			if ( ! in_array( $init, self::allowed_sibling_inits(), true ) ) {
				// After a fresh install the real basename may differ slightly —
				// allow only if the directory slug matches a known sibling.
				$slug = dirname( $init );
				$ok   = false;
				foreach ( self::get_sibling_addons() as $addon ) {
					if ( ! empty( $addon['slug'] ) && $addon['slug'] === $slug ) {
						$ok = true;
						break;
					}
				}
				if ( ! $ok ) {
					wp_send_json_error( array( 'message' => __( 'Plugin not allowed.', 'template-events-calendar' ) ) );
				}
			}

			if ( ! function_exists( 'activate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$result = activate_plugin( $init );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			// Sibling activate hooks schedule a one-shot admin redirect
			// (dashboard / Get Started). Suppress it here so the wizard's
			// Setup CTA can land on the product screen (e.g. epta CPT list).
			delete_transient( 'epta_activation_redirect' );
			delete_transient( 'espbp_activation_redirect' );
			delete_transient( self::REDIRECT_TRANSIENT );

			wp_send_json_success(
				array(
					'message'  => __( 'Plugin activated successfully.', 'template-events-calendar' ),
					'siblings' => self::get_sibling_addons(),
				)
			);
		}

		/**
		 * Persist Step-3 telemetry consent + wizard selections.
		 */
		public static function ajax_save_preferences() {
			check_ajax_referer( 'ect_onboarding_save_preferences', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'template-events-calendar' ) ) );
			}

			if ( ! class_exists( 'ECT_Onboarding_Cpfm_Data', false ) ) {
				require_once ECT_PLUGIN_DIR . 'admin/ect-onboarding/includes/class-ect-onboarding-cpfm-data.php';
			}

			$telemetry_raw = isset( $_POST['telemetry'] ) ? sanitize_text_field( wp_unslash( $_POST['telemetry'] ) ) : '0';
			$telemetry     = ( '1' === $telemetry_raw || 'yes' === $telemetry_raw || 'true' === $telemetry_raw );

			$selections = array();
			if ( isset( $_POST['selections'] ) ) {
				$raw = wp_unslash( $_POST['selections'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_string( $raw ) ) {
					$decoded = json_decode( $raw, true );
					if ( is_array( $decoded ) ) {
						$selections = $decoded;
					}
				} elseif ( is_array( $raw ) ) {
					$selections = $raw;
				}
			}

			ECT_Onboarding_Cpfm_Data::save_choice( $telemetry ? 'yes' : 'no' );
			ECT_Onboarding_Cpfm_Data::save_preferences( $telemetry, $selections );

			if ( $telemetry ) {
				// Reuse the existing CPFM opt-in path (settings flag + cron send).
				do_action( 'cpfm_after_opt_in_ect', 'cool_events' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				self::schedule_crons_for_other_plugins();
			}

			wp_send_json_success(
				array(
					'choice'    => $telemetry ? 'yes' : 'no',
					'telemetry' => ECT_Onboarding_Cpfm_Data::get_telemetry_localize(),
				)
			);
		}

		private static function schedule_crons_for_other_plugins() {
			$plugin_files = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$plugin_files = array_merge( $plugin_files, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
			}

			$self_slug = 'template-events-calendar';
			$seen      = array();

			foreach ( $plugin_files as $plugin_file ) {
				$slug = dirname( (string) $plugin_file );
				if ( '.' === $slug || '' === $slug || $self_slug === $slug || isset( $seen[ $slug ] ) ) {
					continue;
				}
				$seen[ $slug ] = true;
				do_action( 'plugin_opt_in_' . $slug );
			}
		}
		/**
		 * Persist wizard completion.
		 */
		public static function ajax_complete() {
			check_ajax_referer( 'ect_onboarding_complete', 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'template-events-calendar' ) ) );
			}
			update_option( self::COMPLETED_OPTION, true, false );

			// Snapshot light wizard choices for opt-in feedback (local only).
			$editor = isset( $_POST['editor'] ) ? sanitize_key( wp_unslash( $_POST['editor'] ) ) : '';
			if ( ! in_array( $editor, array( 'block', 'shortcode', 'elementor', 'divi', 'bricks', 'wpbakery' ), true ) ) {
				$editor = '';
			}
			update_option(
				self::DATA_OPTION,
				array(
					'editor'       => $editor,
					'created_page' => (int) get_option( self::PAGE_ID_OPTION, 0 ) > 0 ? 1 : 0,
					'updated'      => current_time( 'mysql' ),
				),
				false
			);

			wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=' . ECA_Dashboard_Page::PAGE_SLUG ) ) );
		}

		/**
		 * Create a draft page with shortcode or Gutenberg block content.
		 */
		public static function ajax_create_page() {
			check_ajax_referer( 'ect_onboarding_create_page', 'nonce' );
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'template-events-calendar' ) ) );
			}

			$method = isset( $_POST['method'] ) ? sanitize_key( wp_unslash( $_POST['method'] ) ) : 'shortcode';
			$title  = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$shortcode = isset( $_POST['shortcode'] ) ? wp_unslash( $_POST['shortcode'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in draft helper.

			$selections = array();
			if ( isset( $_POST['selections'] ) ) {
				$raw = wp_unslash( $_POST['selections'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( is_string( $raw ) ) {
					$decoded = json_decode( $raw, true );
					if ( is_array( $decoded ) ) {
						$selections = $decoded;
					}
				} elseif ( is_array( $raw ) ) {
					$selections = $raw;
				}
			}

			if ( '' === $title ) {
				$layout_label = '';
				if ( isset( $selections['layout']['label'] ) ) {
					$layout_label = sanitize_text_field( (string) $selections['layout']['label'] );
				}
				$title = $layout_label
					? sprintf(
						/* translators: %s: layout name */
						__( 'Events — %s', 'template-events-calendar' ),
						$layout_label
					)
					: __( 'Events', 'template-events-calendar' );
			}

			$result = ECT_Onboarding_Draft_Page::create(
				array(
					'method'     => $method,
					'title'      => $title,
					'shortcode'  => $shortcode,
					'selections' => $selections,
				)
			);

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			wp_send_json_success( $result );
		}
	}
}
