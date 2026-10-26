<?php

/**
 * Query helpers for the Events Widget (TEC tribe_get_events args).
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Query', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Query {


		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return string all|between
		 */
		public static function ect_bricks_time_mode( array $settings ) {
			$time_mode = isset( $settings['event_time_mode'] ) ? (string) $settings['event_time_mode'] : 'all';
			return 'between' === $time_mode ? 'between' : 'all';
		}

		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return string all|future|past
		 */
		public static function ect_bricks_event_type( array $settings ) {
			$event_type = isset( $settings['event_type'] ) ? sanitize_key( (string) $settings['event_type'] ) : 'all';
			return in_array( $event_type, array( 'all', 'future', 'past' ), true ) ? $event_type : 'all';
		}

		/**
		 * @param array<string,mixed> $settings Element settings.
		 * @return int
		 */
		public static function ect_bricks_posts_per_page( array $settings ) {
			$posts_per_page = array_key_exists( 'posts_per_page', $settings ) ? (int) $settings['posts_per_page'] : 10;
			if ( $posts_per_page < 1 ) {
				$posts_per_page = 10;
			}

			$max_posts = (int) apply_filters( 'ect_bricks_events_posts_per_page_max', 100 );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$max_posts = max( 1, $max_posts );

			return min( $posts_per_page, $max_posts );
		}

		/**
		 * Inclusive calendar-day bounds from Bricks datepicker strings (site timezone).
		 *
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array{0:string,1:string} Two MySQL datetime strings, or both empty if invalid.
		 */
		public static function ect_bricks_range_bounds( array $settings ) {
			$range_start_raw = isset( $settings['event_range_start'] ) ? trim( (string) $settings['event_range_start'] ) : '';
			$range_end_raw   = isset( $settings['event_range_end'] ) ? trim( (string) $settings['event_range_end'] ) : '';
			if ( '' === $range_start_raw || '' === $range_end_raw ) {
				return array( '', '' );
			}

			$start_timestamp = self::ect_bricks_parse_datepicker_timestamp( $range_start_raw );
			$end_timestamp   = self::ect_bricks_parse_datepicker_timestamp( $range_end_raw );
			if ( ! $start_timestamp || ! $end_timestamp ) {
				return array( '', '' );
			}

			if ( $start_timestamp > $end_timestamp ) {
				$swap            = $start_timestamp;
				$start_timestamp = $end_timestamp;
				$end_timestamp   = $swap;
			}

			$range_start = wp_date( 'Y-m-d 00:00:00', $start_timestamp );
			$range_end   = wp_date( 'Y-m-d 23:59:59', $end_timestamp );

			return array( $range_start, $range_end );
		}

		/**
		 * Parse a Bricks datepicker string to a Unix timestamp.
		 *
		 * Only accepts explicit Y-m-d formats as stored by the Bricks datepicker.
		 * Invalid input returns false so the between-range filter is disabled.
		 *
		 * @param string $raw Datepicker value.
		 * @return int|false
		 */
		private static function ect_bricks_parse_datepicker_timestamp( $raw ) {
			$raw = trim( (string) $raw );
			if ( $raw === '' ) {
				return false;
			}

			foreach ( array( 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d' ) as $format ) {
				$dt = \DateTime::createFromFormat( $format, $raw );
				if ( ! ( $dt instanceof \DateTime ) ) {
					continue;
				}
				$errors = \DateTime::getLastErrors();
				if ( is_array( $errors ) && ( (int) $errors['warning_count'] > 0 || (int) $errors['error_count'] > 0 ) ) {
					continue;
				}

				return $dt->getTimestamp();
			}

			return false;
		}

		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return string[] Category slugs (tribe_events_cat).
		 */
		public static function ect_bricks_category_slugs( array $settings ) {
			$category_slugs = array();

			if ( ! empty( $settings['event_categories'] ) && is_array( $settings['event_categories'] ) ) {
				foreach ( $settings['event_categories'] as $category_slug ) {
					if ( is_array( $category_slug ) ) {
						if ( isset( $category_slug['value'] ) ) {
							$category_slug = $category_slug['value'];
						} elseif ( isset( $category_slug['name'] ) ) {
							$category_slug = $category_slug['name'];
						} else {
							continue;
						}
					}

					$sanitized_slug = sanitize_title( (string) $category_slug );
					if ( '' !== $sanitized_slug ) {
						$category_slugs[] = $sanitized_slug;
					}
				}

				return array_values( array_unique( $category_slugs ) );
			}

			return array();
		}

		/**
		 * Build meta_query for event dates: end date for future/past, start date for between range.
		 *
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array<int|string, mixed> Meta query for WP_Query / tribe_get_events.
		 */
		public static function ect_bricks_date_meta_query( array $settings ) {
			$meta_clauses = array();
			$time_mode  = self::ect_bricks_time_mode( $settings );
			$event_type = self::ect_bricks_event_type( $settings );

			if ( 'between' === $time_mode ) {
				list($range_start, $range_end) = self::ect_bricks_range_bounds( $settings );
				if ( '' !== $range_start && '' !== $range_end ) {
					$meta_clauses[] = array(
						'key'     => '_EventStartDate',
						'value'   => array( $range_start, $range_end ),
						'compare' => 'BETWEEN',
						'type'    => 'DATETIME',
					);
				}
			}

			if ( 'future' === $event_type || 'past' === $event_type ) {
				$now_mysql = current_time( 'mysql' );
				if ( 'future' === $event_type ) {
					$meta_clauses[] = array(
						'key'     => '_EventEndDate',
						'value'   => $now_mysql,
						'compare' => '>=',
						'type'    => 'DATETIME',
					);
				} else {
					$meta_clauses[] = array(
						'key'     => '_EventEndDate',
						'value'   => $now_mysql,
						'compare' => '<',
						'type'    => 'DATETIME',
					);
				}
			}

			if ( empty( $meta_clauses ) ) {
				return array();
			}

			if ( count( $meta_clauses ) > 1 ) {
				return array_merge( array( 'relation' => 'AND' ), $meta_clauses );
			}

			return $meta_clauses;
		}

		/**
		 * @param array<string,mixed> $settings Element or AJAX settings.
		 * @return array<int, array<string, mixed>> Tax query clauses.
		 */
		public static function ect_bricks_tax_query( array $settings ) {
			$category_slugs = self::ect_bricks_category_slugs( $settings );
			if ( empty( $category_slugs ) ) {
				return array();
			}

			return array(
				array(
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'slug',
					'terms'    => $category_slugs,
					'operator' => 'IN',
				),
			);
		}

		/**
		 * Args for tribe_get_events() / matching WP_Query shape used by the Events Widget.
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return array<string, mixed>
		 */
		public static function ect_bricks_tribe_args( array $settings ) {
			$posts_per_page = self::ect_bricks_posts_per_page( $settings );
			$order          = ! empty( $settings['order'] ) && strtoupper( (string) $settings['order'] ) === 'DESC' ? 'DESC' : 'ASC';

			$query_args = array(
				'posts_per_page' => $posts_per_page,
				'order'          => $order,
				'orderby'        => 'meta_value',
				'meta_key'       => '_EventStartDate', //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_type'      => 'DATETIME',
			);

			$meta_query = self::ect_bricks_date_meta_query( $settings );
			if ( ! empty( $meta_query ) ) {
				$query_args['meta_query'] = $meta_query;//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			$tax_query = self::ect_bricks_tax_query( $settings );
			if ( ! empty( $tax_query ) ) {
				$query_args['tax_query'] = $tax_query;//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}

			return $query_args;
		}

		/**
		 * Query events for the initial widget render.
		 *
		 * Caller must ensure `tribe_get_events()` exists (see {@see ECT_Bricks_Widget::render()}).
		 *
		 * @param array<string,mixed> $settings Element settings.
		 * @return \WP_Post[]
		 */
		public static function ect_bricks_fetch_events( array $settings ) {
			$events = tribe_get_events( self::ect_bricks_tribe_args( $settings ) );

			return is_array( $events ) ? $events : array();
		}
	}
}
