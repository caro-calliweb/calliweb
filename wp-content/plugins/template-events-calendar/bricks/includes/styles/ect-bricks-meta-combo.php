<?php
/**
 * ECT_Bricks_Meta_Combo service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Meta_Combo', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Meta_Combo {

		/** @var array<string,string[]>|null */
		private static $meta_combo_order_cache = null;

		/**
		 * Normalize a layout repeater row (meta combo + standalone venue/date/cost parts).
		 *
		 * @param array<string,mixed> $row Repeater row.
		 * @return array<string,mixed>
		 */
		public static function ect_bricks_normalize_layout_row( array $row ) {
			$part     = (string) ( $row['part'] ?? '' );
			$is_combo = self::ect_bricks_is_meta_combo_slug( $part );

			if ( $part === 'venue' || ( $is_combo && self::ect_bricks_meta_combo_has_segment( $part, 'venue' ) ) ) {
				$display = (string) ( $row['venue_display'] ?? '' );
				if ( $display === '' ) {
					$row['venue_display'] = $part === 'venue' ? 'full_details' : 'name_and_city';
				} elseif ( $display === 'name_and_address' ) {
					$row['venue_display'] = 'full_details';
				}
			}
			if (
				( $part === 'date' || ( $is_combo && self::ect_bricks_meta_combo_has_segment( $part, 'time' ) ) )
				&& (string) ( $row['date_display'] ?? '' ) === ''
			) {
				$row['date_display'] = 'time';
			}

			return $row;
		}

		public static function ect_bricks_meta_combo_options_all() {
			return self::ect_bricks_meta_combo_options( self::ect_bricks_meta_combo_all_slugs() );
		}

		private static function ect_bricks_meta_combo_permutations( array $segments ) {
			$segments = array_values( $segments );
			$count    = count( $segments );
			if ( $count === 0 ) {
				return array();
			}
			if ( $count === 1 ) {
				return array( $segments );
			}

			$out = array();
			foreach ( $segments as $i => $seg ) {
				$rest = $segments;
				array_splice( $rest, $i, 1 );
				foreach ( self::ect_bricks_meta_combo_permutations( $rest ) as $perm ) {
					$out[] = array_merge( array( $seg ), $perm );
				}
			}

			return $out;
		}

		public static function ect_bricks_meta_combo_label( array $order ) {
			$map   = array(
				'venue' => class_exists( 'ECT_Bricks_Part_Options', false )
					? \ECT_Bricks_Part_Options::ect_bricks_builder_select_label( __( 'Venue', 'template-events-calendar' ) )
					: __( 'Venue', 'template-events-calendar' ),
				'time'  => class_exists( 'ECT_Bricks_Part_Options', false )
					? \ECT_Bricks_Part_Options::ect_bricks_builder_select_label( __( 'Time', 'template-events-calendar' ) )
					: __( 'Time', 'template-events-calendar' ),
				'cost'  => class_exists( 'ECT_Bricks_Part_Options', false )
					? \ECT_Bricks_Part_Options::ect_bricks_builder_select_label( __( 'Cost', 'template-events-calendar' ) )
					: __( 'Cost', 'template-events-calendar' ),
			);
			$parts = array();
			foreach ( $order as $seg ) {
				if ( isset( $map[ $seg ] ) ) {
					$parts[] = $map[ $seg ];
				}
			}

			return implode( ' + ', $parts );
		}

		public static function ect_bricks_meta_combo_segments() {
			return array( 'venue', 'time', 'cost' );
		}

		public static function ect_bricks_meta_combo_all_slugs() {
			return self::ect_bricks_meta_combo_slugs_style2();
		}

		public static function ect_bricks_meta_combo_has_segment( $slug, $segment ) {
			return in_array( (string) $segment, self::ect_bricks_meta_combo_order( $slug ), true );
		}

		/**
		 * Whether a part slug (UI or cleaned) represents cost output.
		 *
		 * @param string $part Part slug.
		 * @return bool
		 */
		public static function ect_bricks_part_has_cost_segment( $part ) {
			$part = (string) $part;
			if ( $part === 'event_cost' ) {
				return true;
			}

			return self::ect_bricks_is_meta_combo_slug( $part ) && self::ect_bricks_meta_combo_has_segment( $part, 'cost' );
		}

		/**
		 * Whether a part slug should use the venue/pin meta icon.
		 *
		 * @param string $part Cleaned or UI part slug.
		 * @return bool
		 */
		public static function ect_bricks_part_has_venue_segment( $part ) {
			$part = (string) $part;
			if ( in_array( $part, self::ect_bricks_pin_icon_part_slugs(), true ) ) {
				return true;
			}

			return self::ect_bricks_is_meta_combo_slug( $part ) && self::ect_bricks_meta_combo_has_segment( $part, 'venue' );
		}

		/**
		 * @return string[]
		 */
		private static function ect_bricks_pin_icon_part_slugs() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_venue_pin_icon_part_slugs()
				: array( 'venue' );
		}

		/**
		 * Whether a part slug should use the organizer/user meta icon.
		 *
		 * @param string $part Cleaned or UI part slug.
		 * @return bool
		 */
		public static function ect_bricks_part_has_organizer_segment( $part ) {
			$part = (string) $part;
			return in_array( $part, self::ect_bricks_user_icon_part_slugs(), true );
		}

		/**
		 * @return string[]
		 */
		private static function ect_bricks_user_icon_part_slugs() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_organizer_user_icon_part_slugs()
				: array( 'organizer', 'organizer_email', 'organizer_phone', 'organizer_website' );
		}

		public static function ect_bricks_meta_combo_options( array $slugs ) {
			$options = array();
			foreach ( $slugs as $slug ) {
				$order = self::ect_bricks_meta_combo_order( $slug );
				if ( $order !== array() ) {
					$options[ $slug ] = self::ect_bricks_meta_combo_label( $order );
				}
			}

			return $options;
		}

		public static function ect_bricks_is_meta_combo_slug( $slug ) {
			$order = self::ect_bricks_meta_combo_order( $slug );
			if ( count( $order ) < 2 ) {
				return false;
			}

			return self::ect_bricks_meta_combo_slug( $order ) === (string) $slug;
		}

		public static function ect_bricks_meta_combo_slugs_with_segment( $segment ) {
			$out = array();
			foreach ( self::ect_bricks_meta_combo_all_slugs() as $slug ) {
				if ( self::ect_bricks_meta_combo_has_segment( $slug, $segment ) ) {
					$out[] = $slug;
				}
			}

			return $out;
		}

		public static function ect_bricks_meta_combo_order( $slug ) {
			$slug = (string) $slug;
			if ( ! is_array( self::$meta_combo_order_cache ) ) {
				self::$meta_combo_order_cache = array();
			}
			if ( array_key_exists( $slug, self::$meta_combo_order_cache ) ) {
				return self::$meta_combo_order_cache[ $slug ];
			}

			$order = array();
			$rest  = $slug;

			while ( $rest !== '' ) {
				$matched = false;
				foreach ( self::ect_bricks_meta_combo_segments() as $seg ) {
					if ( $rest === $seg ) {
						$order[] = $seg;
						$rest    = '';
						$matched = true;
						break;
					}
					$prefix = $seg . '_';
					if ( strpos( $rest, $prefix ) === 0 ) {
						$order[] = $seg;
						$rest    = substr( $rest, strlen( $prefix ) );
						$matched = true;
						break;
					}
				}
				if ( ! $matched ) {
					self::$meta_combo_order_cache[ $slug ] = array();
					return array();
				}
			}

			self::$meta_combo_order_cache[ $slug ] = $order;

			return $order;
		}

		public static function ect_bricks_meta_combo_slugs_for_segments( array $segments ) {
			$slugs = array();
			foreach ( self::ect_bricks_meta_combo_permutations( $segments ) as $order ) {
				$slugs[] = self::ect_bricks_meta_combo_slug( $order );
			}

			return $slugs;
		}

		public static function ect_bricks_meta_combo_slugs_style2() {
			static $slugs = null;
			if ( is_array( $slugs ) ) {
				return $slugs;
			}

			$slugs = array_values(
				array_unique(
					array_merge(
						self::ect_bricks_meta_combo_slugs_for_segments( array( 'venue', 'time', 'cost' ) ),
						self::ect_bricks_meta_combo_slugs_for_segments( array( 'venue', 'time' ) ),
						self::ect_bricks_meta_combo_slugs_for_segments( array( 'venue', 'cost' ) ),
						self::ect_bricks_meta_combo_slugs_for_segments( array( 'time', 'cost' ) )
					)
				)
			);

			return $slugs;
		}

		public static function ect_bricks_meta_combo_slug( array $order ) {
			return implode( '_', $order );
		}
	}
}
