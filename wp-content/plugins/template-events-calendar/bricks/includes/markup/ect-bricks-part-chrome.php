<?php
/**
 * Part DOM ids, classes, hover styles, and shared chrome.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/ect-bricks-part-chrome-typography.php';
require_once __DIR__ . '/ect-bricks-part-chrome-media.php';

if ( ! class_exists( 'ECT_Bricks_Part_Chrome', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Part_Chrome {
		use ECT_Bricks_Part_Chrome_Typography;
		use ECT_Bricks_Part_Chrome_Media;

		/**
		 * Resolved hover paint + class flags for a repeater row.
		 *
		 * Memoized per Bricks row id so part classes / meta-li / hover vars share one pass.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return array{fg:string,bg:string,has_fg:bool,has_bg:bool,has_td:bool,anim_class:string}
		 */
		public static function ect_bricks_row_hover_state( array $item ): array {
			static $cache = array();

			$row_id = isset( $item['id'] ) ? (string) $item['id'] : '';
			if ( $row_id !== '' && isset( $cache[ $row_id ] ) ) {
				return $cache[ $row_id ];
			}

			$state = array(
				'fg'         => '',
				'bg'         => '',
				'has_fg'     => false,
				'has_bg'     => false,
				'has_td'     => false,
				'anim_class' => '',
			);
			if ( ! self::ect_bricks_hover_style_active( $item ) ) {
				if ( $row_id !== '' ) {
					$cache[ $row_id ] = $state;
				}
				return $state;
			}

			$hover_fg = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_hover_color'] ?? ( $item['hover_color'] ?? '' ) );
			if ( $hover_fg !== '' ) {
				$state['fg']     = $hover_fg;
				$state['has_fg'] = true;
			}

			$hover_bg = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_hover_background'] ?? '' );
			if ( $hover_bg !== '' ) {
				$state['bg']     = $hover_bg;
				$state['has_bg'] = true;
			}

			$hover_td = ECT_Bricks_Value_Utils::ect_bricks_sanitize_text_decoration_slug(
				$item['ect_bricks_hover_text_decoration'] ?? ''
			);
			if ( $hover_td !== '' ) {
				$state['has_td'] = true;
			}

			$hover_anim = ECT_Bricks_Value_Utils::ect_bricks_sanitize_hover_animation_slug(
				$item['ect_bricks_hover_animation'] ?? ''
			);
			if ( $hover_anim !== '' && $hover_anim !== 'none' ) {
				$state['anim_class'] = 'ect-part-hover--' . $hover_anim;
			}

			if ( $row_id !== '' ) {
				$cache[ $row_id ] = $state;
			}

			return $state;
		}

		/** Merge layout/skin classes onto the first anchor in read-more markup. */
		public static function ect_bricks_read_more_merge_link_classes( $html, $extra_classes ) {
			$extra_classes = trim( (string) $extra_classes );
			if ( $extra_classes === '' || strpos( $html, '<a ' ) === false ) {
				return $html;
			}

			if ( preg_match( '#(<a\s[^>]*\sclass=")([^"]*)(")#', $html, $m, PREG_OFFSET_CAPTURE ) ) {
				$merged = trim( $m[2][0] . ' ' . $extra_classes );
				return substr_replace( $html, $merged, $m[2][1], strlen( $m[2][0] ) );
			}

			return preg_replace(
				'#<a\s#',
				'<a class="' . esc_attr( $extra_classes ) . '" ',
				$html,
				1
			);
		}

		private static function ect_bricks_part_dom_id( array $item, $idx ) {
			if ( ! empty( $item['id'] ) ) {
				return (string) $item['id'];
			}
			return (string) absint( $idx );
		}

		public static function ect_bricks_part_dom_id_attr( array $item, $idx ) {
			$id = self::ect_bricks_part_dom_id( $item, $idx );
			if ( $id === '' ) {
				return '';
			}
			return ' data-field-id="' . esc_attr( $id ) . '"';
		}

		/**
		 * Inline repeater hover CSS variables on the part wrapper.
		 *
		 * Bricks control `css` also sets these on `&`, but the deleted inline
		 * generator used to guarantee vars on the rendered row. Static CSS in
		 * base.css consumes the variables on :hover surfaces.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		public static function ect_bricks_part_hover_vars_attr( array $item ) {
			return self::ect_bricks_inline_style_attr_from_decls( self::ect_bricks_part_hover_var_decls( $item ) );
		}

		/**
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_hover_var_decls( array $item ) {
			$hover = self::ect_bricks_row_hover_state( $item );
			$decls = array();
			if ( $hover['fg'] !== '' ) {
				$decls[] = '--ect-bricks-hover-fg:' . $hover['fg'];
			}
			if ( $hover['bg'] !== '' ) {
				$decls[] = '--ect-bricks-hover-bg:' . $hover['bg'];
			}
			if ( $hover['has_td'] ) {
				$hover_td = ECT_Bricks_Value_Utils::ect_bricks_sanitize_text_decoration_slug(
					$item['ect_bricks_hover_text_decoration'] ?? ''
				);
				$decls[]  = '--ect-bricks-hover-text-decoration:' . $hover_td;
			}

			return $decls;
		}

		/**
		 * Meta icon + shared chip background vars for Style 2 venue/time/cost icons.
		 *
		 * Inlined so icons update even when Bricks live-preview only paints
		 * `color` / `background-color` on the row wrapper.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_meta_icon_var_decls( array $item ) {
			$decls   = array();
			$icon_fg = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_meta_icon_color'] ?? '' );
			if ( $icon_fg !== '' ) {
				$decls[] = '--ect-bricks-meta-icon-color:' . $icon_fg;
			}
			$icon_bg = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_meta_icon_background'] ?? '' );
			if ( $icon_bg !== '' ) {
				$decls[] = '--ect-bricks-meta-icon-bg:' . $icon_bg;
			}
			// Part Background also drives the icon chip via --ect-bricks-chip-bg.
			$chip_bg = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_background'] ?? '' );
			if ( $chip_bg !== '' ) {
				$decls[] = '--ect-bricks-chip-bg:' . $chip_bg;
			}

			return $decls;
		}

		/**
		 * Button / chip paint vars from Style tab Background + Typography color.
		 *
		 * Inlined so CTAs pick up colors even when Bricks live-preview only paints the row wrapper.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_paint_var_decls( array $item ) {
			$decls  = array();
			$btn_bg = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_background'] ?? '' );
			if ( $btn_bg !== '' ) {
				$decls[] = '--ect-bricks-btn-bg:' . $btn_bg;
				$decls[] = '--ect-bricks-chip-bg:' . $btn_bg;
			}
			$btn_fg = self::ect_bricks_typography_color_value( $item );
			if ( $btn_fg !== '' ) {
				$decls[] = '--ect-bricks-btn-fg:' . $btn_fg;
				$decls[] = '--ect-bricks-chip-fg:' . $btn_fg;
			}

			return $decls;
		}

		/**
		 * @param string[] $decls
		 * @return string
		 */
		private static function ect_bricks_inline_style_attr_from_decls( array $decls ) {
			if ( $decls === array() ) {
				return '';
			}

			return ' style="' . esc_attr( implode( ';', $decls ) . ';' ) . '"';
		}

		/**
		 * Direct typography inline declarations (color only).
		 *
		 * Font-size / line-height / text-transform / text-decoration come from the Bricks typography
		 * control `css` map so builder updates apply immediately. PHP inline copies
		 * of those values were sticky until a full reset/re-render.
		 *
		 * @param array<string,mixed> $item Repeater row or pseudo-row with ect_bricks_typography.
		 * @return string[]
		 */
		private static function ect_bricks_typography_inline_decls( array $item ) {
			$decls = array();
			$color = self::ect_bricks_typography_color_value( $item );
			if ( $color !== '' ) {
				$decls[] = 'color:' . $color;
			}

			return $decls;
		}

		/**
		 * Typography CSS variables for repeater row wrappers (frontend + builder parity).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_typography_var_decls( array $item ) {
			$decls = array();
			if ( self::ect_bricks_part_typography_skips_text_decoration( $item ) ) {
				return $decls;
			}
			$dec = self::ect_bricks_typography_text_decoration_value( $item );
			if ( $dec !== '' ) {
				$decls[] = '--ect-bricks-typo-text-decoration:' . $dec;
			}

			return $decls;
		}

		/**
		 * Typography inline declarations from widget-level settings (shell badge, etc.).
		 *
		 * @param array<string,mixed> $settings Widget settings.
		 * @param string              $base_key Typography control key.
		 * @return string[]
		 */
		public static function ect_bricks_typography_style_decls_from_settings( array $settings, $base_key ) {
			$typography = ECT_Bricks_Value_Utils::ect_bricks_typography_desktop_tree( $settings, $base_key );
			if ( $typography === array() ) {
				return array();
			}

			return self::ect_bricks_typography_inline_decls(
				array( 'ect_bricks_typography' => $typography )
			);
		}

		/**
		 * Saved Alignments value for a repeater row (desktop).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string left|center|right|justify
		 */
		public static function ect_bricks_text_align_value( array $item ) {
			if ( ! array_key_exists( 'ect_bricks_text_align', $item ) ) {
				return '';
			}

			$align = $item['ect_bricks_text_align'];
			if ( is_array( $align ) ) {
				$align = $align['desktop'] ?? $align['default'] ?? reset( $align );
			}

			$align = sanitize_key( (string) $align );

			return in_array( $align, array( 'left', 'center', 'right', 'justify' ), true ) ? $align : '';
		}

		/**
		 * Map text-align keywords to flex justify-content for meta / category rows.
		 *
		 * @param string $align left|center|right|justify
		 * @return string
		 */
		private static function ect_bricks_align_to_justify_content( $align ) {
			$map = array(
				'left'    => 'flex-start',
				'center'  => 'center',
				'right'   => 'flex-end',
				'justify' => 'space-between',
			);

			return isset( $map[ $align ] ) ? $map[ $align ] : '';
		}

		/**
		 * Inline alignment on the rendered part wrapper (Style 2 + builder parity).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_align_decls( array $item ) {
			$align = self::ect_bricks_text_align_value( $item );
			if ( $align === '' ) {
				return array();
			}

			$decls   = array( 'text-align:' . $align );
			$justify = self::ect_bricks_align_to_justify_content( $align );
			if ( $justify !== '' ) {
				$decls[] = 'justify-content:' . $justify;
			}

			return $decls;
		}

		/**
		 * Alignment / spacing chrome for venue, time, cost, and combo meta list rows.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return array{classes:string[],style:string}
		 */
		public static function ect_bricks_part_meta_li_chrome( array $item ) {
			$classes = array();
			$decls   = array();

			$ui_part  = isset( $item['part'] ) ? (string) $item['part'] : '';
			$is_combo = class_exists( 'ECT_Bricks_Styles', false )
				&& \ECT_Bricks_Styles::ect_bricks_is_meta_combo_slug( $ui_part );

			$align = self::ect_bricks_text_align_value( $item );
			if ( $align !== '' ) {
				$classes[] = 'ect-has-align';
				$classes[] = 'ect-align--' . $align;
				$decls     = array_merge( $decls, self::ect_bricks_part_align_decls( $item ) );
				// Combo rows keep Style 2 grid unwrap; custom-chrome is for margin/padding only.
				if ( ! $is_combo ) {
					$classes[] = 'ect-meta-row--custom-chrome';
				}
			}

			if ( $is_combo ) {
				$margin = ECT_Bricks_Value_Utils::ect_bricks_spacing_to_css( $item['ect_bricks_margin'] ?? null );
				if ( $margin !== '' ) {
					$decls[]   = 'margin:' . $margin;
					$classes[] = 'ect-meta-row--custom-chrome';
				}

				$padding = ECT_Bricks_Value_Utils::ect_bricks_spacing_to_css( $item['ect_bricks_padding'] ?? null );
				if ( $padding !== '' ) {
					$decls[]   = 'padding:' . $padding;
					$classes[] = 'ect-meta-row--custom-chrome';
				}

				$typo_classes = self::ect_bricks_part_meta_li_typography_tokens( $item );
				if ( $typo_classes !== array() ) {
					$classes = array_merge( $classes, $typo_classes );
				}
				$typo_decls = self::ect_bricks_typography_var_decls( $item );
				if ( $typo_decls !== array() ) {
					$decls = array_merge( $decls, $typo_decls );
				}
			}

			return array(
				'classes' => array_values( array_unique( $classes ) ),
				'style'   => $decls !== array()
					? ' style="' . esc_attr( implode( ';', $decls ) . ';' ) . '"'
					: '',
			);
		}

		/**
		 * Button chrome vars (padding, radius) when custom button styles are enabled.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_btn_chrome_var_decls( array $item ) {
			if ( ! self::ect_bricks_btn_style_active( $item ) ) {
				return array();
			}

			$decls   = array();
			$padding = ECT_Bricks_Value_Utils::ect_bricks_spacing_to_css( $item['btn_padding'] ?? null );
			if ( $padding !== '' ) {
				$decls[] = '--ect-bricks-btn-padding:' . $padding;
			}

			$radius = ECT_Bricks_Value_Utils::ect_bricks_dimensions_to_css( $item['btn_border_radius'] ?? null );
			if ( $radius !== '' ) {
				$decls[] = '--ect-bricks-btn-radius:' . $radius;
			}

			return $decls;
		}

		/**
		 * Inline border/radius/size on the <img> — never on the row wrapper.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		public static function ect_bricks_part_image_img_style_decls( array $item ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( $part !== 'image' ) {
				return array();
			}

			$decls  = array();
			$border = ECT_Bricks_Value_Utils::ect_bricks_border_to_css( $item['ect_bricks_image_border'] ?? null );
			if ( $border !== '' ) {
				$decls[] = 'border:' . $border;
			}

			$radius = ECT_Bricks_Value_Utils::ect_bricks_dimensions_to_css( $item['ect_bricks_image_radius'] ?? null );
			if ( $radius === '' ) {
				$border_val = ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $item['ect_bricks_image_border'] ?? null );
				if ( is_array( $border_val ) && isset( $border_val['radius'] ) ) {
					$radius = ECT_Bricks_Value_Utils::ect_bricks_dimensions_to_css( $border_val['radius'] );
				}
			}
			if ( $radius !== '' ) {
				$decls[] = 'border-radius:' . $radius;
			}

			$width = ECT_Bricks_Value_Utils::ect_bricks_css_size_value( $item['ect_bricks_image_width'] ?? null );
			if ( $width !== '' ) {
				$decls[] = 'width:' . $width;
			}

			$height = ECT_Bricks_Value_Utils::ect_bricks_css_size_value( $item['ect_bricks_image_height'] ?? null );
			if ( $height !== '' ) {
				$decls[] = 'height:' . $height;
			}

			if ( $decls !== array() ) {
				$decls[] = 'box-sizing:border-box';
			}

			return $decls;
		}

		/**
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		private static function ect_bricks_part_style_vars_attr( array $item ) {
			$decls = array_merge(
				self::ect_bricks_part_hover_var_decls( $item ),
				self::ect_bricks_part_meta_icon_var_decls( $item ),
				self::ect_bricks_part_paint_var_decls( $item ),
				self::ect_bricks_typography_inline_decls( $item ),
				self::ect_bricks_typography_var_decls( $item ),
				self::ect_bricks_part_align_decls( $item ),
				self::ect_bricks_part_btn_chrome_var_decls( $item )
			);

			return self::ect_bricks_inline_style_attr_from_decls( $decls );
		}

		/**
		 * Inline typography for CTA / action links.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		public static function ect_bricks_part_link_typography_attr( array $item ) {
			return self::ect_bricks_inline_style_attr_from_decls( self::ect_bricks_typography_inline_decls( $item ) );
		}

		public static function ect_bricks_part_wrap_attrs( array $item, $idx ) {
			return self::ect_bricks_part_dom_id_attr( $item, $idx ) . self::ect_bricks_part_style_vars_attr( $item );
		}

		public static function ect_bricks_part_classes( $part, $idx, $skin = '', array $item = array() ) {
			$idx_c = self::ect_bricks_part_index_class( $idx );
			if ( (string) $skin === 'style2' ) {
				$part_cls = class_exists( 'ECT_Bricks_List_2', false )
				? \ECT_Bricks_List_2::ect_bricks_part_class( $part )
				: 'ect-s2-' . str_replace( '_', '-', (string) $part );
				$classes  = 'ect-part ' . $part_cls . ' ' . $idx_c;
			} else {
				$bem     = 'ect-part--' . str_replace( '_', '-', (string) $part );
				$classes = 'ect-part ' . $bem . ' ' . $idx_c;
			}
			if ( $item === array() ) {
				return $classes;
			}

			$row     = ( ! empty( $item['_ect_bricks_clean'] ) || ! class_exists( 'ECT_Bricks_Styles', false ) )
				? $item
				: \ECT_Bricks_Styles::ect_bricks_clean_part( $item );
			$ui_part = isset( $item['part'] ) ? (string) $item['part'] : (string) $part;
			$tokens  = array_merge(
				self::ect_bricks_part_hover_class_tokens( $ui_part, $row, $item ),
				self::ect_bricks_part_typography_class_tokens( $ui_part, $row, $item )
			);

			if ( $tokens !== array() ) {
				$classes .= ' ' . implode( ' ', $tokens );
			}

			return $classes;
		}

		/**
		 * Hover / no-hover class tokens for a repeater row.
		 *
		 * @param string               $ui_part UI part slug.
		 * @param array<string,mixed>  $row     Cleaned repeater row.
		 * @param array<string,mixed>  $item    Original repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_hover_class_tokens( $ui_part, array $row, array $item ) {
			$tokens   = array();
			$hover    = self::ect_bricks_row_hover_state( $item );
			$hover_on = $hover['has_fg'] || $hover['has_bg'] || $hover['has_td'] || $hover['anim_class'] !== '';

			if (
				$ui_part === 'title'
				&& ! self::ect_bricks_title_link_active( $row )
			) {
				$tokens[] = 'ect-no-hover';
			} elseif (
				ECT_Bricks_Settings_Normalizer::ect_bricks_part_has_hover( $ui_part )
				&& ! $hover_on
				&& ! in_array( $ui_part, \ECT_Bricks_Part_Options::CTA_PARTS, true )
			) {
				$tokens[] = 'ect-no-hover';
			}

			if ( ! $hover_on ) {
				return $tokens;
			}
			if ( $hover['has_fg'] ) {
				$tokens[] = 'ect-has-hover-fg';
			}
			if ( $hover['has_bg'] ) {
				$tokens[] = 'ect-has-hover-bg';
			}
			if ( $hover['has_td'] ) {
				$tokens[] = 'ect-has-hover-td';
			}
			if ( $hover['anim_class'] !== '' ) {
				$tokens[] = $hover['anim_class'];
			}

			return $tokens;
		}

		/**
		 * Typography / layout / CTA class tokens for a repeater row.
		 *
		 * @param string               $ui_part UI part slug.
		 * @param array<string,mixed>  $row     Cleaned repeater row.
		 * @param array<string,mixed>  $item    Original repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_typography_class_tokens( $ui_part, array $row, array $item ) {
			$tokens = array();

			if (
				self::ect_bricks_btn_style_active( $row )
				&& class_exists( 'ECT_Bricks_Styles', false )
				&& in_array( $ui_part, \ECT_Bricks_Part_Options::CTA_PARTS, true )
			) {
				$tokens[] = 'ect-has-btn';
			}
			if ( self::ect_bricks_row_has_typography_color( $item ) ) {
				$tokens[] = 'ect-has-typo-fg';
			}
			if ( self::ect_bricks_row_has_part_background( $item ) && $ui_part !== 'image' ) {
				$tokens[] = 'ect-has-part-bg';
			}
			if ( self::ect_bricks_row_has_typography_font_size( $item ) ) {
				$tokens[] = 'ect-has-typo-size';
			}
			if ( self::ect_bricks_row_has_typography_text_transform( $item ) ) {
				$tokens[] = 'ect-has-typo-tx';
			}
			if (
				self::ect_bricks_row_has_typography_text_decoration( $item )
				&& ! self::ect_bricks_part_typography_skips_text_decoration( $item )
			) {
				$tokens[] = 'ect-has-typo-dec';
			}
			$align = self::ect_bricks_text_align_value( $item );
			if ( $align !== '' ) {
				$tokens[] = 'ect-has-align';
				$tokens[] = 'ect-align--' . $align;
			}
			if ( self::ect_bricks_part_uses_meta_icon( $ui_part ) ) {
				$tokens[] = 'ect-has-meta-icon';
			}

			return $tokens;
		}

		/**
		 * Typography class tokens mirrored on combo meta list rows (Style 2 display:contents).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string[]
		 */
		private static function ect_bricks_part_meta_li_typography_tokens( array $item ) {
			$tokens = array();

			if ( self::ect_bricks_row_has_typography_color( $item ) ) {
				$tokens[] = 'ect-has-typo-fg';
			}
			if ( self::ect_bricks_row_has_typography_font_size( $item ) ) {
				$tokens[] = 'ect-has-typo-size';
			}
			if ( self::ect_bricks_row_has_typography_text_transform( $item ) ) {
				$tokens[] = 'ect-has-typo-tx';
			}

			return $tokens;
		}

		public static function ect_bricks_part_index_class( $idx ) {
			return 'ect-bricks-p' . absint( $idx );
		}

		/**
		 * Whether a repeater row has saved hover styling (colors, decoration, animation).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		public static function ect_bricks_hover_has_custom_styles( array $item ) {
			foreach ( array( 'ect_bricks_hover_color', 'ect_bricks_hover_background' ) as $key ) {
				if ( ! array_key_exists( $key, $item ) || $item[ $key ] === '' || $item[ $key ] === null ) {
					continue;
				}
				if ( ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item[ $key ] ) !== '' ) {
					return true;
				}
			}

			$hover_td = ECT_Bricks_Value_Utils::ect_bricks_sanitize_text_decoration_slug(
				$item['ect_bricks_hover_text_decoration'] ?? ''
			);
			if ( $hover_td !== '' ) {
				return true;
			}

			$hover_anim = ECT_Bricks_Value_Utils::ect_bricks_sanitize_hover_animation_slug(
				$item['ect_bricks_hover_animation'] ?? ''
			);
			if ( $hover_anim === 'none' || $hover_anim === '' ) {
				return false;
			}
			return true;
		}

		public static function ect_bricks_hover_style_active( array $item ) {
			$ui_part = isset( $item['part'] ) ? (string) $item['part'] : '';
			if ( ! ECT_Bricks_Settings_Normalizer::ect_bricks_part_has_hover( $ui_part ) ) {
				return false;
			}
			if (
			$ui_part === 'title'
			&& ! self::ect_bricks_title_link_active( $item )
			) {
				return false;
			}
			// No enable/disable toggle: hover styling is active exactly when the
			// row actually defines hover values (color/background/decoration/animation).
			return self::ect_bricks_hover_has_custom_styles( $item );
		}

		public static function ect_bricks_title_link_active( array $item ) {
			return ECT_Bricks_Value_Utils::ect_bricks_parse_bricks_checkbox( $item['link'] ?? false );
		}

		public static function ect_bricks_btn_style_active( array $item ) {
			return ECT_Bricks_Value_Utils::ect_bricks_parse_bricks_checkbox( $item['btn_style'] ?? false );
		}

		/**
		 * Reference layout button class for read-more per skin.
		 *
		 * @param string $skin style1|style2
		 * @return string
		 */
		public static function ect_bricks_layout_read_more_btn_class( $skin ) {
			$skin = (string) $skin;
			if ( $skin === 'style2' ) {
				return 'ect-card__btn';
			}
			return 'event-button event-button--outline';
		}

		/**
		 * Whether a part slug renders Style 2 boxed meta icons (venue/time/cost/organizer/tags).
		 *
		 * @param string $part_slug UI / cleaned part slug.
		 * @return bool
		 */
		private static function ect_bricks_part_uses_meta_icon( $part_slug ) {
			$part_slug = (string) $part_slug;
			if ( in_array( $part_slug, array( 'venue', 'date', 'event_cost', 'tags' ), true ) ) {
				return true;
			}
			if ( ECT_Bricks_Settings_Normalizer::ect_bricks_part_has_organizer_segment( $part_slug ) ) {
				return true;
			}

			return class_exists( 'ECT_Bricks_Styles', false )
				&& \ECT_Bricks_Styles::ect_bricks_is_meta_combo_slug( $part_slug );
		}
	}
}
