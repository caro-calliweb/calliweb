<?php
/**
 * Part Chrome image size and terms helpers (trait).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'ECT_Bricks_Part_Chrome_Media', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedTraitFound
	trait ECT_Bricks_Part_Chrome_Media {

		/** Default WordPress image size slug for repeater featured images. */
		public static function ect_bricks_default_image_size() {
			return 'medium';
		}

		public static function ect_bricks_image_size_opts() {
			$default_slug = self::ect_bricks_default_image_size();
			$subs         = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : array();
			$default_w    = isset( $subs[ $default_slug ]['width'] ) ? (int) $subs[ $default_slug ]['width'] : 300;
			$default_h    = isset( $subs[ $default_slug ]['height'] ) ? (int) $subs[ $default_slug ]['height'] : 300;

			$opts = array(
				'' => sprintf(
					/* translators: 1: image size slug, 2: width, 3: height */
					esc_html__( 'Default (%1$s — %2$d×%3$d)', 'template-events-calendar' ),
					$default_slug,
					$default_w,
					$default_h
				),
			);
			foreach ( $subs as $slug => $data ) {
				if ( ! is_string( $slug ) || $slug === '' ) {
					continue;
				}
				$w             = isset( $data['width'] ) ? (int) $data['width'] : 0;
				$h             = isset( $data['height'] ) ? (int) $data['height'] : 0;
				$opts[ $slug ] = $slug . ' (' . $w . "\u{00D7}" . $h . ')';
			}
			if ( function_exists( 'get_intermediate_image_sizes' ) ) {
				foreach ( get_intermediate_image_sizes() as $slug ) {
					if ( is_string( $slug ) && $slug !== '' && ! isset( $opts[ $slug ] ) ) {
						$opts[ $slug ] = $slug;
					}
				}
			}
			$opts['full'] = esc_html__( 'Full', 'template-events-calendar' );
			return $opts;
		}

		public static function ect_bricks_sanitize_image_size( $slug, $fallback = '' ) {
			if ( $fallback === '' ) {
				$fallback = self::ect_bricks_default_image_size();
			}
			$slug = is_string( $slug ) ? trim( $slug ) : '';
			if ( $slug === '' ) {
				return $fallback;
			}
			if ( $slug === 'full' ) {
				return 'full';
			}
			$reg = function_exists( 'wp_get_registered_image_subsizes' ) ? wp_get_registered_image_subsizes() : array();
			if ( isset( $reg[ $slug ] ) ) {
				return $slug;
			}
			$intermediate = function_exists( 'get_intermediate_image_sizes' ) ? get_intermediate_image_sizes() : array();
			if ( is_array( $intermediate ) && in_array( $slug, $intermediate, true ) ) {
				return $slug;
			}
			if ( preg_match( '/^[a-z0-9_\\-]+$/i', $slug ) ) {
				return $slug;
			}
			return $fallback;
		}

		/**
		 * @param \WP_Term[] $terms
		 * @param string     $anchor_class
		 * @param string[]   $style_decls Safe CSS declarations (e.g. color:#fff); escaped when rendered.
		 * @return string[]
		 */
		public static function ect_bricks_term_anchor_links( array $terms, $anchor_class, array $style_decls = array() ) {
			$links      = array();
			$style_attr = self::ect_bricks_inline_style_attr_from_decls( $style_decls );
			foreach ( $terms as $term ) {
				if ( ! $term instanceof \WP_Term ) {
					continue;
				}
				$url = get_term_link( $term );
				if ( is_wp_error( $url ) ) {
					continue;
				}
				$links[] = '<a class="' . esc_attr( (string) $anchor_class ) . '" href="' . esc_url( $url ) . '"' . $style_attr . '>'
					. esc_html( $term->name ) . '</a>';
			}

			return $links;
		}

		/**
		 * CTA / action part anchor (read more).
		 *
		 * @param array<string,mixed>  $item  Repeater row.
		 * @param string               $href  Destination URL.
		 * @param string               $label Visible label.
		 * @param array<string,string> $attrs Extra HTML attributes (name => value); values are escaped.
		 * @return string
		 */
		public static function ect_bricks_action_link_html( array $item, $href, $label, array $attrs = array() ) {
			$part       = isset( $item['part'] ) ? (string) $item['part'] : '';
			$force_link = in_array( $part, \ECT_Bricks_Part_Options::CTA_PARTS, true );

			if ( ! $force_link && ! self::ect_bricks_hover_style_active( $item ) && ! self::ect_bricks_btn_style_active( $item ) ) {
				return '<span class="ect-fld__plain">' . esc_html( $label ) . '</span>';
			}

			$style_attr = self::ect_bricks_inline_style_attr_from_decls( self::ect_bricks_typography_inline_decls( $item ) );
			$attr_html  = '';
			foreach ( $attrs as $name => $value ) {
				$name = preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $name );
				if ( $name === '' ) {
					continue;
				}
				$attr_html .= ' ' . $name . '="' . esc_attr( (string) $value ) . '"';
			}

			return '<a class="ect-fld__link" href="' . esc_url( $href ) . '"' . $style_attr . $attr_html . '>' . esc_html( $label ) . '</a>';
		}

		public static function ect_bricks_terms_html( array $terms, array $item, $skin = '', $part = 'categories' ) {
			unset( $skin, $part );

			$sep = isset( $item['terms_separator'] ) ? sanitize_text_field( (string) $item['terms_separator'] ) : ', ';
			$sep = $sep !== '' ? $sep : ', ';

			$link_decls = self::ect_bricks_typography_inline_decls( $item );

			$links = self::ect_bricks_term_anchor_links( $terms, 'ect-fld__link', $link_decls );

			if ( $links === array() ) {
				return '';
			}

			$sep_html = esc_html( $sep );
			return implode( $sep_html, $links );
		}

		/**
		 * Inline shell category hover CSS variables on the widget root (.ect-ev).
		 *
		 * Hover tokens only (not normal color/bg). Normal colors are painted by Bricks
		 * directly on `.event-badge--blue`. Hover paint is applied in CSS exclusively on
		 * `.event-badge--blue:hover` so card/widget hover cannot recolor every badge.
		 *
		 * @param array<string,mixed> $settings Widget settings.
		 * @return string Semicolon-separated declarations (no style= wrapper).
		 */
		public static function ect_bricks_shell_category_root_style_decls( array $settings ) {
			if ( ! ECT_Bricks_Settings_Normalizer::ect_bricks_show_shell_category_badge( $settings ) ) {
				return '';
			}

			$map = array(
				'ect_bricks_shell_category_hover_color'      => '--ect-bricks-shell-cat-hover-color',
				'ect_bricks_shell_category_hover_background' => '--ect-bricks-shell-cat-hover-bg',
			);

			$decls = array();
			foreach ( $map as $setting_key => $css_var ) {
				$value = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color(
					ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $settings[ $setting_key ] ?? '' )
				);
				if ( $value !== '' ) {
					$decls[] = $css_var . ':' . $value;
				}
			}

			$hover_td = ECT_Bricks_Value_Utils::ect_bricks_sanitize_text_decoration_slug(
				$settings['ect_bricks_shell_category_hover_text_decoration'] ?? ''
			);
			if ( $hover_td !== '' ) {
				$decls[] = '--ect-bricks-shell-cat-hover-text-decoration:' . $hover_td;
			}

			if ( $decls === array() ) {
				return '';
			}

			return implode( ';', $decls ) . ';';
		}
	}
}
