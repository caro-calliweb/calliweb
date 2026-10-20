<?php
/**
 * Bricks integration bootstrap (ECT Bricks prefix: scripts, elements).
 *
 * @package template-events-calendar
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Plugin', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Plugin {

		/**
		 * Shared layout base (always loaded before a style file).
		 */
		const ECT_BRICKS_LAYOUT_BASE_FILE = 'templates/class-ect-bricks-layout-base.php';

		/**
		 * Per-style layout template files (loaded only for the chosen list style).
		 *
		 * @var array<string,string>
		 */
		const ECT_BRICKS_LAYOUT_STYLE_FILES = array(
			'style-1' => 'templates/list-style-1.php',
			'style-2' => 'templates/list-style-2.php',
		);

		/**
		 * Element-data files shared by markup and styles loaders.
		 *
		 * @var array<int,string>
		 */
		const ECT_BRICKS_SHARED_ELEMENT_DATA_FILES = array(
			'ect-bricks-meta-combo.php',
			'ect-bricks-part-options.php',
		);

		/**
		 * Bricks versions this save preflight was verified against (re-test after Bricks upgrades).
		 */
		const ECT_BRICKS_SAVE_PREFLIGHT_MIN_VERSION = '1.9';

		const ECT_BRICKS_SAVE_PREFLIGHT_MAX_TESTED_VERSION = '2.1';

		/** @var string Bricks builder AJAX nonce action (fail closed if Bricks renames it). */
		const ECT_BRICKS_BUILDER_NONCE_ACTION = 'bricks-nonce-builder';

		public function __construct() {
			$this->register_hooks();
		}

		/**
		 * Register WordPress hooks (kept out of the constructor for clarity and testability).
		 *
		 * @return void
		 */
		private function register_hooks() {
			add_action( 'init', array( $this, 'ect_bricks_register_elements' ), 11 );
			add_action( 'wp_enqueue_scripts', array( $this, 'ect_bricks_enqueue_scripts' ), 25 );
			add_action( 'wp_enqueue_scripts', array( $this, 'ect_bricks_enqueue_builder_panel_assets' ), 30 );
			add_filter( 'bricks/element/settings', array( $this, 'ect_bricks_filter_events_loop_element_settings' ), 10, 2 );
			add_action( 'wp_ajax_bricks_save_post', array( $this, 'ect_bricks_preflight_merge_events_loop_repeaters_on_bricks_save' ), 0 );
			add_action( 'admin_notices', array( $this, 'ect_bricks_maybe_show_save_preflight_compat_notice' ) );
		}

		/**
		 * Sanitize list style slug.
		 *
		 * @param mixed $value Raw list_item_style.
		 * @return string style-1|style-2
		 */
		public static function ect_bricks_sanitize_list_style( $value ) {
			$v = is_string( $value ) ? trim( $value ) : '';
			return in_array( $v, array( 'style-1', 'style-2' ), true ) ? $v : 'style-1';
		}

		/**
		 * Full render stack (query, markup, styles) for widget output.
		 *
		 * Controls are builder-only — use {@see self::ect_bricks_load_builder_dependencies()}.
		 * Layout templates are loaded separately via {@see self::ect_bricks_load_layouts()}.
		 * Idempotent via require_once.
		 *
		 * @return void
		 */
		public static function ect_bricks_load_render_dependencies() {
			foreach ( array( 'includes/query.php', 'includes/markup/markup.php', 'includes/styles/styles.php' ) as $relative_path ) {
				self::ect_bricks_require_file( $relative_path );
			}
		}

		/**
		 * Builder control stack only (styles + list defaults + control registrars).
		 *
		 * Does not load list-style-*.php — repeater defaults come from
		 * {@see ECT_Bricks_List_Defaults}. Render loads only the chosen layout.
		 *
		 * @return void
		 */
		public static function ect_bricks_load_builder_dependencies() {
			self::ect_bricks_require_file( 'includes/styles/styles.php' );
			self::ect_bricks_require_file( 'includes/markup/ect-bricks-value-utils.php' );
			self::ect_bricks_require_file( 'templates/ect-bricks-list-defaults.php' );
			self::ect_bricks_load_controls_dependencies();
		}

		/**
		 * Builder control registrars (not needed for front-end render).
		 *
		 * Prefer {@see self::ect_bricks_load_builder_dependencies()} from set_controls().
		 *
		 * @return void
		 */
		public static function ect_bricks_load_controls_dependencies() {
			self::ect_bricks_require_file( 'includes/controls/controls.php' );
		}

		/**
		 * Settings normalizer stack for Bricks save/filter (no render markup).
		 *
		 * Loads shared element data + value-utils + cost + settings only.
		 * Idempotent; skips work when the full markup stack already loaded them.
		 *
		 * @return void
		 */
		public static function ect_bricks_load_settings_dependencies() {
			if ( class_exists( 'ECT_Bricks_Settings_Normalizer', false ) ) {
				return;
			}
			self::ect_bricks_require_shared_element_data();
			self::ect_bricks_require_files(
				ECT_BRICKS_DIR . 'includes/markup/',
				array(
					'ect-bricks-value-utils.php',
					'ect-bricks-cost.php',
					'ect-bricks-settings.php',
				)
			);
		}

		/**
		 * @param string $relative_path Path relative to ECT_BRICKS_DIR.
		 * @return void
		 */
		private static function ect_bricks_require_file( $relative_path ) {
			require_once ECT_BRICKS_DIR . $relative_path;
		}

		/**
		 * @param string            $dir   Absolute directory path (trailing slash optional).
		 * @param array<int,string> $files Basenames to require.
		 * @return void
		 */
		public static function ect_bricks_require_files( $dir, array $files ) {
			$dir = trailingslashit( (string) $dir );
			foreach ( $files as $file ) {
				require_once $dir . $file;
			}
		}

		/**
		 * Load shared style data files used by both markup and styles facades.
		 *
		 * @return void
		 */
		public static function ect_bricks_require_shared_element_data() {
			self::ect_bricks_require_files(
				ECT_BRICKS_DIR . 'includes/styles/',
				self::ECT_BRICKS_SHARED_ELEMENT_DATA_FILES
			);
		}

		/**
		 * Load layout template files on demand (never globally).
		 *
		 * Pass a list style (`style-1`|`style-2`) to load only that layout.
		 * Null/empty loads shared defaults + base only (not every style file).
		 * Idempotent via require_once.
		 *
		 * @param string|null $list_item_style style-1|style-2|null
		 * @return void
		 */
		public static function ect_bricks_load_layouts( $list_item_style = null ) {
			self::ect_bricks_require_file( 'templates/ect-bricks-list-defaults.php' );
			self::ect_bricks_require_file( self::ECT_BRICKS_LAYOUT_BASE_FILE );

			if ( null === $list_item_style || '' === $list_item_style ) {
				foreach ( self::ECT_BRICKS_LAYOUT_STYLE_FILES as $relative_path ) {
					self::ect_bricks_require_file( $relative_path );
				}
				return;
			}

			$style = self::ect_bricks_sanitize_list_style( $list_item_style );
			if ( isset( self::ECT_BRICKS_LAYOUT_STYLE_FILES[ $style ] ) ) {
				self::ect_bricks_require_file( self::ECT_BRICKS_LAYOUT_STYLE_FILES[ $style ] );
			}
		}

		/**
		 * Whether a layout-specific parts repeater is the one currently selected in the UI.
		 *
		 * @param string $parts_repeater_key parts_style1|parts_style2
		 * @param string $layout_template    list
		 * @param string $list_item_style    style-1|style-2
		 * @return bool
		 */
		private static function ect_bricks_is_active_parts_repeater( $parts_repeater_key, $layout_template, $list_item_style ) {
			$map = array(
				'parts_style1' => 'style-1',
				'parts_style2' => 'style-2',
			);
			return isset( $map[ $parts_repeater_key ] )
				&& 'list' === $layout_template
				&& $map[ $parts_repeater_key ] === $list_item_style;
		}

		/**
		 * Before Bricks reads $_POST content, merge inactive layout repeaters from the last saved data.
		 * Hidden `required` repeaters are often sent empty/omitted when saving while another template is selected,
		 * which cleared inactive `parts_style*` values in post meta after refresh.
		 *
		 * Bricks save flow (verified against Bricks Ajax::save_post): content/header/footer JSON is decoded
		 * from $_POST after this priority-0 preflight. Mutating $_POST here is intentional — the documented
		 * filter `bricks/security_check_before_save/new_elements` is skipped for users who can execute code
		 * (early return in Helpers::security_check_elements_before_save), so it cannot replace this path.
		 * Re-verify on Bricks upgrades if save_post stops reading those POST keys.
		 *
		 * @return void
		 */
		public function ect_bricks_preflight_merge_events_loop_repeaters_on_bricks_save() {
			if ( ! $this->ect_bricks_is_bricks_save_environment() ) {
				return;
			}

			if ( ! $this->ect_bricks_verify_bricks_save_preflight_compat() ) {
				return;
			}

			if ( false === check_ajax_referer( self::ECT_BRICKS_BUILDER_NONCE_ACTION, 'nonce', false ) ) {
				$this->ect_bricks_log_save_preflight_issue(
					'Bricks save preflight skipped: nonce check failed (action "' . self::ECT_BRICKS_BUILDER_NONCE_ACTION . '"). Inactive parts_style* repeater data may not be preserved on save.'
				);
				return;
			}

			if ( ! is_scalar( $_POST['postId'] ) ) {
				return;
			}
			$post_id = absint( wp_unslash( $_POST['postId'] ) );
			if ( $post_id < 1 ) {
				return;
			}
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			self::ect_bricks_load_settings_dependencies();

			$has_area_payload = false;
			foreach ( array( 'content', 'header', 'footer' ) as $area ) {
				$area_raw = isset( $_POST[ $area ] ) ? wp_unslash( $_POST[ $area ] ) : '';//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( ! is_string( $area_raw ) || $area_raw === '' ) {
					continue;
				}
				$has_area_payload = true;
				// Bricks element-tree JSON; decoded via Bricks\Ajax::decode() below.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$posted_json = $area_raw;
				$merged      = $this->ect_bricks_merge_events_loop_repeaters_into_posted_area( $posted_json, $post_id, $area );
				if ( is_string( $merged ) ) {
					// Required: Bricks Ajax::save_post decodes content/header/footer from $_POST after this hook.
					$_POST[ $area ] = wp_slash( $merged );
				}
			}

			if ( ! $has_area_payload ) {
				$this->ect_bricks_log_save_preflight_issue(
					'Bricks save preflight: no content/header/footer POST payloads found. Bricks may have changed its save API; inactive parts_style* data may be lost.'
				);
			}
		}

		/**
		 * Whether required Bricks classes exist for the save preflight hook.
		 *
		 * @return bool
		 */
		private function ect_bricks_is_bricks_save_environment() {
			return isset( $_POST['postId'] ) && class_exists( '\Bricks\Ajax' ) && class_exists( '\Bricks\Database' );
		}

		/**
		 * Warn when Bricks is outside the tested version range for the save preflight.
		 *
		 * @return bool False when preflight must not run (unsupported Bricks version).
		 */
		private function ect_bricks_verify_bricks_save_preflight_compat() {
			if ( ! defined( 'BRICKS_VERSION' ) ) {
				$this->ect_bricks_log_save_preflight_issue(
					'Bricks save preflight: BRICKS_VERSION is undefined; compatibility cannot be verified.'
				);
				return true;
			}

			$version = (string) BRICKS_VERSION;
			if ( version_compare( $version, self::ECT_BRICKS_SAVE_PREFLIGHT_MIN_VERSION, '<' ) ) {
				$this->ect_bricks_log_save_preflight_issue(
					sprintf(
						'Bricks save preflight skipped: Bricks %1$s is below the supported minimum %2$s.',
						$version,
						self::ECT_BRICKS_SAVE_PREFLIGHT_MIN_VERSION
					)
				);
				return false;
			}

			if ( version_compare( $version, self::ECT_BRICKS_SAVE_PREFLIGHT_MAX_TESTED_VERSION, '>' ) ) {
				$message = sprintf(
					'Bricks save preflight: Bricks %1$s is newer than the last tested version %2$s. Re-verify the Bricks save flow and bump ECT_BRICKS_SAVE_PREFLIGHT_MAX_TESTED_VERSION if still compatible.',
					$version,
					self::ECT_BRICKS_SAVE_PREFLIGHT_MAX_TESTED_VERSION
				);
				$this->ect_bricks_log_save_preflight_issue( $message );
				set_transient( 'ect_bricks_save_preflight_compat_warn', $message, DAY_IN_SECONDS );
			}

			return true;
		}

		/**
		 * @param string $message Debug message.
		 * @return void
		 */
		private function ect_bricks_log_save_preflight_issue( $message ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[ECT Bricks] ' . $message );
			}
		}

		/**
		 * Surface save-preflight compatibility warnings to admins (set during Bricks save).
		 *
		 * @return void
		 */
		public function ect_bricks_maybe_show_save_preflight_compat_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$message = get_transient( 'ect_bricks_save_preflight_compat_warn' );
			if ( ! is_string( $message ) || $message === '' ) {
				return;
			}
			delete_transient( 'ect_bricks_save_preflight_compat_warn' );
			echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
		}

		/**
		 * @param string $posted_json Raw POST JSON for a Bricks area.
		 * @param int    $post_id     Post ID being saved.
		 * @param string $area        content|header|footer
		 * @return string|null        New JSON string, or null to keep original.
		 */
		private function ect_bricks_merge_events_loop_repeaters_into_posted_area( $posted_json, $post_id, $area ) {
			$new_elements = \Bricks\Ajax::decode( $posted_json );
			if ( ! is_array( $new_elements ) || $new_elements === array() ) {
				return null;
			}

			$meta_key     = \Bricks\Database::get_bricks_data_key( $area );
			$old_elements = get_post_meta( $post_id, $meta_key, true );
			if ( ! is_array( $old_elements ) || $old_elements === array() ) {
				return null;
			}

			$old_indexed = $this->ect_bricks_index_bricks_elements_by_id( $old_elements );
			if ( $old_indexed === array() ) {
				return null;
			}

			$merged = $this->ect_bricks_apply_inactive_part_repeater_preservation( $new_elements, $old_indexed );
			$json   = wp_json_encode( $merged );
			return is_string( $json ) ? $json : null;
		}

		/**
		 * @param array<int,array<string,mixed>> $elements
		 * @return array<string,array<string,mixed>>
		 */
		private function ect_bricks_index_bricks_elements_by_id( array $elements ) {
			$elements_by_id = array();
			foreach ( $elements as $element ) {
				if ( ! is_array( $element ) || empty( $element['id'] ) ) {
					continue;
				}
				$elements_by_id[ (string) $element['id'] ] = $element;
			}
			return $elements_by_id;
		}

		/**
		 * @param array<int,array<string,mixed>>    $new_elements
		 * @param array<string,array<string,mixed>> $old_elements_indexed
		 * @return array<int,array<string,mixed>>
		 */
		private function ect_bricks_apply_inactive_part_repeater_preservation( array $new_elements, array $old_elements_indexed ) {
			foreach ( $new_elements as $i => $element ) {
				if ( ! is_array( $element ) ) {
					continue;
				}
				if ( ( $element['name'] ?? '' ) !== 'ect-bricks-events-loop' || empty( $element['id'] ) ) {
					continue;
				}
				$id = (string) $element['id'];
				if ( ! isset( $old_elements_indexed[ $id ] ) || ! is_array( $old_elements_indexed[ $id ] ) ) {
					continue;
				}
				$old_settings = $old_elements_indexed[ $id ]['settings'] ?? array();
				if ( ! is_array( $old_settings ) ) {
					continue;
				}
				if ( ! isset( $new_elements[ $i ]['settings'] ) || ! is_array( $new_elements[ $i ]['settings'] ) ) {
					$new_elements[ $i ]['settings'] = array();
				}
				$new_settings = &$new_elements[ $i ]['settings'];

				$layout          = \ECT_Bricks_Settings_Normalizer::ect_bricks_sanitize_layout_template( $new_settings );
				$layout_template = $layout['template'];
				$list_item_style = $layout['item_chrome'];

				foreach ( array( 'parts_style1', 'parts_style2' ) as $parts_repeater_key ) {
					if ( self::ect_bricks_is_active_parts_repeater( $parts_repeater_key, $layout_template, $list_item_style ) ) {
						continue;
					}

					$posted_parts       = $new_settings[ $parts_repeater_key ] ?? null;
					$posted_parts_empty = ! is_array( $posted_parts ) || \ECT_Bricks_Settings_Normalizer::ect_bricks_parts_is_empty( $posted_parts );
					if ( ! $posted_parts_empty ) {
						continue;
					}
					if ( ! isset( $old_settings[ $parts_repeater_key ] ) || ! is_array( $old_settings[ $parts_repeater_key ] ) ) {
						continue;
					}
					if ( \ECT_Bricks_Settings_Normalizer::ect_bricks_parts_is_empty( $old_settings[ $parts_repeater_key ] ) ) {
						continue;
					}
					$new_settings[ $parts_repeater_key ] = $old_settings[ $parts_repeater_key ];
				}
				unset( $new_settings );
			}

			return $new_elements;
		}

		/**
		 * Coerce layout-specific repeaters when another layout's row stack was left on disk
		 * (e.g. Grid defaults still stored on `parts_style1` after switching to List Style 1).
		 * Empty repeaters are left unchanged so {@see \ECT_Bricks_Markup::ect_bricks_resolve_parts()}
		 * can still fall back to legacy `parts`.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @param \Bricks\Element     $element  Bricks element instance.
		 * @return array<string,mixed>
		 */
		public function ect_bricks_filter_events_loop_element_settings( $settings, $element ) {
			if ( ! is_array( $settings ) || ! is_object( $element ) ) {
				return $settings;
			}
			if ( ! isset( $element->name ) || $element->name !== 'ect-bricks-events-loop' ) {
				return $settings;
			}

			self::ect_bricks_load_settings_dependencies();

			return \ECT_Bricks_Settings_Normalizer::ect_bricks_norm_widget_settings( $settings );//phpcs ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		/**
		 * Register custom elements
		 */
		public function ect_bricks_register_elements() {
			if ( ! class_exists( '\Bricks\Elements' ) || ! class_exists( '\Bricks\Element' ) ) {
				return;
			}

			if ( isset( \Bricks\Elements::$elements['ect-bricks-events-loop'] ) ) {
				return;
			}

			$file  = ECT_BRICKS_DIR . 'includes/class-ect-bricks-widget.php';
			$class = \ECT\Bricks\Element_ECT_Bricks_Events_Widget::class;

			if ( ! is_readable( $file ) ) {
				return;
			}

			\Bricks\Elements::register_element( $file, 'ect-bricks-events-loop', $class );
		}

		/**
		 * Register widget styles globally; enqueue only when the element renders
		 * ({@see ECT_Bricks_Widget::enqueue_scripts()}), like ECT loads CSS per shortcode.
		 */
		public function ect_bricks_enqueue_scripts() {
			self::ect_bricks_register_events_widget_styles();
		}

		/**
		 * Builder panel: Grid (PRO only) click-through, plus Cool Timeline-style
		 * gold group icons and a Style Settings heading.
		 *
		 * @return void
		 */
		public function ect_bricks_enqueue_builder_panel_assets() {
			if ( ! function_exists( 'bricks_is_builder_main' ) || ! bricks_is_builder_main() ) {
				return;
			}

			self::ect_bricks_register_events_widget_styles();
			self::ect_bricks_load_controls_dependencies();
			wp_enqueue_style( 'ect-bricks-events-widget-base' );

			$css_path = ECT_BRICKS_DIR . 'assets/css/ect-bricks-builder-panel.css';
			$js_path  = ECT_BRICKS_DIR . 'assets/js/ect-bricks-builder-panel.js';
			$css_ver  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $css_path ) )
				? (string) filemtime( $css_path )
				: ECT_VERSION;
			$js_ver   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $js_path ) )
				? (string) filemtime( $js_path )
				: ECT_VERSION;

			wp_enqueue_style(
				'ect-bricks-builder-panel',
				ECT_BRICKS_URL . 'assets/css/ect-bricks-builder-panel.css',
				array(),
				$css_ver
			);

			wp_enqueue_script(
				'ect-bricks-builder-panel',
				ECT_BRICKS_URL . 'assets/js/ect-bricks-builder-panel.js',
				array(),
				$js_ver,
				true
			);

			self::ect_bricks_load_settings_dependencies();

			wp_localize_script(
				'ect-bricks-builder-panel',
				'ectBricksBuilderPanel',
				array(
					'styleSettingsLabel' => __( 'Events Shortcodes Style Settings', 'template-events-calendar' ),
					'gridUrl'            => 'https://eventscalendaraddons.com/bricks/grid/?utm_source=ect_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=bricks_grid_editor_panel',
					'upgradeUrl'         => ECT_Bricks_Layout_Controls::ect_bricks_get_pro_url( 'bricks_pro_lock_editor_panel' ),
					'upgradeLabel'       => __( 'Upgrade to Pro', 'template-events-calendar' ),
					'proLockGroups'      => ECT_Bricks_Layout_Controls::ect_bricks_pro_lock_groups(),
					'defaultParts'       => array(
						'parts_style1' => \ECT_Bricks_Settings_Normalizer::ect_bricks_get_default_list_parts( 'style-1' ),
						'parts_style2' => \ECT_Bricks_Settings_Normalizer::ect_bricks_get_default_list_parts( 'style-2' ),
					),
				)
			);
		}

		/**
		 *
		 */
		private static function ect_bricks_events_widget_style_definitions() {
			$base = array( 'ect-bricks-events-widget-base' );
			return array(
				array(
					'handle' => 'ect-bricks-events-widget-base',
					'path'   => 'assets/css/ect-bricks-events-widget-base.css',
					'deps'   => array(),
				),
				array(
					'handle'  => 'ect-bricks-featured-image-shell',
					'path'    => 'assets/css/ect-bricks-featured-image-shell.css',
					'deps'    => $base,
					'feature' => 'featured-image',
				),
				array(
					'handle'   => 'ect-bricks-list-1',
					'path'     => 'assets/css/list-style-1.css',
					'deps'     => $base,
					'for_list' => 'style-1',
				),
				array(
					'handle'   => 'ect-bricks-list-2',
					'path'     => 'assets/css/list-style-2.css',
					'deps'     => $base,
					'for_list' => 'style-2',
				),
			);
		}

		/**
		 * Register front-end CSS for the Events Widget (base + list templates).
		 *
		 * @return void
		 */
		public static function ect_bricks_register_events_widget_styles() {
			foreach ( self::ect_bricks_events_widget_style_definitions() as $style ) {
				$handle        = $style['handle'];
				$relative_path = $style['path'];
				$deps          = $style['deps'];
				if ( wp_style_is( $handle, 'registered' ) ) {
					continue;
				}
				$disk_path = ECT_BRICKS_DIR . $relative_path;
				$version   = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $disk_path ) )
					? (string) filemtime( $disk_path )
					: ECT_VERSION;
				wp_register_style( $handle, ECT_BRICKS_URL . $relative_path, $deps, $version );
			}
		}

		/**
		 * Enqueue Events Widget styles for the active list layout.
		 *
		 * Pass null `$list_item_style` to enqueue both list skins (builder preview).
		 *
		 * @param string|null $list_item_style     style-1|style-2|null
		 * @param bool        $with_featured_image Whether featured-image shell CSS is needed.
		 * @return void
		 */
		public static function ect_bricks_enqueue_events_widget_styles( $list_item_style = null, $with_featured_image = true ) {
			if ( ! wp_style_is( 'ect-bricks-events-widget-base', 'registered' ) ) {
				self::ect_bricks_register_events_widget_styles();
			}

			$style = ( null === $list_item_style || '' === $list_item_style )
				? null
				: self::ect_bricks_sanitize_list_style( $list_item_style );

			foreach ( self::ect_bricks_events_widget_style_definitions() as $definition ) {
				$handle   = $definition['handle'];
				$for_list = $definition['for_list'] ?? null;
				$feature  = $definition['feature'] ?? null;

				if ( null !== $style && null !== $for_list && $for_list !== $style ) {
					continue;
				}
				if ( 'featured-image' === $feature && ! $with_featured_image ) {
					continue;
				}
				if ( ! wp_style_is( $handle, 'enqueued' ) ) {
					wp_enqueue_style( $handle );
				}
			}
		}
	}

}
