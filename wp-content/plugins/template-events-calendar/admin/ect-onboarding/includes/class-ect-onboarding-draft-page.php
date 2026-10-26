<?php
/**
 * Creates onboarding draft pages with shortcode or Gutenberg block content.
 *
 * @package Template_Events_Calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Onboarding_Draft_Page' ) ) {

	/**
	 * Builds post content from wizard selections and inserts a draft page.
	 */
	final class ECT_Onboarding_Draft_Page {

		const META_KEY = '_ect_onboarding_draft';

		/**
		 * @param array<string, mixed> $args {
		 *     @type string               $method      shortcode|block
		 *     @type string               $title       Page title.
		 *     @type string               $shortcode   Precomposed shortcode (preferred for shortcode method).
		 *     @type array<string,mixed>  $selections  Wizard selections (includes editor).
		 * }
		 * @return array{postId:int,editUrl:string,viewUrl:string,previewUrl:string}|WP_Error
		 */
		public static function create( $args ) {
			$method     = isset( $args['method'] ) ? sanitize_key( $args['method'] ) : 'shortcode';
			$title      = isset( $args['title'] ) ? sanitize_text_field( $args['title'] ) : '';
			$shortcode  = isset( $args['shortcode'] ) ? self::sanitize_shortcode( $args['shortcode'] ) : '';
			$selections = isset( $args['selections'] ) && is_array( $args['selections'] ) ? $args['selections'] : array();
			$editor     = isset( $selections['editor'] ) ? sanitize_key( (string) $selections['editor'] ) : 'shortcode';

			if ( '' === $title ) {
				$title = __( 'Events', 'template-events-calendar' );
			}

			if ( ! in_array( $method, array( 'shortcode', 'block' ), true ) ) {
				$method = 'shortcode';
			}

			if ( 'block' !== $method && '' === $shortcode ) {
				$shortcode = self::compose_shortcode_from_selections( $selections );
			}

			$content_plan = self::resolve_content_plan( $method, $editor, $selections, $shortcode );
			$content      = $content_plan['content'];

			$existing = (int) get_option( ECT_Onboarding_Page::PAGE_ID_OPTION, 0 );
			$status   = $existing ? get_post_status( $existing ) : false;
			$reuse    = ( $existing && 'draft' === $status && get_post_meta( $existing, self::META_KEY, true ) );

			if ( $reuse ) {
				$post_id = wp_update_post(
					array(
						'ID'           => $existing,
						'post_title'   => $title,
						'post_content' => $content,
					),
					true
				);
			} else {
				$post_id = wp_insert_post(
					array(
						'post_title'   => $title,
						'post_content' => $content,
						'post_status'  => 'draft',
						'post_type'    => 'page',
						'post_author'  => get_current_user_id(),
					),
					true
				);
			}

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			update_option( ECT_Onboarding_Page::PAGE_ID_OPTION, (int) $post_id, false );
			update_post_meta( $post_id, self::META_KEY, 1 );
			update_post_meta( $post_id, self::META_KEY . '_method', $method );
			update_post_meta( $post_id, self::META_KEY . '_editor', $editor );
			update_post_meta( $post_id, self::META_KEY . '_format', $content_plan['format'] );

			$edit_url = get_edit_post_link( $post_id, 'raw' );
			if ( ! $edit_url ) {
				$edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
			}

			if ( 'elementor' === $content_plan['format'] ) {
				self::apply_elementor_shortcode_page( $post_id, $shortcode );
				$edit_url = admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
			} elseif ( in_array( $content_plan['format'], array( 'divi-4', 'divi-5' ), true ) ) {
				self::apply_divi_builder_meta( $post_id );
				$permalink = get_permalink( $post_id );
				if ( $permalink ) {
					$edit_url = add_query_arg(
						array(
							'et_fb'     => '1',
							'PageSpeed' => 'off',
						),
						$permalink
					);
				}
			} elseif ( 'bricks' === $content_plan['format'] ) {
				self::apply_bricks_page( $post_id, $selections, $shortcode );
				$permalink = get_permalink( $post_id );
				if ( $permalink ) {
					$edit_url = add_query_arg( 'bricks', 'run', $permalink );
				}
			} elseif ( 'wpbakery' === $content_plan['format'] ) {
				self::apply_wpbakery_page( $post_id );
				$edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
			}

			$view_url    = get_permalink( $post_id );
			$preview_url = get_preview_post_link( $post_id );

			return array(
				'postId'     => (int) $post_id,
				'editUrl'    => $edit_url,
				'viewUrl'    => $view_url ? $view_url : '',
				'previewUrl' => $preview_url ? $preview_url : ( $view_url ? $view_url : '' ),
				'updated'    => (bool) $reuse,
			);
		}

		/**
		 * Decide post_content + format from method + selected editor.
		 *
		 * Isolated branches (order matters; earlier wins):
		 * - method=block → ebec/event-list
		 * - editor=elementor → Elementor Shortcode widget
		 * - editor=divi → Divi 4 shortcodes or Divi 5 blocks
		 * - editor=bricks → native ect-bricks-events-loop element in _bricks_page_content_2
		 * - editor=wpbakery → VC row/column wrapping the events shortcode
		 * - shortcode on Block Editor → ect/shortcode
		 * - else → plain shortcode
		 *
		 * @param string               $method     shortcode|block
		 * @param string               $editor     Wizard editor value.
		 * @param array<string,mixed>  $selections Wizard selections.
		 * @param string               $shortcode  Composed shortcode.
		 * @return array{format:string,content:string}
		 */
		private static function resolve_content_plan( $method, $editor, $selections, $shortcode ) {
			// Native Gutenberg Events Block (ebec).
			if ( 'block' === $method ) {
				return array(
					'format'  => 'ebec',
					'content' => self::build_ebec_block_content( $selections ),
				);
			}

			// Elementor — Shortcode widget via meta.
			if ( 'elementor' === $editor && self::is_elementor_available() ) {
				return array(
					'format'  => 'elementor',
					'content' => '',
				);
			}

			// Divi — version-specific layout (does not affect other editors).
			if ( 'divi' === $editor && self::is_divi_available() ) {
				$generation = self::get_divi_generation();
				if ( 5 === $generation ) {
					return array(
						'format'  => 'divi-5',
						'content' => self::build_divi5_shortcode_content( $shortcode ),
					);
				}
				if ( 4 === $generation ) {
					return array(
						'format'  => 'divi-4',
						'content' => self::build_divi4_shortcode_content( $shortcode ),
					);
				}
			}

			// Bricks — content lives in post meta (not post_content).
			if ( 'bricks' === $editor && self::is_bricks_available() ) {
				return array(
					'format'  => 'bricks',
					'content' => '',
				);
			}

			// WPBakery — shortcode nested in vc_row / vc_column.
			if ( 'wpbakery' === $editor && self::is_wpbakery_available() ) {
				return array(
					'format'  => 'wpbakery',
					'content' => self::build_wpbakery_content( $shortcode ),
				);
			}

			// Block Editor + Shortcode method:
			// - Step 1 "Shortcode" selected, or
			// - Step 1 "Block" + switched to shortcode display mode
			// → Events Shortcodes generator block (ect/shortcode).
			if (
				self::is_block_editor_available()
				&& in_array( $editor, array( 'shortcode', 'block' ), true )
			) {
				return array(
					'format'  => 'ect-shortcode',
					'content' => self::build_ect_shortcode_block_content( $selections ),
				);
			}

			// Classic / unknown — plain shortcode.
			return array(
				'format'  => 'plain',
				'content' => $shortcode,
			);
		}

		/**
		 * Divi theme version string (child themes resolve to parent Divi).
		 *
		 * @return string Empty when Divi theme is not installed.
		 */
		public static function get_divi_version() {
			$theme = wp_get_theme();

			if ( $theme->parent() && 'divi' === strtolower( (string) $theme->get_template() ) ) {
				return (string) $theme->parent()->get( 'Version' );
			}

			if ( 'divi' === strtolower( (string) $theme->get_stylesheet() ) ) {
				return (string) $theme->get( 'Version' );
			}

			$divi_theme = wp_get_theme( 'Divi' );

			return $divi_theme->exists() ? (string) $divi_theme->get( 'Version' ) : '';
		}

		/**
		 * @return bool
		 */
		private static function is_divi_available() {
			if ( '' !== self::get_divi_version() ) {
				return true;
			}
			return defined( 'ET_BUILDER_VERSION' ) || function_exists( 'et_setup_theme' );
		}

		/**
		 * Major Divi builder generation for storage format.
		 *
		 * @return int 0 = none, 4 = shortcode builder, 5 = block builder
		 */
		private static function get_divi_generation() {
			$version = self::get_divi_version();
			if ( '' === $version && defined( 'ET_BUILDER_VERSION' ) ) {
				$version = (string) ET_BUILDER_VERSION;
			}
			if ( '' === $version ) {
				return 0;
			}

			if ( version_compare( $version, '5.0.0', '>=' ) ) {
				// Prefer native D5 blocks when registered; otherwise D4 shortcodes
				// still render on Divi 5 (with a performance cost).
				if (
					class_exists( '\WP_Block_Type_Registry' )
					&& \WP_Block_Type_Registry::get_instance()->is_registered( 'divi/section' )
				) {
					return 5;
				}
				return 4;
			}

			return 4;
		}

		/**
		 * Enable Divi Builder on the draft page.
		 *
		 * @param int $post_id Page ID.
		 */
		private static function apply_divi_builder_meta( $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return;
			}
			update_post_meta( $post_id, '_et_pb_use_builder', 'on' );
			update_post_meta( $post_id, '_et_pb_page_layout', 'et_full_width_page' );
		}

		/**
		 * Divi 4 layout: Code module with the events shortcode.
		 *
		 * @param string $shortcode Shortcode text.
		 * @return string
		 */
		private static function build_divi4_shortcode_content( $shortcode ) {
			return '[et_pb_section fb_built="1"][et_pb_row][et_pb_column type="4_4" _builder_version="4.0"]'
				. '[et_pb_code _builder_version="4.0"]'
				. $shortcode
				. '[/et_pb_code][/et_pb_column][/et_pb_row][/et_pb_section]';
		}

		/**
		 * Divi 5 layout: section/row/column + text module containing the shortcode.
		 *
		 * @param string $shortcode Shortcode text.
		 * @return string
		 */
		private static function build_divi5_shortcode_content( $shortcode ) {
			$builder_version = self::get_divi_version();
			if ( '' === $builder_version ) {
				$builder_version = defined( 'ET_BUILDER_VERSION' ) ? (string) ET_BUILDER_VERSION : '5.0.0';
			}

			$inner_html = '<p>' . $shortcode . '</p>';
			// Divi 5 expects literal \u003c / \u003e / \u0022 sequences in innerContent.
			$inner_encoded = str_replace(
				array( '<', '>', '"' ),
				array( '\u003c', '\u003e', '\u0022' ),
				$inner_html
			);

			$base_module = array(
				'meta' => array(
					'adminLabel' => array(
						'desktop' => array(
							'value' => 'Events Shortcode',
						),
					),
				),
			);

			$section_attrs = array(
				'module'         => $base_module,
				'builderVersion' => $builder_version,
			);
			$row_attrs     = array(
				'module'         => array(
					'meta' => array(
						'adminLabel' => array(
							'desktop' => array(
								'value' => 'row',
							),
						),
					),
				),
				'builderVersion' => $builder_version,
			);
			$column_attrs  = array(
				'module'         => array(
					'meta' => array(
						'adminLabel' => array(
							'desktop' => array(
								'value' => 'column',
							),
						),
					),
				),
				'type'           => '4_4',
				'builderVersion' => $builder_version,
			);
			$text_attrs    = array(
				'module'         => $base_module,
				'content'        => array(
					'innerContent' => array(
						'desktop' => array(
							'value' => $inner_encoded,
						),
					),
				),
				'builderVersion' => $builder_version,
			);

			$section_json = self::divi5_json( $section_attrs );
			$row_json     = self::divi5_json( $row_attrs );
			$column_json  = self::divi5_json( $column_attrs );
			$text_json    = self::divi5_json( $text_attrs );

			if ( ! $section_json || ! $row_json || ! $column_json || ! $text_json ) {
				// Safe fallback — never break draft creation.
				return $shortcode;
			}

			return "<!-- wp:divi/placeholder -->\n"
				. '<!-- wp:divi/section ' . $section_json . " -->\n"
				. '<!-- wp:divi/row ' . $row_json . " -->\n"
				. '<!-- wp:divi/column ' . $column_json . " -->\n"
				. '<!-- wp:divi/text ' . $text_json . " /-->\n"
				. "<!-- /wp:divi/column -->\n"
				. "<!-- /wp:divi/row -->\n"
				. "<!-- /wp:divi/section -->\n"
				. '<!-- /wp:divi/placeholder -->';
		}

		/**
		 * JSON-encode Divi 5 attrs without double-escaping pre-encoded \uXXXX sequences.
		 *
		 * @param array<string,mixed> $attrs Block attributes.
		 * @return string|false
		 */
		private static function divi5_json( $attrs ) {
			$json = wp_json_encode( $attrs );
			if ( ! is_string( $json ) || '' === $json ) {
				return false;
			}
			// wp_json_encode turns "\u003c" into "\\u003c"; Divi needs the literal escape form.
			return str_replace( '\\\\u', '\\u', $json );
		}

		/**
		 * @return bool
		 */
		private static function is_bricks_available() {
			if ( defined( 'BRICKS_VERSION' ) || function_exists( 'bricks_is_builder' ) ) {
				return true;
			}
			$theme = wp_get_theme();
			$slug  = strtolower( (string) $theme->get_template() );
			return ( 'bricks' === $slug );
		}

		/**
		 * @return bool
		 */
		private static function is_wpbakery_available() {
			return defined( 'WPB_VC_VERSION' ) || class_exists( 'Vc_Manager' );
		}

		/**
		 * Wrap the events shortcode in a minimal WPBakery row/column.
		 *
		 * The free plugin registers the `events-calendar-templates` element
		 * via vc_map; nesting that shortcode inside vc_row/vc_column lets
		 * WPBakery recognize and edit it as the Events Calendar module.
		 *
		 * @param string $shortcode Shortcode text.
		 * @return string
		 */
		private static function build_wpbakery_content( $shortcode ) {
			$shortcode = trim( (string) $shortcode );
			if ( '' === $shortcode ) {
				return '';
			}
			return '[vc_row][vc_column width="1/1"]' . $shortcode . '[/vc_column][/vc_row]';
		}

		/**
		 * Mark the page as edited with WPBakery backend editor.
		 *
		 * @param int $post_id Page ID.
		 */
		private static function apply_wpbakery_page( $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return;
			}
			update_post_meta( $post_id, '_wpb_vc_js_status', 'true' );
		}

		/**
		 * Persist Bricks layout: section → container → Events Widget element.
		 *
		 * Storage (Bricks Academy):
		 * - `_bricks_page_content_2` — flat serialized element array
		 * - `_bricks_editor_mode` = bricks — render with Bricks on the frontend
		 *
		 * @param int                  $post_id     Page ID.
		 * @param array<string,mixed>  $selections  Wizard selections.
		 * @param string               $shortcode   Fallback shortcode when widget unavailable.
		 */
		private static function apply_bricks_page( $post_id, $selections, $shortcode ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return;
			}

			$content_key = defined( 'BRICKS_DB_PAGE_CONTENT' ) ? BRICKS_DB_PAGE_CONTENT : '_bricks_page_content_2';
			$editor_key  = defined( 'BRICKS_DB_EDITOR_MODE' ) ? BRICKS_DB_EDITOR_MODE : '_bricks_editor_mode';

			$elements = self::build_bricks_events_elements( $selections );
			if ( empty( $elements ) ) {
				if ( '' === $shortcode ) {
					return;
				}
				$elements = self::build_bricks_shortcode_elements( $shortcode );
			}
			if ( empty( $elements ) ) {
				return;
			}

			update_post_meta( $post_id, $content_key, $elements );
			update_post_meta( $post_id, $editor_key, 'bricks' );
		}

		/**
		 * Build Bricks page content with the native Events Widget element.
		 *
		 * @param array<string,mixed> $selections Wizard selections.
		 * @return array<int,array<string,mixed>>
		 */
		private static function build_bricks_events_elements( $selections ) {
			if ( ! self::is_bricks_available() ) {
				return array();
			}

			$settings = self::map_selections_to_bricks_settings( $selections );
			if ( empty( $settings ) ) {
				return array();
			}

			$section_id = self::bricks_element_id();
			$container_id = self::bricks_element_id();
			$widget_id    = self::bricks_element_id();

			return array(
				array(
					'id'       => $section_id,
					'name'     => 'section',
					'parent'   => 0,
					'children' => array( $container_id ),
					'settings' => array(),
				),
				array(
					'id'       => $container_id,
					'name'     => 'container',
					'parent'   => $section_id,
					'children' => array( $widget_id ),
					'settings' => array(),
				),
				array(
					'id'       => $widget_id,
					'name'     => 'ect-bricks-events-loop',
					'parent'   => $container_id,
					'children' => array(),
					'settings' => $settings,
				),
			);
		}

		/**
		 * Map wizard selections to Events Widget (Bricks) element settings.
		 *
		 * @param array<string,mixed> $selections Wizard selections.
		 * @return array<string,mixed>
		 */
		private static function map_selections_to_bricks_settings( $selections ) {
			$layout_value = self::selection_value( $selections, 'layout', 'list' );
			$layout_map   = array(
				'list'     => 'list',
				'grid'     => 'grid',
				'carousel' => 'list',
				'default'  => 'list',
			);
			$layout_template = isset( $layout_map[ $layout_value ] ) ? $layout_map[ $layout_value ] : 'list';

			$style_value = self::selection_value( $selections, 'style', 'style-1' );
			$list_item_style = in_array( $style_value, array( 'style-1', 'style-2' ), true ) ? $style_value : 'style-1';
			if ( 'grid' === $layout_template ) {
				$list_item_style = 'style-1';
			}

			$time_value = self::selection_value( $selections, 'time', 'upcoming' );
			$time_map   = array(
				'upcoming' => 'future',
				'past'     => 'past',
				'both'     => 'all',
			);
			$event_type = isset( $time_map[ $time_value ] ) ? $time_map[ $time_value ] : 'future';

			$category_raw = isset( $selections['category'] ) ? (string) $selections['category'] : 'all';
			$categories   = array_values(
				array_filter(
					array_map( 'sanitize_text_field', explode( ',', $category_raw ) )
				)
			);

			$settings = array(
				'layout_template' => $layout_template,
				'list_item_style' => $list_item_style,
				'event_type'      => $event_type,
				'event_time_mode' => 'all',
				'posts_per_page'  => 10,
				'order'           => 'ASC',
				'event_featured'  => ( 'yes' === self::selection_value( $selections, 'featured', 'no' ) ) ? 'featured' : 'all',
			);

			if ( ! empty( $categories ) && ! in_array( 'all', $categories, true ) ) {
				$settings['event_categories'] = $categories;
			}

			foreach ( self::bricks_default_layout_parts() as $parts_key => $rows ) {
				$settings[ $parts_key ] = $rows;
			}

			return $settings;
		}

		/**
		 * Default Elements stacks for List Style 1 and 2 so switching Style
		 * in Bricks still has repeater rows.
		 *
		 * @return array<string,array<int,array<string,mixed>>>
		 */
		private static function bricks_default_layout_parts() {
			if ( class_exists( 'ECT_Bricks_Plugin', false ) ) {
				ECT_Bricks_Plugin::ect_bricks_load_settings_dependencies();
			}
			if ( ! class_exists( 'ECT_Bricks_Settings_Normalizer', false ) ) {
				return array();
			}

			return array_filter(
				array(
					'parts_style1' => ECT_Bricks_Settings_Normalizer::ect_bricks_get_default_list_parts( 'style-1' ),
					'parts_style2' => ECT_Bricks_Settings_Normalizer::ect_bricks_get_default_list_parts( 'style-2' ),
				)
			);
		}

		/**
		 * Build a minimal Bricks content-area element list with a Shortcode element.
		 *
		 * Legacy fallback when the native Events Widget cannot be seeded.
		 *
		 * @param string $shortcode Shortcode text.
		 * @return array<int,array<string,mixed>>
		 */
		private static function build_bricks_shortcode_elements( $shortcode ) {
			$section_id   = self::bricks_element_id();
			$container_id = self::bricks_element_id();
			$shortcode_id = self::bricks_element_id();

			return array(
				array(
					'id'       => $section_id,
					'name'     => 'section',
					'parent'   => 0,
					'children' => array( $container_id ),
					'settings' => array(),
				),
				array(
					'id'       => $container_id,
					'name'     => 'container',
					'parent'   => $section_id,
					'children' => array( $shortcode_id ),
					'settings' => array(),
				),
				array(
					'id'       => $shortcode_id,
					'name'     => 'shortcode',
					'parent'   => $container_id,
					'children' => array(),
					'settings' => array(
						'shortcode' => $shortcode,
					),
				),
			);
		}

		/**
		 * Bricks element IDs are 6-character lowercase alphanumeric strings.
		 *
		 * @return string
		 */
		private static function bricks_element_id() {
			return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 6 );
		}

		/**
		 * @return bool
		 */
		private static function is_elementor_available() {
			return class_exists( '\Elementor\Plugin' );
		}

		/**
		 * @return bool
		 */
		private static function is_classic_editor_active() {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			return is_plugin_active( 'classic-editor/classic-editor.php' );
		}

		/**
		 * @return bool
		 */
		private static function is_block_editor_available() {
			return ! self::is_classic_editor_active();
		}

		/**
		 * Mark page as Elementor-built and insert a Shortcode widget.
		 *
		 * @param int    $post_id   Page ID.
		 * @param string $shortcode Shortcode text.
		 */
		private static function apply_elementor_shortcode_page( $post_id, $shortcode ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 || '' === $shortcode ) {
				return;
			}

			$section_id = self::elementor_id();
			$column_id  = self::elementor_id();
			$widget_id  = self::elementor_id();

			$elements = array(
				array(
					'id'       => $section_id,
					'elType'   => 'section',
					'settings' => array(),
					'elements' => array(
						array(
							'id'       => $column_id,
							'elType'   => 'column',
							'settings' => array(
								'_column_size' => 100,
							),
							'elements' => array(
								array(
									'id'         => $widget_id,
									'elType'     => 'widget',
									'widgetType' => 'shortcode',
									'settings'   => array(
										'shortcode' => $shortcode,
									),
									'elements'   => array(),
								),
							),
							'isInner'  => false,
						),
					),
					'isInner'  => false,
				),
			);

			$json = wp_json_encode( $elements );
			if ( ! is_string( $json ) ) {
				return;
			}

			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_template_type', 'wp-page' );
			update_post_meta( $post_id, '_elementor_data', wp_slash( $json ) );

			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
			}

			delete_post_meta( $post_id, '_elementor_css' );
		}

		/**
		 * Elementor element IDs are 7-char hex strings.
		 *
		 * @return string
		 */
		private static function elementor_id() {
			return substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 7 );
		}

		/**
		 * Build Events Shortcodes generator block (ect/shortcode).
		 *
		 * @param array<string,mixed> $selections Wizard selections.
		 * @return string
		 */
		private static function build_ect_shortcode_block_content( $selections ) {
			$attrs = self::map_selections_to_ect_attrs( $selections );

			if ( function_exists( 'serialize_block' ) ) {
				return serialize_block(
					array(
						'blockName'    => 'ect/shortcode',
						'attrs'        => $attrs,
						'innerBlocks'  => array(),
						'innerHTML'    => '',
						'innerContent' => array(),
					)
				);
			}

			$json = wp_json_encode( $attrs );
			if ( ! is_string( $json ) || '' === $json ) {
				$json = '{}';
			}
			return '<!-- wp:ect/shortcode ' . $json . ' /-->';
		}

		/**
		 * @param string $shortcode Raw shortcode text.
		 * @return string
		 */
		private static function sanitize_shortcode( $shortcode ) {
			$shortcode = wp_unslash( $shortcode );
			$shortcode = trim( $shortcode );
			// Allow only our shortcode tag.
			if ( ! preg_match( '/^\[events-calendar-templates\b[\s\S]*\]$/i', $shortcode ) ) {
				return '';
			}
			return $shortcode;
		}

		/**
		 * Map wizard selections to shortcode / block attribute bag.
		 *
		 * @param array<string,mixed> $selections Wizard selections.
		 * @return array<string,string>
		 */
		private static function map_selections_to_ect_attrs( $selections ) {
			$category_raw = isset( $selections['category'] ) ? (string) $selections['category'] : 'all';
			$vals         = array_values(
				array_filter(
					array_map( 'sanitize_text_field', explode( ',', $category_raw ) )
				)
			);
			$category     = ! empty( $vals ) ? implode( ',', $vals ) : 'all';

			$time_value = self::selection_value( $selections, 'time', 'upcoming' );
			$time_map   = array(
				'upcoming' => 'future',
				'past'     => 'past',
				'both'     => 'all',
			);
			$time       = isset( $time_map[ $time_value ] ) ? $time_map[ $time_value ] : 'future';

			$date_format_value = isset( $selections['dateFormatValue'] )
				? sanitize_text_field( (string) $selections['dateFormatValue'] )
				: 'default';
			$date_format_map   = array(
				'default' => 'default',
				'md-y'    => 'MD,Y',
				'fd-y'    => 'FD,Y',
				'dm'      => 'DM',
				'dml'     => 'DML',
				'df'      => 'DF',
				'md'      => 'MD',
				'fd'      => 'FD',
				'md-yt'   => 'MD,YT',
				'full'    => 'full',
				'jml'     => 'jMl',
				'd-fy'    => 'd.FY',
				'd-f'     => 'd.F',
				'ldf'     => 'ldF',
				'mdl'     => 'Mdl',
				'd-ml'    => 'd.Ml',
				'dft'     => 'dFT',
			);
			$dateformat        = isset( $date_format_map[ $date_format_value ] )
				? $date_format_map[ $date_format_value ]
				: 'default';

			return array(
				'category'    => $category,
				'template'    => self::selection_value( $selections, 'layout', 'default' ),
				'style'       => self::selection_value( $selections, 'style', 'style-1' ),
				'dateformat'  => $dateformat,
				'limit'       => '10',
				'order'       => 'ASC',
				'hideVenue'   => 'no',
				'time'        => $time,
				'startDate'   => '',
				'endDate'     => '',
				'socialshare' => 'no',
			);
		}

		/**
		 * Build an `ebec/event-list` block (Events Block) from wizard state.
		 *
		 * @param array<string,mixed> $selections Wizard selections.
		 * @return string
		 */
		private static function build_ebec_block_content( $selections ) {
			$category_raw = isset( $selections['category'] ) ? (string) $selections['category'] : 'all';
			$categories   = array_values(
				array_filter(
					array_map( 'sanitize_text_field', explode( ',', $category_raw ) )
				)
			);
			if ( empty( $categories ) ) {
				$categories = array( 'all' );
			}

			$time_value = self::selection_value( $selections, 'time', 'upcoming' );
			$time_map   = array(
				'upcoming' => 'future',
				'past'     => 'past',
				'both'     => 'all',
			);
			$ebec_type  = isset( $time_map[ $time_value ] ) ? $time_map[ $time_value ] : 'future';

			$date_format_value = isset( $selections['dateFormatValue'] )
				? sanitize_text_field( (string) $selections['dateFormatValue'] )
				: 'default';
			$date_format_map   = array(
				'default' => 'default',
				'md-y'    => 'MD,Y',
				'fd-y'    => 'FD,Y',
				'dm'      => 'DM',
				'dml'     => 'DML',
				'df'      => 'DF',
				'md'      => 'MD',
				'fd'      => 'FD',
				'md-yt'   => 'MD,YT',
				'full'    => 'full',
				'jml'     => 'jMl',
				'd-fy'    => 'd.FY',
				'd-f'     => 'd.F',
				'ldf'     => 'ldF',
				'mdl'     => 'Mdl',
				'd-ml'    => 'd.Ml',
				'dft'     => 'dFT',
			);
			$ebec_date = isset( $date_format_map[ $date_format_value ] )
				? $date_format_map[ $date_format_value ]
				: 'MD,YT';

			$layout_raw     = self::selection_value( $selections, 'layout', 'default' );
			$event_layout   = self::wizard_layout_to_ebec_event_layout( $layout_raw );

			$attrs = array(
				'ebec_ev_category'         => $categories,
				'ebec_max_events'          => '10',
				'ebec_block_id'            => 'ect' . wp_generate_password( 8, false, false ),
				'ebec_venue'               => 'no',
				'ebec_display_cate'        => 'yes',
				'ebec_display_desc'        => 'yes',
				'ebec_type'                => $ebec_type,
				'ebec_hide_read_more_link' => 'yes',
				'ebec_date_formats'        => $ebec_date,
				'ebec_order'               => 'ASC',
				'event_layout'             => $event_layout,
			);

			if ( function_exists( 'serialize_block' ) ) {
				return serialize_block(
					array(
						'blockName'    => 'ebec/event-list',
						'attrs'        => $attrs,
						'innerBlocks'  => array(),
						'innerHTML'    => '',
						'innerContent' => array(),
					)
				);
			}

			$json = wp_json_encode( $attrs );
			return '<!-- wp:ebec/event-list ' . $json . ' /-->';
		}

		/**
		 * Map wizard layout slug to ebec/event-list `event_layout` attribute.
		 *
		 * @param string $layout_raw Wizard layout value (shortcode or block slug).
		 * @return string `default` or `minimal`
		 */
		private static function wizard_layout_to_ebec_event_layout( $layout_raw ) {
			if ( 'minimal' === $layout_raw || 'minimal-list' === $layout_raw ) {
				return 'minimal';
			}
			return 'default';
		}

		/**
		 * @param array<string,mixed> $selections Wizard selections.
		 * @return string
		 */
		private static function compose_shortcode_from_selections( $selections ) {
			$attrs = self::map_selections_to_ect_attrs( $selections );

			return sprintf(
				'[events-calendar-templates category="%1$s" template="%2$s" style="%3$s" date_format="%4$s" start_date="" end_date="" limit="10" order="ASC" hide_venue="no" socialshare="no" time="%5$s"]',
				$attrs['category'],
				$attrs['template'],
				$attrs['style'],
				$attrs['dateformat'],
				$attrs['time']
			);
		}

		/**
		 * @param array<string,mixed> $selections Selections bag.
		 * @param string              $key        Selection key.
		 * @param string              $default    Fallback.
		 * @return string
		 */
		private static function selection_value( $selections, $key, $default ) {
			if ( empty( $selections[ $key ] ) ) {
				return $default;
			}
			$item = $selections[ $key ];
			if ( is_array( $item ) && isset( $item['value'] ) ) {
				return sanitize_text_field( (string) $item['value'] );
			}
			if ( is_string( $item ) || is_numeric( $item ) ) {
				return sanitize_text_field( (string) $item );
			}
			return $default;
		}
	}
}
