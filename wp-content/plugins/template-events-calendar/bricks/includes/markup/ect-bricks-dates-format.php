<?php
/**
 * Event date/time formatting for part output.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Date_Formatter', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Date_Formatter {

		/** @var array<int,array{day:string,time:string}> */
		private static $day_time_parts_cache = array();

		/** PHP date() format string for a part row (preset, custom, or site default). */
		public static function ect_bricks_part_date_php_fmt( $part, array $item ): string {
			$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
			$custom = isset( $item['date_format_custom'] ) ? trim( (string) $item['date_format_custom'] ) : '';
			$custom = preg_replace( '/[\x00-\x1F\x7F<>]/', '', wp_strip_all_tags( $custom ) );
			$part   = (string) $part;

			if ( $preset === 'custom' ) {
				return $custom;
			}
			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				$mapped = \ECT_Bricks_Styles::ect_bricks_date_php_format( $preset, $part );
				if ( $mapped !== null && $mapped !== '' ) {
					return $mapped;
				}
			}
			if ( $preset === 'site_date' ) {
				return (string) get_option( 'date_format' );
			}
			if ( $preset === 'site_time' ) {
				return (string) get_option( 'time_format' );
			}
			if ( $preset === 'site_date_time' ) {
				return (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' );
			}
			if ( $part === 'event_date' ) {
				return (string) get_option( 'date_format' );
			}
			return '';
		}

		/** PHP time() format string for time-only / time-range visibility modes. */
		public static function ect_bricks_part_time_php_fmt( array $item ): string {
			$preset = isset( $item['time_format_preset'] ) ? (string) $item['time_format_preset'] : '';
			$custom = isset( $item['time_format_custom'] ) ? trim( (string) $item['time_format_custom'] ) : '';
			$custom = preg_replace( '/[\x00-\x1F\x7F<>]/', '', wp_strip_all_tags( $custom ) );

			if ( $preset === 'custom' && $custom !== '' ) {
				return self::ect_bricks_time_fmt_lower( $custom );
			}
			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				$mapped = \ECT_Bricks_Styles::ect_bricks_time_php_format( $preset );
				if ( $mapped !== null && $mapped !== '' ) {
					return self::ect_bricks_time_fmt_lower( $mapped );
				}
			}

			return self::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) );
		}

		/** Start or end time for start/end presets; empty when another preset is selected. */
		public static function ect_bricks_start_end_preset_text( $post_id, array $item, $which = 'start' ): string {
			$preset = isset( $item['time_format_preset'] ) ? (string) $item['time_format_preset'] : '';
			$which  = (string) $which;
			if ( $preset !== $which || ! in_array( $which, array( 'start', 'end' ), true ) ) {
				return '';
			}

			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( (int) $post_id );
			if ( ! $start_ts ) {
				return '';
			}

			$php = self::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) );
			$ts  = $which === 'end' ? (int) $end_ts : (int) $start_ts;

			return self::ect_bricks_event_time_text( (int) $post_id, $php, $which, $ts );
		}

		/** Start–end time range for the STR preset; empty when another preset is selected. */
		public static function ect_bricks_str_preset_range_text( $post_id, array $item ): string {
			$preset = isset( $item['time_format_preset'] ) ? (string) $item['time_format_preset'] : '';
			if ( $preset !== 'str' ) {
				return '';
			}

			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( (int) $post_id );
			if ( ! $start_ts ) {
				return '';
			}

			$php   = self::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) );
			$start = self::ect_bricks_event_time_text( (int) $post_id, $php, 'start', (int) $start_ts );
			$end   = self::ect_bricks_event_time_text( (int) $post_id, $php, 'end', (int) $end_ts );

			if ( $start === '' ) {
				return '';
			}
			if ( $end === '' || $start === $end ) {
				return $start;
			}

			return $start . ' - ' . $end;
		}

		/** Weekday name for the day preset; empty when another preset is selected. */
		public static function ect_bricks_day_name_preset_text( $post_id, array $item ): string {
			$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
			if ( $preset !== 'day' ) {
				return '';
			}

			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( (int) $post_id );
			unset( $end_ts );
			if ( ! $start_ts ) {
				return '';
			}

			return trim( wp_strip_all_tags( date_i18n( 'l', $start_ts ) ) );
		}

		/** Join two formatted bounds; collapse when same calendar day or identical text. */
		private static function ect_bricks_i18n_range_text( $start_ts, $end_ts, $php_fmt, $collapse_ymd = false ): string {
			$start = trim( wp_strip_all_tags( date_i18n( $php_fmt, $start_ts ) ) );
			$end   = trim( wp_strip_all_tags( date_i18n( $php_fmt, $end_ts ) ) );
			if ( $collapse_ymd && date_i18n( 'Ymd', $start_ts ) === date_i18n( 'Ymd', $end_ts ) ) {
				return $start;
			}
			if ( $start === $end ) {
				return $start;
			}

			return $start . ' - ' . $end;
		}

		/** Weekday start–end for the day-range preset; empty when another preset is selected. */
		public static function ect_bricks_day_range_preset_text( $post_id, array $item ): string {
			$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
			if ( $preset !== 'sdl' ) {
				return '';
			}

			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( (int) $post_id );
			if ( ! $start_ts ) {
				return '';
			}

			return self::ect_bricks_i18n_range_text( $start_ts, $end_ts, 'l' );
		}

		/** Site-format date range for the DR preset; empty when another preset is selected. */
		public static function ect_bricks_dr_preset_range_text( $post_id, array $item ): string {
			$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
			if ( $preset !== 'dr' ) {
				return '';
			}

			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( (int) $post_id );
			if ( ! $start_ts ) {
				return '';
			}

			return self::ect_bricks_i18n_range_text( $start_ts, $end_ts, (string) get_option( 'date_format' ), true );
		}

		/** Whether the default time preset includes the weekday prefix (day + time). */
		private static function ect_bricks_time_preset_shows_day_prefix( $preset ): bool {
			return in_array( (string) $preset, array( '', 'default' ), true );
		}

		/** Time-only output for presets that omit the weekday prefix. */
		private static function ect_bricks_time_only_preset_text( $post_id, array $item, array $day_time_parts ): string {
			foreach ( array( 'start', 'end' ) as $which ) {
				$single = self::ect_bricks_start_end_preset_text( $post_id, $item, $which );
				if ( $single !== '' ) {
					return $single;
				}
			}

			$str = self::ect_bricks_str_preset_range_text( $post_id, $item );
			if ( $str !== '' ) {
				return $str;
			}

			$time = isset( $day_time_parts['time'] ) ? trim( (string) $day_time_parts['time'] ) : '';
			if ( $time !== '' ) {
				return $time;
			}

			$php = self::ect_bricks_part_time_php_fmt( $item );
			$raw = ECT_Bricks_Event_Data::ect_bricks_event_start_date_raw( (int) $post_id );
			$ts  = $raw ? strtotime( $raw ) : false;

			return self::ect_bricks_event_time_text( (int) $post_id, $php, 'start', $ts ? (int) $ts : 0 );
		}

		/** Start–end range for SED / SEDT presets; empty when another preset is selected. */
		public static function ect_bricks_sed_preset_range_text( $post_id, array $item ): string {
			$preset = isset( $item['date_format_preset'] ) ? (string) $item['date_format_preset'] : '';
			if ( ! in_array( $preset, array( 'sed', 'sedt' ), true ) ) {
				return '';
			}

			list( $start_ts, $end_ts ) = ECT_Bricks_Event_Data::ect_bricks_date_bounds( (int) $post_id );
			if ( ! $start_ts ) {
				return '';
			}

			if ( $preset === 'sedt' ) {
				$php = trim(
					(string) get_option( 'date_format' ) . ' ' . self::ect_bricks_time_fmt_lower( (string) get_option( 'time_format' ) )
				);
			} else {
				$php = (string) get_option( 'date_format' );
			}

			return self::ect_bricks_i18n_range_text( $start_ts, $end_ts, $php, $preset === 'sed' );
		}

		/** Lowercase am/pm in a PHP time format string. */
		public static function ect_bricks_time_fmt_lower( $format ): string {
			$format = (string) $format;
			return $format === '' ? '' : str_replace( 'A', 'a', $format );
		}

		/** Lowercase AM/PM in formatted time text. */
		public static function ect_bricks_time_lower_am( $time_str ): string {
			$time_str = trim( (string) $time_str );
			if ( $time_str === '' ) {
				return '';
			}
			return (string) preg_replace_callback(
				'/\b(AM|PM)\b/u',
				static function ( $m ) {
					return strtolower( $m[0] );
				},
				$time_str
			);
		}

		/** Day name + time range for the “day & time” part. */
		public static function ect_bricks_build_day_time_parts( $post_id, array $item = array() ): array {
			$post_id = (int) $post_id;

			$time_preset = isset( $item['time_format_preset'] ) ? (string) $item['time_format_preset'] : '';
			$cache_key   = $post_id . '|' . $time_preset . '|' . md5( wp_json_encode( $item ) );

			$store = static function ( array $result ) use ( $cache_key ) {
				self::$day_time_parts_cache[ $cache_key ] = $result;
				return $result;
			};

			if ( isset( self::$day_time_parts_cache[ $cache_key ] ) ) {
				return self::$day_time_parts_cache[ $cache_key ];
			}
			if ( $post_id < 1 ) {
				return $store(
					array(
						'day'  => '',
						'time' => '',
					)
				);
			}

			$dates = ECT_Bricks_Event_Data::ect_bricks_event_meta_dates( $post_id );
			$start = $dates['start'] ? strtotime( $dates['start'] ) : false;
			if ( ! $start ) {
				return $store(
					array(
						'day'  => '',
						'time' => '',
					)
				);
			}
			$end = $dates['end'] ? strtotime( $dates['end'] ) : $start;
			if ( ! $end ) {
				$end = $start;
			}
			if ( function_exists( 'tribe_event_is_all_day' ) && tribe_event_is_all_day( $post_id ) ) {
				$day = trim( wp_strip_all_tags( date_i18n( 'l', $start ) ) );
				return $store(
					array(
						'day'  => $day,
						'time' => '',
					)
				);
			}

			if ( in_array( $time_preset, array( 'start', 'end' ), true ) ) {
				$single_time = self::ect_bricks_start_end_preset_text( $post_id, $item, $time_preset );
				$day         = trim( wp_strip_all_tags( date_i18n( 'l', $start ) ) );
				return $store(
					array(
						'day'  => $day,
						'time' => $single_time,
					)
				);
			}

			if ( $time_preset === 'str' ) {
				$str_time = self::ect_bricks_str_preset_range_text( $post_id, $item );
				$day      = trim( wp_strip_all_tags( date_i18n( 'l', $start ) ) );
				return $store(
					array(
						'day'  => $day,
						'time' => $str_time,
					)
				);
			}

			$fmt = self::ect_bricks_part_time_php_fmt( $item );
			$day = trim( wp_strip_all_tags( date_i18n( 'l', $start ) ) );

			$t0 = self::ect_bricks_event_time_text( $post_id, $fmt, 'start', $start );
			$t1 = self::ect_bricks_event_time_text( $post_id, $fmt, 'end', $end );
			if ( $t0 === '' ) {
				return $store(
					array(
						'day'  => $day,
						'time' => '',
					)
				);
			}
			if ( $t1 === '' || $t0 === $t1 ) {
				return $store(
					array(
						'day'  => $day,
						'time' => $t0,
					)
				);
			}
			return $store(
				array(
					'day'  => $day,
					'time' => $t0 . ' - ' . $t1,
				)
			);
		}

		/**
		 * Resolve a single event start/end time via Tribe helpers, then date_i18n.
		 *
		 * @param int    $post_id Event post ID.
		 * @param string $fmt     PHP time format (already lowercased am/pm tokens as needed).
		 * @param string $which   start|end.
		 * @param int    $ts      Fallback Unix timestamp.
		 * @return string Plain-text time (am/pm lowercased).
		 */
		private static function ect_bricks_event_time_text( $post_id, $fmt, $which = 'start', $ts = 0 ) {
			$post_id = (int) $post_id;
			$fmt     = (string) $fmt;
			$html    = '';

			if ( $which === 'end' ) {
				if ( function_exists( 'tribe_get_end_time' ) ) {
					$html = (string) tribe_get_end_time( $post_id, $fmt );
				}
				if ( $html === '' && function_exists( 'tribe_get_end_date' ) ) {
					$html = (string) tribe_get_end_date( $post_id, true, $fmt );
				}
			} else {
				if ( function_exists( 'tribe_get_start_time' ) ) {
					$html = (string) tribe_get_start_time( $post_id, $fmt );
				}
				if ( $html === '' && function_exists( 'tribe_get_start_date' ) ) {
					$html = (string) tribe_get_start_date( $post_id, true, $fmt );
				}
			}

			if ( $html === '' && $ts ) {
				$html = date_i18n( $fmt, (int) $ts );
			}

			return self::ect_bricks_time_lower_am( trim( wp_strip_all_tags( $html ) ) );
		}

		/** Whether visibility uses date formats (Date / legacy date range). */
		public static function ect_bricks_uses_date_format( $date_display ): bool {
			return in_array( (string) $date_display, array( 'date', 'range' ), true );
		}

		/**
		 * Plain text for a part row's Visibility (date_display) setting.
		 *
		 * @param int                 $post_id Event post ID.
		 * @param array<string,mixed> $item    Repeater row.
		 * @return string
		 */
		public static function ect_bricks_part_visibility_plain_text( $post_id, array $item ) {
			$post_id = (int) $post_id;
			if ( $post_id < 1 ) {
				return '';
			}

			$raw_fmt = isset( $item['date_display'] ) ? (string) $item['date_display'] : 'day_time_range';
			if ( $raw_fmt === '' ) {
				$raw_fmt = 'day_time_range';
			}

			if ( self::ect_bricks_uses_date_format( $raw_fmt ) ) {
				foreach ( array( 'ect_bricks_day_name_preset_text', 'ect_bricks_day_range_preset_text', 'ect_bricks_dr_preset_range_text', 'ect_bricks_sed_preset_range_text' ) as $handler ) {
					$text = self::$handler( $post_id, $item );
					if ( $text !== '' ) {
						return $text;
					}
				}

				if ( $raw_fmt === 'range' && class_exists( 'ECT_Bricks_Layout_Shell', false ) ) {
					$post = get_post( $post_id );
					return $post instanceof \WP_Post
						? ECT_Bricks_Layout_Shell::ect_bricks_date_range_text( $post, $item )
						: '';
				}

				$format = self::ect_bricks_part_date_php_fmt( 'event_date', $item );
				$php    = $format !== '' ? $format : (string) get_option( 'date_format' );
				if ( function_exists( 'tribe_get_start_date' ) ) {
					$html = (string) tribe_get_start_date( $post_id, false, $php );
				} else {
					$raw  = ECT_Bricks_Event_Data::ect_bricks_event_start_date_raw( $post_id );
					$ts   = $raw ? strtotime( $raw ) : false;
					$html = $ts ? date_i18n( $php, $ts ) : '';
				}

				return trim( wp_strip_all_tags( $html ) );
			}

			if ( $raw_fmt === 'day' ) {
				$raw = ECT_Bricks_Event_Data::ect_bricks_event_start_date_raw( $post_id );
				$ts  = $raw ? strtotime( $raw ) : false;

				return $ts ? trim( wp_strip_all_tags( date_i18n( 'l', $ts ) ) ) : '';
			}

			$time_preset = isset( $item['time_format_preset'] ) ? (string) $item['time_format_preset'] : '';
			$tp          = self::ect_bricks_build_day_time_parts( $post_id, $item );

			if ( $raw_fmt === 'time' ) {
				return self::ect_bricks_time_only_preset_text( $post_id, $item, $tp );
			}

			if ( ! self::ect_bricks_time_preset_shows_day_prefix( $time_preset ) ) {
				return self::ect_bricks_time_only_preset_text( $post_id, $item, $tp );
			}

			$day  = isset( $tp['day'] ) ? trim( (string) $tp['day'] ) : '';
			$time = isset( $tp['time'] ) ? trim( (string) $tp['time'] ) : '';
			if ( $day !== '' && $time !== '' ) {
				return $day . ', ' . $time;
			}
			if ( $time !== '' ) {
				return $time;
			}

			return $day;
		}
	}
}
