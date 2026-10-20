<?php
/**
 * Part Chrome typography helpers (trait).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! trait_exists( 'ECT_Bricks_Part_Chrome_Typography', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedTraitFound
	trait ECT_Bricks_Part_Chrome_Typography {

		/**
		 * Collect typography trees saved on a repeater row (desktop + breakpoint keys).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return array<int,array<string,mixed>>
		 */
		private static function ect_bricks_typography_roots_from_item( array $item ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';
			$key  = 'ect_bricks_typography';
			if (
				class_exists( 'ECT_Bricks_Styles', false )
				&& \ECT_Bricks_Styles::ect_bricks_is_meta_combo_slug( $part )
			) {
				$key = 'ect_bricks_typography_combo';
			}

			$desktop = ECT_Bricks_Value_Utils::ect_bricks_typography_desktop_tree( $item, $key );
			if ( $desktop === array() && $key === 'ect_bricks_typography_combo' ) {
				$desktop = ECT_Bricks_Value_Utils::ect_bricks_typography_desktop_tree( $item, 'ect_bricks_typography' );
			}
			if ( $desktop === array() ) {
				return array();
			}

			return array( $desktop );
		}

		/**
		 * Combo meta rows (venue+time+cost, etc.) do not support typography text-decoration.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ect_bricks_part_typography_skips_text_decoration( array $item ) {
			$part = isset( $item['part'] ) ? (string) $item['part'] : '';

			return class_exists( 'ECT_Bricks_Styles', false )
				&& \ECT_Bricks_Styles::ect_bricks_is_meta_combo_slug( $part );
		}

		/**
		 * Read font-size from a desktop typography tree before walking nested keys.
		 *
		 * @param array<string,mixed> $tree Desktop typography control value.
		 * @return mixed|null
		 */
		private static function ect_bricks_typography_tree_font_size_raw( array $tree ) {
			foreach ( array( 'font-size', 'fontSize' ) as $key ) {
				if ( array_key_exists( $key, $tree ) ) {
					return $tree[ $key ];
				}
			}

			return null;
		}

		/**
		 * Walk a repeater typography tree; visitor returns non-null to stop.
		 *
		 * @param array<string,mixed> $item    Repeater row.
		 * @param callable            $visitor function( string $key, mixed $value ): mixed|null
		 * @return mixed|null
		 */
		private static function ect_bricks_typography_walk( array $item, callable $visitor ) {
			$roots = self::ect_bricks_typography_roots_from_item( $item );
			if ( $roots === array() ) {
				return null;
			}

			foreach ( $roots as $root ) {
				$stack = array( $root );
				while ( $stack !== array() ) {
					$current = array_pop( $stack );
					if ( ! is_array( $current ) ) {
						continue;
					}

					foreach ( $current as $key => $value ) {
						if ( is_array( $value ) ) {
							$found = $visitor( (string) $key, $value );
							if ( $found !== null ) {
								return $found;
							}
							$stack[] = $value;
							continue;
						}

						$found = $visitor( (string) $key, $value );
						if ( $found !== null ) {
							return $found;
						}
					}
				}
			}

			return null;
		}

		/**
		 * Resolved typography values for a repeater row.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return array{color:string,font_size:string,text_transform:string,text_decoration:string}
		 */
		private static function ect_bricks_typography_state( array $item ): array {
			static $cache = array();

			$row_id = isset( $item['id'] ) ? (string) $item['id'] : '';
			if ( $row_id !== '' && isset( $cache[ $row_id ] ) ) {
				return $cache[ $row_id ];
			}

			$color = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color(
				ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $item['ect_bricks_text_color'] ?? '' )
			);
			if ( $color === '' ) {
				$walked = self::ect_bricks_typography_walk(
					$item,
					static function ( $key, $value ) {
						if ( strpos( (string) $key, 'color' ) === false ) {
							return null;
						}
						$normalized = ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color(
							ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $value )
						);

						return $normalized !== '' ? $normalized : null;
					}
				);
				$color = is_string( $walked ) ? $walked : '';
			}

			$font_size = '';
			foreach ( self::ect_bricks_typography_roots_from_item( $item ) as $root ) {
				$raw = self::ect_bricks_typography_tree_font_size_raw( $root );
				if ( $raw !== null ) {
					$size = ECT_Bricks_Value_Utils::ect_bricks_css_size_value( $raw );
					if ( $size !== '' ) {
						$font_size = $size;
						break;
					}
				}
			}
			if ( $font_size === '' ) {
				$raw = self::ect_bricks_typography_walk(
					$item,
					static function ( $key, $value ) {
						if ( ! in_array( (string) $key, array( 'font-size', 'fontSize' ), true ) ) {
							return null;
						}

						return $value;
					}
				);
				$font_size = ( $raw === null ) ? '' : ECT_Bricks_Value_Utils::ect_bricks_css_size_value( $raw );
			}

			$tf_raw = self::ect_bricks_typography_walk(
				$item,
				static function ( $key, $value ) {
					if ( ! in_array( (string) $key, array( 'text-transform', 'textTransform', 'text_transform' ), true ) ) {
						return null;
					}

					return $value;
				}
			);
			$text_transform = '';
			if ( $tf_raw !== null ) {
				$str            = sanitize_key( (string) ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $tf_raw ) );
				$allowed        = array( 'none', 'capitalize', 'uppercase', 'lowercase', 'full-width', 'full-size-kana' );
				$text_transform = in_array( $str, $allowed, true ) ? $str : '';
			}

			$td_raw = self::ect_bricks_typography_walk(
				$item,
				static function ( $key, $value ) {
					if ( ! in_array( (string) $key, array( 'text-decoration', 'textDecoration', 'text_decoration' ), true ) ) {
						return null;
					}

					return $value;
				}
			);
			$text_decoration = '';
			if ( $td_raw !== null ) {
				$text_decoration = ECT_Bricks_Value_Utils::ect_bricks_sanitize_text_decoration_slug(
					ECT_Bricks_Value_Utils::ect_bricks_resolve_responsive_value( $td_raw )
				);
			}
			if ( self::ect_bricks_part_typography_skips_text_decoration( $item ) ) {
				$text_decoration = '';
			}

			$state = array(
				'color'            => $color,
				'font_size'        => $font_size,
				'text_transform'   => $text_transform,
				'text_decoration'  => $text_decoration,
			);

			if ( $row_id !== '' ) {
				$cache[ $row_id ] = $state;
			}

			return $state;
		}

		/**
		 * Whether a repeater row defines any typography color value.
		 *
		 * Used to suppress layout fallback button hover colors when the user already
		 * chose a custom text color in the Typography control.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ect_bricks_row_has_typography_color( array $item ) {
			return self::ect_bricks_typography_color_value( $item ) !== '';
		}

		/**
		 * First visible typography color from a repeater row (normalized CSS color).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		private static function ect_bricks_typography_color_value( array $item ) {
			return self::ect_bricks_typography_state( $item )['color'];
		}

		/**
		 * Whether a repeater row defines Style tab Background.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ect_bricks_row_has_part_background( array $item ) {
			return ECT_Bricks_Value_Utils::ect_bricks_norm_hover_paint_color( $item['ect_bricks_background'] ?? '' ) !== '';
		}

		/**
		 * Whether a repeater row defines any typography font-size value.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ect_bricks_row_has_typography_font_size( array $item ) {
			return self::ect_bricks_typography_font_size_value( $item ) !== '';
		}

		/**
		 * Whether a repeater row defines any typography text-transform value.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ect_bricks_row_has_typography_text_transform( array $item ) {
			return self::ect_bricks_typography_text_transform_value( $item ) !== '';
		}

		/**
		 * Whether a repeater row defines any typography text-decoration value.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return bool
		 */
		private static function ect_bricks_row_has_typography_text_decoration( array $item ) {
			return self::ect_bricks_typography_text_decoration_value( $item ) !== '';
		}

		/**
		 * First visible typography font-size from a repeater row (normalized CSS length).
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		private static function ect_bricks_typography_font_size_value( array $item ) {
			return self::ect_bricks_typography_state( $item )['font_size'];
		}

		/**
		 * First visible typography text-transform from a repeater row.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		private static function ect_bricks_typography_text_transform_value( array $item ) {
			return self::ect_bricks_typography_state( $item )['text_transform'];
		}

		/**
		 * First visible typography text-decoration from a repeater row.
		 *
		 * @param array<string,mixed> $item Repeater row.
		 * @return string
		 */
		private static function ect_bricks_typography_text_decoration_value( array $item ) {
			return self::ect_bricks_typography_state( $item )['text_decoration'];
		}
	}
}
