<?php
/**
 * Bricks control value normalization (colors, checkboxes).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Value_Utils', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Value_Utils {

		/** @var string[] Legacy meta-combo part slugs when Meta_Combo element data is unavailable. */
		public const LEGACY_META_COMBO_SLUGS = array( 'venue_time', 'venue_time_cost' );

		/**
		 * Bricks checkbox value → bool.
		 *
		 * Bricks stores checked boxes as true; unchecked keys are absent or false.
		 */
		public static function ect_bricks_parse_bricks_checkbox( $value, $default = false ) {
			if ( is_bool( $value ) ) {
				return $value;
			}
			if ( $value === null || $value === '' || $value === array() ) {
				return $default;
			}
			if ( $value === 1 || $value === '1' || $value === 'true' || $value === 'yes' || $value === 'on' ) {
				return true;
			}
			if ( $value === 0 || $value === '0' || $value === 'false' || $value === 'no' || $value === 'off' ) {
				return false;
			}

			return (bool) $value;
		}

		/** Bricks color control value → CSS color string, or empty. */
		private static function ect_bricks_norm_color( $value ) {
			if ( $value === null || $value === false ) {
				return '';
			}
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			if ( is_array( $value ) ) {
				return self::ect_bricks_norm_color_from_array( $value );
			}
			if ( is_string( $value ) ) {
				return self::ect_bricks_validate_css_color_string( trim( $value ) );
			}
			return '';
		}

		private static function ect_bricks_norm_color_from_array( array $value ) {
			$color = '';
			if (
				class_exists( '\Bricks\Assets' ) && method_exists( '\Bricks\Assets', 'generate_css_color' )
				&& ( isset( $value['id'] ) || isset( $value['raw'] ) || isset( $value['rgb'] ) || isset( $value['hex'] ) || isset( $value['rgba'] ) )
			) {
				$gen = \Bricks\Assets::generate_css_color( $value );
				if ( is_string( $gen ) && trim( $gen ) !== '' ) {
					$color = trim( $gen );
				}
			}
			if ( $color === '' && isset( $value['rgb'] ) && is_array( $value['rgb'] ) ) {
				$r     = isset( $value['rgb']['r'] ) ? (int) $value['rgb']['r'] : 0;
				$g     = isset( $value['rgb']['g'] ) ? (int) $value['rgb']['g'] : 0;
				$b     = isset( $value['rgb']['b'] ) ? (int) $value['rgb']['b'] : 0;
				$a     = isset( $value['rgb']['a'] ) ? (float) $value['rgb']['a'] : 1.0;
				$color = 'rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ')';
			}
			if ( $color === '' ) {
				$tmp   = $value['raw'] ?? $value['rgba'] ?? $value['hex'] ?? $value['value'] ?? '';
				$color = is_string( $tmp ) ? trim( $tmp ) : '';
			}
			if ( $color === '' ) {
				return '';
			}
			return self::ect_bricks_validate_css_color_string( $color );
		}

		private static function ect_bricks_validate_css_color_string( $color ) {
			if ( $color === '' ) {
				return '';
			}
			if ( isset( $color[0] ) && ( $color[0] === '{' || $color[0] === '[' ) ) {
				$decoded = json_decode( $color, true );
				if ( is_array( $decoded ) ) {
					return self::ect_bricks_norm_color( $decoded );
				}
			}
			$color = preg_replace( '/\s*!important\s*$/i', '', $color );
			$color = rtrim( trim( $color ), ';' );
			if ( preg_match( '/^var\\(--[a-zA-Z0-9\\-_]+(\\s*,\\s*[^\\)]+)?\\)$/', $color ) ) {
				return $color;
			}
			$lower = strtolower( $color );
			if ( in_array( $lower, array( 'transparent', 'currentcolor', 'inherit', 'initial', 'unset' ), true ) ) {
				return $color;
			}
			if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
				return $color;
			}
			if ( preg_match( '/^rgba?\\(([^\\)]+)\\)$/', $color ) ) {
				return $color;
			}
			if ( preg_match( '/^hsla?\\(([^\\)]+)\\)$/', $color ) ) {
				return $color;
			}
			return '';
		}

		/** Visible hover paint only (drops transparent / inherit / zero-alpha). */
		public static function ect_bricks_norm_hover_paint_color( $value ) {
			$color = self::ect_bricks_norm_color( $value );
			if ( $color === '' ) {
				return '';
			}
			$lower = strtolower( trim( $color ) );
			if ( in_array( $lower, array( 'transparent', 'currentcolor', 'inherit', 'initial', 'unset' ), true ) ) {
				return '';
			}
			foreach ( array( '/^rgba?\(([^)]+)\)$/i', '/^hsla?\(([^)]+)\)$/i' ) as $pattern ) {
				if ( preg_match( $pattern, $color, $m ) ) {
					$parts = array_map( 'trim', explode( ',', $m[1] ) );
					if ( count( $parts ) >= 4 && (float) $parts[3] <= 0 ) {
						return '';
					}
					break;
				}
			}
			return $color;
		}

		/**
		 * Unwrap Bricks responsive control values (desktop → tablet → mobile).
		 *
		 * @param mixed $value Control value.
		 * @return mixed
		 */
		public static function ect_bricks_resolve_responsive_value( $value ) {
			if ( ! is_array( $value ) ) {
				return $value;
			}

			// Bricks color objects — never unwrap as breakpoints.
			if ( isset( $value['hex'] ) || isset( $value['rgb'] ) || isset( $value['raw'] ) || isset( $value['id'] ) ) {
				return $value;
			}

			// Bricks font-size / spacing: size and unit can each be responsive.
			if ( isset( $value['size'] ) || isset( $value['unit'] ) ) {
				$size = array_key_exists( 'size', $value )
					? self::ect_bricks_resolve_responsive_value( $value['size'] )
					: '';
				if ( $size === null || $size === '' || $size === array() ) {
					return '';
				}
				$unit = array_key_exists( 'unit', $value )
					? self::ect_bricks_resolve_responsive_value( $value['unit'] )
					: 'px';
				if ( $unit === null || $unit === '' || $unit === array() ) {
					$unit = 'px';
				}

				return array(
					'size' => $size,
					'unit' => $unit,
				);
			}

			foreach ( array( 'desktop', 'tablet', 'mobile', 'default' ) as $breakpoint ) {
				if ( ! array_key_exists( $breakpoint, $value ) ) {
					continue;
				}
				$resolved = self::ect_bricks_resolve_responsive_value( $value[ $breakpoint ] );
				if ( $resolved === null || $resolved === '' || $resolved === array() ) {
					continue;
				}

				return $resolved;
			}

			return $value;
		}

		/**
		 * Desktop-only Bricks typography tree (ignores tablet/mobile sibling keys).
		 *
		 * Bricks stores responsive typography as separate keys such as
		 * `ect_bricks_typography:tablet`. Merging those siblings can blank out
		 * desktop font-size when a breakpoint row exists but is empty.
		 *
		 * @param array<string,mixed> $settings  Widget or repeater row settings.
		 * @param string              $base_key Typography setting key.
		 * @return array<string,mixed>
		 */
		public static function ect_bricks_typography_desktop_tree( array $settings, $base_key ) {
			$prefix = (string) $base_key;

			if ( isset( $settings[ $prefix ] ) && is_array( $settings[ $prefix ] ) && $settings[ $prefix ] !== array() ) {
				return $settings[ $prefix ];
			}

			$desktop_key = $prefix . ':desktop';
			if ( isset( $settings[ $desktop_key ] ) && is_array( $settings[ $desktop_key ] ) && $settings[ $desktop_key ] !== array() ) {
				return $settings[ $desktop_key ];
			}

			return array();
		}


		/**
		 * Allowed CSS length units for inline style vars (editor-controlled sizes).
		 *
		 * @var string[]
		 */
		private const CSS_LENGTH_UNITS = array(
			'px',
			'em',
			'rem',
			'%',
			'vh',
			'vw',
			'vmin',
			'vmax',
			'ch',
			'ex',
			'pt',
			'pc',
			'cm',
			'mm',
			'in',
		);

		/**
		 * Parse a Bricks size/unit control value into numeric size + unit strings.
		 *
		 * @param mixed $value Control value (array or scalar).
		 * @return array{size:string,unit:string}|null Null when size is missing/invalid.
		 */
		private static function ect_bricks_parse_size_unit( $value ) {
			if ( $value === null || $value === false || $value === '' ) {
				return null;
			}
			if ( ! is_array( $value ) ) {
				return null;
			}
			if ( ! isset( $value['size'] ) && ! isset( $value['unit'] ) ) {
				return null;
			}

			$size_raw = $value['size'] ?? '';
			if ( is_array( $size_raw ) ) {
				$size_raw = self::ect_bricks_resolve_responsive_value( $size_raw );
			}
			$size = trim( (string) $size_raw );
			if ( $size === '' || ! preg_match( '/^-?\d+(\.\d+)?$/', $size ) ) {
				return null;
			}

			$unit_raw = $value['unit'] ?? '';
			if ( is_array( $unit_raw ) ) {
				$unit_raw = self::ect_bricks_resolve_responsive_value( $unit_raw );
			}
			$unit = strtolower( trim( (string) $unit_raw ) );
			if ( $unit === '-' || $unit === 'none' ) {
				$unit = '';
			}

			return array(
				'size' => $size,
				'unit' => $unit,
			);
		}

		/**
		 * Bricks spacing/number value → safe CSS length (numeric + allowlisted unit).
		 *
		 * @param mixed $value Control value.
		 * @return string
		 */
		public static function ect_bricks_css_size_value( $value ) {
			if ( $value === null || $value === false || $value === '' ) {
				return '';
			}
			$value = self::ect_bricks_resolve_responsive_value( $value );
			if ( is_array( $value ) ) {
				$parsed = self::ect_bricks_parse_size_unit( $value );
				if ( null === $parsed ) {
					return '';
				}
				$unit = $parsed['unit'] !== '' ? $parsed['unit'] : 'px';
				if ( ! in_array( $unit, self::CSS_LENGTH_UNITS, true ) ) {
					return '';
				}

				return $parsed['size'] . $unit;
			}
			if ( is_numeric( $value ) ) {
				return (string) $value . 'px';
			}

			$str = trim( (string) $value );
			if ( $str === '' ) {
				return '';
			}
			if ( preg_match( '/^-?\d+(\.\d+)?$/', $str ) ) {
				return $str . 'px';
			}
			if ( preg_match( '/^(-?\d+(?:\.\d+)?)([a-z%]+)$/i', $str, $m ) ) {
				$unit = strtolower( $m[2] );
				if ( in_array( $unit, self::CSS_LENGTH_UNITS, true ) ) {
					return $m[1] . $unit;
				}
			}

			return '';
		}

		/**
		 * Collapse four CSS side values into 1/2/4-value shorthand.
		 *
		 * @param string[] $parts Exactly four CSS length values (empty → treated as "0").
		 * @return string
		 */
		private static function ect_bricks_collapse_box_shorthand( array $parts ) {
			if ( count( $parts ) !== 4 ) {
				return '';
			}
			foreach ( $parts as $index => $part ) {
				if ( $part === '' ) {
					$parts[ $index ] = '0';
				}
			}
			if ( count( array_unique( $parts, SORT_STRING ) ) === 1 ) {
				return $parts[0];
			}
			if ( $parts[0] === $parts[2] && $parts[1] === $parts[3] ) {
				return $parts[0] . ' ' . $parts[1];
			}

			return implode( ' ', $parts );
		}

		/**
		 * Bricks spacing control → CSS padding/margin shorthand.
		 *
		 * @param mixed $value Control value.
		 * @return string
		 */
		public static function ect_bricks_spacing_to_css( $value ) {
			if ( ! is_array( $value ) || $value === array() ) {
				return '';
			}

			$parts = array();
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$parts[] = self::ect_bricks_css_size_value( $value[ $side ] ?? '' );
			}

			if ( implode( '', $parts ) === '' ) {
				return '';
			}

			return self::ect_bricks_collapse_box_shorthand( $parts );
		}

		/**
		 * Bricks border control → CSS border shorthand (e.g. 4px solid #000).
		 *
		 * @param mixed $value Control value.
		 * @return string
		 */
		public static function ect_bricks_border_to_css( $value ) {
			$value = self::ect_bricks_resolve_responsive_value( $value );
			if ( ! is_array( $value ) || $value === array() ) {
				return '';
			}

			$style = isset( $value['style'] ) ? sanitize_key( (string) $value['style'] ) : '';
			if ( $style === '' || $style === 'none' ) {
				return 'none';
			}

			$allowed_styles = array( 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' );
			if ( ! in_array( $style, $allowed_styles, true ) ) {
				$style = 'solid';
			}

			$color = self::ect_bricks_norm_hover_paint_color( $value['color'] ?? '' );
			if ( $color === '' ) {
				$color = 'currentColor';
			}

			$width_val = $value['width'] ?? null;
			$width_css = '';
			if ( is_array( $width_val ) ) {
				$sides = array();
				foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
					$side_val = self::ect_bricks_css_size_value( $width_val[ $side ] ?? '' );
					$sides[]  = $side_val !== '' ? $side_val : '0';
				}
				if ( $sides === array( '0', '0', '0', '0' ) ) {
					return 'none';
				}
				$width_css = self::ect_bricks_collapse_box_shorthand( $sides );
			} else {
				$width_css = self::ect_bricks_css_size_value( $width_val );
				if ( $width_css === '' ) {
					$width_css = '0';
				}
			}

			return $width_css . ' ' . $style . ' ' . $color;
		}

		/**
		 * Bricks dimensions control → CSS border-radius shorthand.
		 *
		 * @param mixed $value Control value.
		 * @return string
		 */
		public static function ect_bricks_dimensions_to_css( $value ) {
			if ( ! is_array( $value ) || $value === array() ) {
				return '';
			}

			$key_groups = array(
				array( 'top', 'right', 'bottom', 'left' ),
				array( 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' ),
			);

			foreach ( $key_groups as $keys ) {
				$parts = array();
				foreach ( $keys as $key ) {
					$parts[] = self::ect_bricks_css_size_value( $value[ $key ] ?? '' );
				}
				if ( implode( '', $parts ) === '' ) {
					continue;
				}

				return self::ect_bricks_collapse_box_shorthand( $parts );
			}

			return '';
		}

		/**
		 * Allowed hover animation slugs (CSS modifiers; labels live in controls).
		 *
		 * @return string[]
		 */
		public static function ect_bricks_hover_animation_slugs() {
			return array(
				'fade_in_up',
				'fade_in_right',
				'fade_in_down',
				'fade_in_left',
				'zoom_in',
				'zoom_out',
			);
		}

		/**
		 * Sanitize a hover animation setting to a known slug, `none`, or empty.
		 *
		 * @param mixed $value Raw control value.
		 * @return string
		 */
		public static function ect_bricks_sanitize_hover_animation_slug( $value ) {
			$slug = sanitize_key( (string) $value );
			if ( $slug === '' || $slug === 'default' ) {
				return '';
			}
			if ( $slug === 'none' || in_array( $slug, self::ect_bricks_hover_animation_slugs(), true ) ) {
				return $slug;
			}
			return '';
		}

		/**
		 * Allowed hover text-decoration CSS values (labels live in controls).
		 *
		 * @return string[]
		 */
		public static function ect_bricks_text_decoration_slugs() {
			return array( 'none', 'underline', 'overline', 'line-through' );
		}

		/**
		 * Sanitize a text-decoration setting to a known slug or empty.
		 *
		 * @param mixed $value Raw control value.
		 * @return string
		 */
		public static function ect_bricks_sanitize_text_decoration_slug( $value ) {
			$slug = sanitize_key( (string) $value );

			return in_array( $slug, self::ect_bricks_text_decoration_slugs(), true ) ? $slug : '';
		}
	}
}
