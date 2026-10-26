<?php
/**
 * Widget settings normalization and parts resolution.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Settings_Normalizer', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Settings_Normalizer {

		/** @var string[] Bricks repeater keys that store layout part rows. */
		private const PARTS_REPEATER_KEYS = array( 'parts_style1', 'parts_style2', 'parts' );

		/** list only; defaults to list. */
		public static function ect_bricks_sanitize_template() {
			return 'list';
		}

		/** style-1|style-2; defaults to style-1. */
		public static function ect_bricks_sanitize_list_style( $value ) {
			return \ECT_Bricks_Plugin::ect_bricks_sanitize_list_style( $value );
		}

		/** Normalized layout_template + list_item_style pair. */
		public static function ect_bricks_sanitize_layout_template( array $settings ) {
			return array(
				'template'    => self::ect_bricks_sanitize_template(),
				'item_chrome' => self::ect_bricks_sanitize_list_style( $settings['list_item_style'] ?? 'style-1' ),
			);
		}

		/** Drop empty rows and legacy link/prefix fields. */
		public static function ect_bricks_parts_clean( array $parts ) {
			$out = array();
			foreach ( $parts as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['part'] ) || trim( (string) $row['part'] ) === '' ) {
					continue;
				}
				unset( $row['venue_link'], $row['organizer_link'], $row['cost_prefix'], $row['cost_suffix'] );
				$out[] = $row;
			}
			return $out;
		}

		/** Copy Bricks row ids and style fields from saved rows onto defaults. */
		public static function ect_bricks_parts_preserve_bricks_rows( array $saved, array $defaults ) {
			$style_keys = ECT_Bricks_Part_Options::PRESERVE_STYLE_KEYS;
			foreach ( $defaults as $i => $row ) {
				if ( ! is_array( $row ) || ! isset( $saved[ $i ] ) || ! is_array( $saved[ $i ] ) ) {
					continue;
				}
				$saved_row = $saved[ $i ];
				if ( (string) ( $saved_row['part'] ?? '' ) !== (string) ( $row['part'] ?? '' ) ) {
					continue;
				}
				foreach ( $style_keys as $key ) {
					if ( array_key_exists( $key, $saved_row ) ) {
						$defaults[ $i ][ $key ] = $saved_row[ $key ];
					}
				}
				foreach ( $saved_row as $key => $value ) {
					if (
						is_string( $key )
						&& (
							strpos( $key, 'ect_bricks_typography:' ) === 0
							|| strpos( $key, 'ect_bricks_typography_combo:' ) === 0
						)
					) {
						$defaults[ $i ][ $key ] = $value;
					}
				}
			}
			return $defaults;
		}

		/** Use layout defaults when the parts repeater is empty. */
		public static function ect_bricks_upgrade_layout_parts( array $parts, callable $default_fn ) {
			if ( self::ect_bricks_parts_is_empty( $parts ) ) {
				return $default_fn();
			}

			return null;
		}

		public static function ect_bricks_parts_is_empty( array $parts ) {
			return self::ect_bricks_parts_clean( $parts ) === array();
		}

		/**
		 * Default List Elements rows (with Bricks row ids when available).
		 *
		 * @param string $list_item_style style-1|style-2
		 * @return array<int,array<string,mixed>>
		 */
		public static function ect_bricks_get_default_list_parts( $list_item_style = 'style-1' ) {
			if ( ! class_exists( 'ECT_Bricks_List_Defaults', false ) ) {
				$path = defined( 'ECT_BRICKS_DIR' ) ? ECT_BRICKS_DIR . 'templates/ect-bricks-list-defaults.php' : '';
				if ( $path !== '' && is_readable( $path ) ) {
					require_once $path;
				}
			}
			if ( ! class_exists( 'ECT_Bricks_List_Defaults', false ) ) {
				return array();
			}

			$defaults = \ECT_Bricks_List_Defaults::ect_bricks_default_parts( $list_item_style );
			if ( ! is_array( $defaults ) ) {
				return array();
			}

			return self::ect_bricks_parts_assign_ids( $defaults );
		}

		/** Assign Bricks repeater row ids (6-char lowercase alphanumeric). */
		public static function ect_bricks_parts_assign_ids( array $rows ) {
			foreach ( $rows as $index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$id = isset( $row['id'] ) ? (string) $row['id'] : '';
				if ( preg_match( '/^[a-z0-9]{6}$/', $id ) ) {
					continue;
				}
				if ( class_exists( '\Bricks\Helpers' ) && method_exists( '\Bricks\Helpers', 'generate_random_id' ) ) {
					$rows[ $index ]['id'] = \Bricks\Helpers::generate_random_id( false );
				} else {
					$rows[ $index ]['id'] = substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 6 );
				}
			}
			return $rows;
		}

		public static function ect_bricks_part_has_hover( $part ) {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				&& in_array( (string) $part, \ECT_Bricks_Part_Options::ect_bricks_hover_part_types(), true );
		}

		/**
		 * Drop the legacy ect_bricks_use_hover toggle from saved rows.
		 * Hover styling now applies whenever hover values are set
		 * (see ECT_Bricks_Part_Chrome::ect_bricks_hover_style_active()).
		 */
		public static function ect_bricks_norm_parts_hover( array $rows ) {
			foreach ( $rows as $index => $row ) {
				if ( is_array( $row ) && array_key_exists( 'ect_bricks_use_hover', $row ) ) {
					unset( $rows[ $index ]['ect_bricks_use_hover'] );
				}
			}
			return $rows;
		}

		/**
		 * Normalize parts repeaters on widget settings: drop legacy hover toggle, assign missing row ids.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string,mixed>
		 */
		public static function ect_bricks_norm_settings_hover( array $settings ) {
			foreach ( self::PARTS_REPEATER_KEYS as $key ) {
				if ( empty( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
					continue;
				}
				$settings[ $key ] = self::ect_bricks_parts_assign_ids(
					self::ect_bricks_norm_parts_hover( $settings[ $key ] )
				);
			}
			return $settings;
		}

		/**
		 * Active parts stack for layout (style-2 / style-1, then legacy parts).
		 *
		 * Expects settings already passed through {@see self::ect_bricks_norm_widget_settings()}
		 * (row ids + hover cleanup). This method only selects which repeater is active.
		 *
		 * @param array<string,mixed> $settings    Normalized widget settings.
		 * @param string              $item_chrome style-1|style-2.
		 * @return array<int,array<string,mixed>>
		 */
		public static function ect_bricks_resolve_parts( array $settings, $item_chrome ) {
			$item_chrome = self::ect_bricks_sanitize_list_style( $item_chrome );

			$non_empty = static function ( $key ) use ( $settings ) {
				$v = $settings[ $key ] ?? null;
				if ( ! is_array( $v ) || $v === array() || self::ect_bricks_parts_is_empty( $v ) ) {
					return null;
				}
				return $v;
			};

			if ( $item_chrome === 'style-2' ) {
				$s2 = $non_empty( 'parts_style2' ) ?? $non_empty( 'parts' );
				return $s2 ? $s2 : array();
			}
			$s1 = $non_empty( 'parts_style1' ) ?? $non_empty( 'parts' );
			return $s1 ? $s1 : array();
		}

		/** Normalize shell show/hide settings for Bricks save/render. */
		public static function ect_bricks_norm_layout_shell_settings( array $settings ) {
			foreach ( array( 'list1_show_category_badge', 'style2_show_date_badge', 'list1_show_date_column' ) as $key ) {
				if ( ! array_key_exists( $key, $settings ) ) {
					continue;
				}
				$raw = $settings[ $key ];
				if ( $raw === 'show' || $raw === 'hide' ) {
					continue;
				}
				// Checkbox: true = hide, false/absent = show.
				$settings[ $key ] = ECT_Bricks_Value_Utils::ect_bricks_parse_bricks_checkbox( $raw ) ? 'hide' : 'show';
			}

			return $settings;
		}

		/**
		 * Normalize widget settings for save/render (hover, shell toggles).
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string,mixed>
		 */
		public static function ect_bricks_norm_widget_settings( array $settings ) {
			$settings = self::ect_bricks_norm_settings_hover( $settings );
			return self::ect_bricks_norm_layout_shell_settings( $settings );
		}

		/**
		 * Root CSS classes for shell card/image hover animation presets.
		 *
		 * @param array<string,mixed> $settings Widget settings.
		 * @return string[]
		 */
		public static function ect_bricks_shell_hover_root_classes( array $settings ) {
			$classes = array();
			foreach (
				array(
					'ect_bricks_card_hover_animation'  => 'ect-card-hover--',
					'ect_bricks_image_hover_animation' => 'ect-img-hover--',
				) as $key => $prefix
			) {
				$slug = ECT_Bricks_Value_Utils::ect_bricks_sanitize_hover_animation_slug( $settings[ $key ] ?? '' );
				if ( $slug !== '' ) {
					$classes[] = $prefix . sanitize_html_class( $slug );
				}
			}

			return $classes;
		}

		/**
		 * True when image category hover Style options are configured.
		 *
		 * @param array<string,mixed> $settings Widget settings.
		 * @return bool
		 */
		public static function ect_bricks_shell_category_hover_active( array $settings ) {
			if ( ! self::ect_bricks_show_shell_category_badge( $settings ) ) {
				return false;
			}

			foreach (
				array(
					'ect_bricks_shell_category_hover_color',
					'ect_bricks_shell_category_hover_background',
				) as $key
			) {
				$value = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color(
					ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $settings[ $key ] ?? '' )
				);
				if ( $value !== '' ) {
					return true;
				}
			}

			$hover_td = ECT_Bricks_Value_Utils::ect_bricks_sanitize_text_decoration_slug(
				$settings['ect_bricks_shell_category_hover_text_decoration'] ?? ''
			);
			if ( $hover_td !== '' ) {
				return true;
			}

			$cat_hover = ECT_Bricks_Value_Utils::ect_bricks_sanitize_hover_animation_slug(
				$settings['ect_bricks_shell_category_hover_animation'] ?? ''
			);

			return $cat_hover !== '' && $cat_hover !== 'none';
		}

		/**
		 * Root CSS classes for Style tab shell presets (hover, date alignment, badge hover).
		 *
		 * @param array<string,mixed> $settings Widget settings.
		 * @return string[]
		 */
		public static function ect_bricks_shell_style_root_classes( array $settings ) {
			$classes = self::ect_bricks_shell_hover_root_classes( $settings );

			if ( self::ect_bricks_show_list1_date_column( $settings ) ) {
				$align = isset( $settings['ect_bricks_list1_date_align'] ) ? sanitize_key( (string) $settings['ect_bricks_list1_date_align'] ) : 'top';
				if ( ! in_array( $align, array( 'top', 'center', 'bottom' ), true ) ) {
					$align = 'top';
				}
				$classes[] = 'ect-list1-date-align--' . $align;
			}

			if ( ! self::ect_bricks_show_shell_category_badge( $settings ) ) {
				return $classes;
			}

			if ( self::ect_bricks_shell_category_hover_active( $settings ) ) {
				$classes[] = 'ect-has-shell-cat-hover';
			}

			$cat_hover = ECT_Bricks_Value_Utils::ect_bricks_sanitize_hover_animation_slug( $settings['ect_bricks_shell_category_hover_animation'] ?? '' );
			if ( $cat_hover !== '' && $cat_hover !== 'none' ) {
				$classes[] = 'ect-shell-cat-hover--' . sanitize_html_class( $cat_hover );
			}

			return $classes;
		}

		/** @return array<string,mixed> */
		private static function ect_bricks_coerce_settings( $settings ) {
			return is_array( $settings ) ? $settings : array();
		}

		/** True when a shell show/hide select (or legacy checkbox) is on. */
		public static function ect_bricks_shell_select_on( array $settings, $key, $default = 'show' ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				return $default !== 'hide';
			}
			$raw = $settings[ $key ];
			if ( $raw === 'hide' || $raw === 'no' ) {
				return false;
			}
			if ( $raw === 'show' || $raw === 'yes' ) {
				return true;
			}
			return ECT_Bricks_Value_Utils::ect_bricks_parse_bricks_checkbox( $raw );
		}

		public static function ect_bricks_show_event_image( $settings ) {
			$settings = self::ect_bricks_coerce_settings( $settings );
			return ! ECT_Bricks_Value_Utils::ect_bricks_parse_bricks_checkbox( $settings['hide_event_image'] ?? false );
		}

		public static function ect_bricks_show_shell_category_badge( $settings ) {
			$settings = self::ect_bricks_coerce_settings( $settings );
			if ( ! self::ect_bricks_show_event_image( $settings ) ) {
				return false;
			}
			$layout = self::ect_bricks_sanitize_layout_template( $settings );
			return $layout['item_chrome'] === 'style-1'
				&& self::ect_bricks_shell_select_on( $settings, 'list1_show_category_badge', 'show' );
		}

		public static function ect_bricks_show_style2_date_badge( $settings ) {
			$settings = self::ect_bricks_coerce_settings( $settings );
			if ( ! self::ect_bricks_show_event_image( $settings ) ) {
				return false;
			}
			$layout = self::ect_bricks_sanitize_layout_template( $settings );
			return $layout['item_chrome'] === 'style-2'
				&& self::ect_bricks_shell_select_on( $settings, 'style2_show_date_badge', 'show' );
		}

		public static function ect_bricks_show_list1_date_column( $settings ) {
			$settings = self::ect_bricks_coerce_settings( $settings );
			$layout   = self::ect_bricks_sanitize_layout_template( $settings );
			return $layout['item_chrome'] === 'style-1'
				&& self::ect_bricks_shell_select_on( $settings, 'list1_show_date_column', 'show' );
		}

		public static function ect_bricks_style2_date_badge_order( $settings ) {
			$settings = self::ect_bricks_coerce_settings( $settings );
			return self::ect_bricks_date_column_order_value( $settings['style2_date_badge_order'] ?? '', 'month_day' );
		}

		public static function ect_bricks_list1_date_column_order( $settings ) {
			$settings = self::ect_bricks_coerce_settings( $settings );
			return self::ect_bricks_date_column_order_value( $settings['list1_date_column_order'] ?? '', 'day_month' );
		}

		/** month_day|day_month with fallback. */
		private static function ect_bricks_date_column_order_value( $raw, $default = 'month_day' ) {
			$order    = is_string( $raw ) ? $raw : '';
			$fallback = in_array( $default, array( 'month_day', 'day_month' ), true ) ? $default : 'month_day';
			return in_array( $order, array( 'month_day', 'day_month' ), true ) ? $order : $fallback;
		}

		/**
		 * Resolve Meta_Combo provider class (direct service, else Styles facade).
		 *
		 * @return string Class name or empty when unavailable.
		 */
		private static function ect_bricks_meta_combo_provider() {
			if ( class_exists( 'ECT_Bricks_Meta_Combo', false ) ) {
				return 'ECT_Bricks_Meta_Combo';
			}
			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				return 'ECT_Bricks_Styles';
			}

			return '';
		}

		/**
		 * Meta-combo slugs when element data is available, else legacy defaults.
		 *
		 * @return string[]
		 */
		public static function ect_bricks_meta_combo_slugs_or_fallback() {
			$provider = self::ect_bricks_meta_combo_provider();
			if ( $provider !== '' ) {
				return $provider::ect_bricks_meta_combo_all_slugs();
			}

			return ECT_Bricks_Value_Utils::LEGACY_META_COMBO_SLUGS;
		}

		/**
		 * Whether a part slug (UI or cleaned) represents cost output.
		 *
		 * @param string $part Part slug.
		 * @return bool
		 */
		public static function ect_bricks_part_has_cost_segment( $part ) {
			$provider = self::ect_bricks_meta_combo_provider();
			if ( $provider !== '' ) {
				return $provider::ect_bricks_part_has_cost_segment( $part );
			}

			return (string) $part === 'event_cost';
		}

		/**
		 * Whether a part slug should use the venue/pin meta icon.
		 *
		 * @param string $part Part slug.
		 * @return bool
		 */
		public static function ect_bricks_part_has_venue_segment( $part ) {
			$provider = self::ect_bricks_meta_combo_provider();
			if ( $provider !== '' ) {
				return $provider::ect_bricks_part_has_venue_segment( $part );
			}

			return (string) $part === 'venue';
		}

		/**
		 * Whether a part slug should use the organizer/user meta icon.
		 *
		 * @param string $part Part slug.
		 * @return bool
		 */
		public static function ect_bricks_part_has_organizer_segment( $part ) {
			$provider = self::ect_bricks_meta_combo_provider();
			if ( $provider !== '' && method_exists( $provider, 'ect_bricks_part_has_organizer_segment' ) ) {
				return $provider::ect_bricks_part_has_organizer_segment( $part );
			}

			return in_array(
				(string) $part,
				array( 'organizer', 'organizer_email', 'organizer_phone', 'organizer_website' ),
				true
			);
		}
	}
}
