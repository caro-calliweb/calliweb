<?php
/**
 * Event venue, organizer, and detail-field data.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Event_Data', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Event_Data {

		/** @var array<int,int> */
		private static $venue_id_cache = array();

		/** @var array<int,string> */
		private static $venue_name_cache = array();

		/** @var array<int,int> */
		private static $organizer_id_cache = array();

		/** @var array<int,array{start:string,end:string}> */
		private static $date_cache = array();

		private static function venue_tribe_meta_map() {
			return array(
				'venue_street'  => array( 'tribe_get_address', '_VenueAddress' ),
				'venue_city'    => array( 'tribe_get_city', '_VenueCity' ),
				'venue_zip'     => array( 'tribe_get_zip', '_VenueZip' ),
				'venue_country' => array( 'tribe_get_country', '_VenueCountry' ),
				'venue_phone'   => array( 'tribe_get_phone', '_VenuePhone' ),
			);
		}

		private static function ect_bricks_resolve_venue_tribe_meta( $event_id, $tribe_fn, $meta_key ) {
			return self::ect_bricks_resolve_tribe_or_related_meta(
				$event_id,
				$tribe_fn,
				$meta_key,
				array( self::class, 'ect_bricks_event_meta_venue_id' )
			);
		}

		private static function organizer_tribe_meta_map() {
			return array(
				'organizer_email'   => array( 'tribe_get_organizer_email', '_OrganizerEmail' ),
				'organizer_phone'   => array( 'tribe_get_organizer_phone', '_OrganizerPhone' ),
				'organizer_website' => array( 'tribe_get_organizer_website_url', '_OrganizerWebsite' ),
			);
		}

		private static function ect_bricks_resolve_organizer_field( $event_id, $tribe_fn, $meta_key ) {
			return self::ect_bricks_resolve_tribe_or_related_meta(
				$event_id,
				$tribe_fn,
				$meta_key,
				array( self::class, 'ect_bricks_event_meta_organizer_id' )
			);
		}

		/**
		 * Call a Tribe helper, then fall back to related-post meta.
		 *
		 * @param int                  $event_id Event post ID.
		 * @param string               $tribe_fn Tribe function name.
		 * @param string               $meta_key Related post meta key.
		 * @param callable(int):int    $id_fn    Related post ID resolver.
		 * @return string
		 */
		private static function ect_bricks_resolve_tribe_or_related_meta( $event_id, $tribe_fn, $meta_key, $id_fn ) {
			if ( is_string( $tribe_fn ) && $tribe_fn !== '' && function_exists( $tribe_fn ) ) {
				$t = trim( (string) call_user_func( $tribe_fn, $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$related_id = (int) call_user_func( $id_fn, $event_id );
			if ( $related_id < 1 ) {
				return '';
			}

			return trim( (string) get_post_meta( $related_id, $meta_key, true ) );
		}

		/** Detail part slug → callable resolver. */
		private static function detail_resolvers() {
			static $map = null;
			if ( is_array( $map ) ) {
				return $map;
			}

			// Custom handlers; remaining detail slugs come from tribe meta maps below.
			// Slug catalog: ECT_Bricks_Part_Options::ect_bricks_detail_part_slugs().
			$map = array(
				'venue_full_address' => array( self::class, 'ect_bricks_resolve_detail_venue_full_address' ),
				'venue_state'        => array( self::class, 'ect_bricks_resolve_detail_venue_state' ),
				'venue_website'      => array( self::class, 'ect_bricks_resolve_detail_venue_website' ),
			);

			foreach ( self::organizer_tribe_meta_map() as $slug => $cfg ) {
				$map[ $slug ] = static function ( $event_id ) use ( $cfg ) {
					return self::ect_bricks_resolve_organizer_field( $event_id, $cfg[0], $cfg[1] );
				};
			}

			foreach ( self::venue_tribe_meta_map() as $slug => $cfg ) {
				$map[ $slug ] = static function ( $event_id ) use ( $cfg ) {
					return self::ect_bricks_resolve_venue_tribe_meta( $event_id, $cfg[0], $cfg[1] );
				};
			}

			return $map;
		}

		private static function ect_bricks_resolve_detail_venue_full_address( $event_id ) {
			return self::ect_bricks_venue_full_address_text( $event_id );
		}

		private static function ect_bricks_resolve_detail_venue_state( $event_id ) {
			if ( function_exists( 'tribe_get_province' ) ) {
				$t = trim( (string) \tribe_get_province( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			if ( function_exists( 'tribe_get_state' ) ) {
				$t = trim( (string) \tribe_get_state( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ect_bricks_event_meta_venue_id( $event_id );
			if ( $vid < 1 ) {
				return '';
			}
			return self::ect_bricks_venue_state_meta( $vid );
		}

		private static function ect_bricks_resolve_detail_venue_website( $event_id ) {
			if ( function_exists( 'tribe_get_venue_website_url' ) ) {
				$t = trim( (string) \tribe_get_venue_website_url( $event_id ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			$vid = self::ect_bricks_event_meta_venue_id( $event_id );
			if ( $vid > 0 ) {
				$t = trim( (string) get_post_meta( $vid, '_VenueURL', true ) );
				if ( $t !== '' ) {
					return $t;
				}
			}
			return '';
		}

		/** Plain-text value for a detail part slug (caller escapes for HTML). */
		public static function ect_bricks_part_detail_text( $event_id, $part ) {
			$event_id = (int) $event_id;
			$part     = (string) $part;
			if ( $event_id < 1 ) {
				return '';
			}

			$resolvers = self::detail_resolvers();
			if ( ! isset( $resolvers[ $part ] ) ) {
				return '';
			}

			return (string) call_user_func( $resolvers[ $part ], $event_id );
		}

		/** Cached `_EventStartDate` / `_EventEndDate` per event per request. */
		public static function ect_bricks_event_meta_dates( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return array(
					'start' => '',
					'end'   => '',
				);
			}
			if ( ! array_key_exists( $event_id, self::$date_cache ) ) {
				self::$date_cache[ $event_id ] = array(
					'start' => (string) get_post_meta( $event_id, '_EventStartDate', true ),
					'end'   => (string) get_post_meta( $event_id, '_EventEndDate', true ),
				);
			}
			return self::$date_cache[ $event_id ];
		}

		/** Raw `_EventStartDate` string. */
		public static function ect_bricks_event_start_date_raw( $event_id ) {
			return self::ect_bricks_event_meta_dates( $event_id )['start'];
		}

		/** Start and end Unix timestamps for an event. */
		public static function ect_bricks_date_bounds( $post_id ) {
			$post_id = absint( $post_id );
			if ( $post_id < 1 ) {
				return array( false, false );
			}
			$dates    = self::ect_bricks_event_meta_dates( $post_id );
			$start_ts = ! empty( $dates['start'] ) ? strtotime( $dates['start'] ) : false;
			$end_ts   = ! empty( $dates['end'] ) ? strtotime( $dates['end'] ) : $start_ts;
			if ( ! $start_ts ) {
				return array( false, false );
			}
			if ( ! $end_ts ) {
				$end_ts = $start_ts;
			}
			return array( $start_ts, $end_ts );
		}

		/** Cached `_EventVenueID` (0 when missing). */
		private static function ect_bricks_event_meta_venue_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( ! array_key_exists( $event_id, self::$venue_id_cache ) ) {
				self::$venue_id_cache[ $event_id ] = (int) get_post_meta( $event_id, '_EventVenueID', true );
			}
			return self::$venue_id_cache[ $event_id ];
		}

		/** Cached `_EventOrganizerID` (0 when missing). */
		private static function ect_bricks_event_meta_organizer_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( ! array_key_exists( $event_id, self::$organizer_id_cache ) ) {
				self::$organizer_id_cache[ $event_id ] = (int) get_post_meta( $event_id, '_EventOrganizerID', true );
			}
			return self::$organizer_id_cache[ $event_id ];
		}

		public static function ect_bricks_venue_id( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return 0;
			}
			if ( function_exists( 'tribe_get_venue_id' ) ) {
				$venue_id = (int) \tribe_get_venue_id( $event_id );
				if ( $venue_id > 0 ) {
					return $venue_id;
				}
			}
			return self::ect_bricks_event_meta_venue_id( $event_id );
		}

		public static function ect_bricks_venue_name( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			if ( array_key_exists( $event_id, self::$venue_name_cache ) ) {
				return self::$venue_name_cache[ $event_id ];
			}
			$venue = '';
			if ( function_exists( 'tribe_get_venue' ) ) {
				$venue = trim( (string) \tribe_get_venue( $event_id ) );
			}
			if ( $venue === '' ) {
				$venue_id = self::ect_bricks_event_meta_venue_id( $event_id );
				if ( $venue_id ) {
					$venue = trim( (string) get_the_title( $venue_id ) );
				}
			}
			self::$venue_name_cache[ $event_id ] = $venue;
			return $venue;
		}

		private static function ect_bricks_venue_state_meta( $venue_id ) {
			$venue_id = (int) $venue_id;
			if ( $venue_id < 1 ) {
				return '';
			}
			$s = get_post_meta( $venue_id, '_VenueStateProvince', true );
			if ( $s === '' || $s === null ) {
				$s = get_post_meta( $venue_id, '_VenueState', true );
			}
			return trim( (string) $s );
		}

		private static function ect_bricks_resolve_tribe_full_address( array $ids, $strip_venue_name_for_event_id = 0 ) {
			if ( ! function_exists( 'tribe_get_full_address' ) ) {
				return '';
			}
			foreach ( $ids as $try_id ) {
				$raw = (string) \tribe_get_full_address( $try_id );
				$raw = preg_replace( '/<br\s*\/?>/i', ', ', $raw );
				$t   = trim( wp_strip_all_tags( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ) ) );
				if ( $t === '' ) {
					continue;
				}
				if ( $strip_venue_name_for_event_id > 0 ) {
					$name = self::ect_bricks_venue_name( $strip_venue_name_for_event_id );
					if ( $name !== '' && strcasecmp( $t, $name ) === 0 ) {
						continue;
					}
					if ( $name !== '' && stripos( $t, $name ) === 0 ) {
						$t = trim( preg_replace( '/^' . preg_quote( $name, '/' ) . '\s*,\s*/i', '', $t ) );
					}
					if ( $t === '' ) {
						continue;
					}
				}
				return $t;
			}
			return '';
		}

		private static function ect_bricks_venue_address_ids( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return array();
			}
			$ids = array( $event_id );
			$vid = self::ect_bricks_venue_id( $event_id );
			if ( $vid > 0 ) {
				array_unshift( $ids, $vid );
			}
			return array_values( array_unique( $ids ) );
		}

		private static function ect_bricks_trim_address_bits( array $parts ) {
			return array_filter( array_map( 'trim', $parts ) );
		}

		private static function ect_bricks_build_address_bits_from_venue_meta( $venue_id ) {
			$venue_id = (int) $venue_id;
			if ( $venue_id < 1 ) {
				return array();
			}
			$state = self::ect_bricks_venue_state_meta( $venue_id );

			return self::ect_bricks_trim_address_bits(
				array(
					(string) get_post_meta( $venue_id, '_VenueAddress', true ),
					(string) get_post_meta( $venue_id, '_VenueCity', true ),
					trim( (string) $state . ' ' . (string) get_post_meta( $venue_id, '_VenueZip', true ) ),
					(string) get_post_meta( $venue_id, '_VenueCountry', true ),
				)
			);
		}

		private static function ect_bricks_build_address_bits_from_event( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return array();
			}

			return self::ect_bricks_trim_address_bits(
				array(
					self::ect_bricks_part_detail_text( $event_id, 'venue_street' ),
					self::ect_bricks_part_detail_text( $event_id, 'venue_city' ),
					trim(
						self::ect_bricks_part_detail_text( $event_id, 'venue_state' )
						. ' '
						. self::ect_bricks_part_detail_text( $event_id, 'venue_zip' )
					),
					self::ect_bricks_part_detail_text( $event_id, 'venue_country' ),
				)
			);
		}

		private static function ect_bricks_join_name_with( $name, $second ) {
			$name   = trim( (string) $name );
			$second = trim( (string) $second );
			if ( $name === '' && $second === '' ) {
				return '';
			}
			if ( $name === '' ) {
				return $second;
			}
			if ( $second === '' ) {
				return $name;
			}
			return $name . ', ' . $second;
		}

		public static function ect_bricks_venue_address( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			$ids = self::ect_bricks_venue_address_ids( $event_id );
			$vid = self::ect_bricks_venue_id( $event_id );

			$address = self::ect_bricks_resolve_tribe_full_address( $ids, $event_id );
			if ( $address !== '' ) {
				return $address;
			}

			if ( $vid > 0 ) {
				$bits = self::ect_bricks_build_address_bits_from_venue_meta( $vid );
				if ( $bits !== array() ) {
					return implode( ', ', $bits );
				}
			}

			$bits = self::ect_bricks_build_address_bits_from_event( $event_id );
			return $bits !== array() ? implode( ', ', $bits ) : '';
		}

		public static function ect_bricks_venue_name_addr( $event_id ) {
			return self::ect_bricks_join_name_with(
				self::ect_bricks_venue_name( $event_id ),
				self::ect_bricks_venue_address( $event_id )
			);
		}

		public static function ect_bricks_venue_name_state( $event_id ) {
			return self::ect_bricks_join_name_with(
				self::ect_bricks_venue_name( $event_id ),
				self::ect_bricks_part_detail_text( $event_id, 'venue_state' )
			);
		}

		public static function ect_bricks_venue_name_city( $event_id ) {
			return self::ect_bricks_join_name_with(
				self::ect_bricks_venue_name( $event_id ),
				self::ect_bricks_part_detail_text( $event_id, 'venue_city' )
			);
		}

		public static function ect_bricks_venue_display_key( array $item, $skin = '' ) {
			unset( $skin );
			$display = isset( $item['venue_display'] ) ? (string) $item['venue_display'] : '';
			if ( $display === 'name_and_address' ) {
				return 'full_details';
			}
			if ( $display !== '' ) {
				return $display;
			}

			$part = (string) ( $item['part'] ?? '' );
			if (
				$part !== ''
				&& class_exists( 'ECT_Bricks_Meta_Combo', false )
				&& \ECT_Bricks_Meta_Combo::ect_bricks_is_meta_combo_slug( $part )
				&& \ECT_Bricks_Meta_Combo::ect_bricks_meta_combo_has_segment( $part, 'venue' )
			) {
				return 'name_and_city';
			}

			return 'full_details';
		}

		public static function ect_bricks_venue_uses_full( array $item, $skin = '' ) {
			return self::ect_bricks_venue_display_key( $item, $skin ) === 'full_details';
		}

		public static function ect_bricks_venue_text( $event_id, array $item, $skin = '' ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}

			$display = self::ect_bricks_venue_display_key( $item, $skin );
			if ( class_exists( 'ECT_Bricks_Part_Options', false ) ) {
				$detail_slug = \ECT_Bricks_Part_Options::ect_bricks_venue_display_detail_slug( $display );
				if ( $detail_slug !== '' ) {
					return self::ect_bricks_part_detail_text( $event_id, $detail_slug );
				}
			}

			if ( self::ect_bricks_venue_uses_full( $item, $skin ) ) {
				return self::ect_bricks_venue_name_addr( $event_id );
			}
			if ( $display === 'name_and_state' ) {
				return self::ect_bricks_venue_name_state( $event_id );
			}
			if ( $display === 'name_and_city' ) {
				return self::ect_bricks_venue_name_city( $event_id );
			}

			return self::ect_bricks_venue_name( $event_id );
		}

		private static function ect_bricks_venue_full_address_text( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}

			$address = self::ect_bricks_resolve_tribe_full_address( self::ect_bricks_venue_address_ids( $event_id ) );
			if ( $address !== '' ) {
				return $address;
			}

			$bits = self::ect_bricks_build_address_bits_from_event( $event_id );
			return $bits !== array() ? implode( ', ', $bits ) : '';
		}

		public static function ect_bricks_organizer_name( $event_id ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			$name = '';
			if ( function_exists( 'tribe_get_organizer' ) ) {
				$name = trim( (string) \tribe_get_organizer( $event_id ) );
			}
			if ( $name === '' ) {
				$oid = self::ect_bricks_event_meta_organizer_id( $event_id );
				if ( $oid ) {
					$name = trim( (string) get_the_title( $oid ) );
				}
			}
			return $name;
		}

		public static function ect_bricks_organizer_full( $event_id ) {
			$bits = array_filter(
				array_map(
					'trim',
					array(
						self::ect_bricks_organizer_name( $event_id ),
						self::ect_bricks_part_detail_text( $event_id, 'organizer_email' ),
						self::ect_bricks_part_detail_text( $event_id, 'organizer_phone' ),
						self::ect_bricks_part_detail_text( $event_id, 'organizer_website' ),
					)
				)
			);
			return implode( ', ', $bits );
		}

		public static function ect_bricks_organizer_uses_full( array $item ) {
			$display = isset( $item['organizer_display'] ) ? (string) $item['organizer_display'] : 'full_details';
			if ( $display === '' ) {
				$display = 'full_details';
			}
			return $display === 'full_details';
		}

		public static function ect_bricks_organizer_text( $event_id, array $item ) {
			$event_id = (int) $event_id;
			if ( $event_id < 1 ) {
				return '';
			}
			if ( self::ect_bricks_organizer_uses_full( $item ) ) {
				return self::ect_bricks_organizer_full( $event_id );
			}
			return self::ect_bricks_organizer_name( $event_id );
		}
	}
}
