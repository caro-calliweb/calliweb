<?php
/**
 * ECT_Bricks_Part_Options service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Part_Options', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Part_Options {

		/** @var string[] Repeater row style keys preserved when merging layout defaults. */
		public const PRESERVE_STYLE_KEYS = array(
			'id',
			'ect_bricks_typography',
			'ect_bricks_typography_combo',
			'ect_bricks_text_color',
			'ect_bricks_text_align',
			'ect_bricks_background',
			'ect_bricks_meta_icon_color',
			'ect_bricks_meta_icon_background',
			'ect_bricks_margin',
			'ect_bricks_padding',
			'ect_bricks_hover_color',
			'ect_bricks_hover_background',
			'ect_bricks_hover_text_decoration',
			'ect_bricks_hover_animation',
			'btn_style',
			'btn_border_type',
			'btn_border_width',
			'btn_border_color',
			'btn_padding',
			'btn_border_radius',
			'ect_bricks_image_width',
			'ect_bricks_image_height',
			'ect_bricks_image_border',
			'ect_bricks_image_radius',
			'image_size',
			'image_link',
			'date_display',
			'venue_display',
			'website_link_text',
			'organizer_display',
			'organizer_website_link_text',
			'date_format_preset',
			'date_format_custom',
			'time_format_preset',
			'time_format_custom',
		);

		/** Venue address/detail field slugs (excluding top-level `venue`). */
		public const VENUE_DETAIL_PART_SLUGS = array(
			'venue_full_address',
			'venue_street',
			'venue_city',
			'venue_state',
			'venue_zip',
			'venue_country',
			'venue_phone',
			'venue_website',
		);

		/** Organizer detail field slugs (excluding top-level `organizer`). */
		public const ORGANIZER_DETAIL_PART_SLUGS = array(
			'organizer_email',
			'organizer_phone',
			'organizer_website',
		);

		/**
		 * Detail-field part slugs (venue / organizer detail fields).
		 *
		 * @return string[]
		 */
		public static function ect_bricks_detail_part_slugs() {
			return array_merge(
				self::VENUE_DETAIL_PART_SLUGS,
				self::ORGANIZER_DETAIL_PART_SLUGS
			);
		}

		/**
		 * Legacy display-mode part slugs kept during clean_part migrations.
		 *
		 * @return string[]
		 */
		public static function ect_bricks_legacy_part_slugs() {
			return array_merge(
				self::ect_bricks_detail_part_slugs(),
				array( 'event_date', 'event_time', 'event_day' )
			);
		}

		/**
		 * Venue/pin meta-icon part slugs (venue + address fields).
		 *
		 * @return string[]
		 */
		public static function ect_bricks_venue_pin_icon_part_slugs() {
			return array_merge(
				array( 'venue' ),
				self::VENUE_DETAIL_PART_SLUGS
			);
		}

		/**
		 * Organizer/user meta-icon part slugs.
		 *
		 * @return string[]
		 */
		public static function ect_bricks_organizer_user_icon_part_slugs() {
			return array_merge( array( 'organizer' ), self::ORGANIZER_DETAIL_PART_SLUGS );
		}

		/**
		 * Base meta-list part slugs (excluding meta-combo permutations).
		 *
		 * @return string[]
		 */
		public static function ect_bricks_base_meta_row_part_slugs() {
			return array_merge(
				array(
					'date',
					'event_time',
					'venue',
					'organizer',
					'event_cost',
					'tags',
				),
				self::VENUE_DETAIL_PART_SLUGS,
				self::ORGANIZER_DETAIL_PART_SLUGS
			);
		}

		/**
		 * Venue display option → cleaned part slug (composite modes map to `venue`).
		 *
		 * @return array<string,string>
		 */
		public static function ect_bricks_venue_display_clean_map() {
			return array(
				'full_details'   => 'venue',
				'name_and_city'  => 'venue',
				'name_and_state' => 'venue',
				'name'           => 'venue',
				'full_address'   => 'venue_full_address',
				'street'         => 'venue_street',
				'city'           => 'venue_city',
				'state'          => 'venue_state',
				'zip'            => 'venue_zip',
				'country'        => 'venue_country',
				'phone'          => 'venue_phone',
				'website'        => 'venue_website',
			);
		}

		/**
		 * Detail part slug for a single-field venue display option, or empty for composite modes.
		 *
		 * @param string $display Venue display key.
		 * @return string
		 */
		public static function ect_bricks_venue_display_detail_slug( $display ) {
			$display = (string) $display;
			$map     = self::ect_bricks_venue_display_clean_map();
			if ( ! isset( $map[ $display ] ) || $map[ $display ] === 'venue' ) {
				return '';
			}

			return $map[ $display ];
		}

		public static function ect_bricks_clean_part( array $item ) {
			if ( ! empty( $item['_ect_bricks_clean'] ) ) {
				return $item;
			}

			$part = isset( $item['part'] ) ? (string) $item['part'] : 'title';

			if ( ECT_Bricks_Meta_Combo::ect_bricks_is_meta_combo_slug( $part ) ) {
				$item['_ect_bricks_clean'] = true;
				return $item;
			}

			$legacy_parts = self::ect_bricks_legacy_part_slugs();

			if ( in_array( $part, $legacy_parts, true ) ) {
				$item['_ect_bricks_clean'] = true;
				return $item;
			}

			$display_maps = array(
				'venue'      => array(
					'key'     => 'venue_display',
					'default' => 'full_details',
					'map'     => self::ect_bricks_venue_display_clean_map(),
				),
				'date'       => array(
					'key'     => 'date_display',
					'default' => 'day_time_range',
					'map'     => array(
						'day_time_range' => 'date',
						'date'           => 'event_date',
						'time'           => 'event_time',
						'day'            => 'event_day',
					),
				),
				'organizer'  => array(
					'key'     => 'organizer_display',
					'default' => 'full_details',
					'map'     => array(
						'full_details' => 'organizer',
						'name'         => 'organizer',
						'email'        => 'organizer_email',
						'phone'        => 'organizer_phone',
						'website'      => 'organizer_website',
					),
				),
			);

			if ( isset( $display_maps[ $part ] ) ) {
				$config = $display_maps[ $part ];
				$fmt    = isset( $item[ $config['key'] ] ) ? (string) $item[ $config['key'] ] : $config['default'];

				if ( $part === 'venue' && $fmt === 'name_and_address' ) {
					$fmt = 'full_details';
				}
				if ( $part === 'date' && $fmt === 'range' ) {
					$item['_ect_bricks_clean'] = true;
					return $item;
				}
				if ( isset( $config['map'][ $fmt ] ) ) {
					$item['part'] = $config['map'][ $fmt ];
				}
			}

			$item['_ect_bricks_clean'] = true;
			return $item;
		}

		public static function ect_bricks_part_options_shared() {
			return array(
				'title'       => self::ect_bricks_builder_select_label( __( 'Title', 'template-events-calendar' ) ),
				'description' => self::ect_bricks_builder_select_label( __( 'Description', 'template-events-calendar' ) ),
				'date'        => self::ect_bricks_builder_select_label( __( 'Date & time', 'template-events-calendar' ) ),
				'venue'       => self::ect_bricks_builder_select_label( __( 'Venue', 'template-events-calendar' ) ),
				'organizer'   => self::ect_bricks_builder_select_label( __( 'Organizer', 'template-events-calendar' ) ),
				'event_cost'  => self::ect_bricks_builder_select_label( __( 'Cost', 'template-events-calendar' ) ),
				'read_more'   => self::ect_bricks_builder_select_label( __( 'Read more', 'template-events-calendar' ) ),
				'categories'  => self::ect_bricks_builder_select_label( __( 'Categories', 'template-events-calendar' ) ),
				'tags'        => self::ect_bricks_builder_select_label( __( 'Tags', 'template-events-calendar' ) ),
				'image'       => self::ect_bricks_builder_select_label( __( 'Featured image', 'template-events-calendar' ) ),
			);
		}

		/**
		 * Bricks builder select label: decode entities after translation.
		 *
		 * Bricks escapes control option labels in the panel — do not use esc_html__ at
		 * call sites or ampersands render literally as "&amp;". Pass the result of __()
		 * with a literal string so gettext parsers can extract translatable text.
		 *
		 * @param string $translated_text Already translated label.
		 * @return string
		 */
		public static function ect_bricks_builder_select_label( $translated_text ) {
			return html_entity_decode(
				(string) $translated_text,
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			);
		}

		public static function ect_bricks_get_part_options() {
			return array_merge(
				self::ect_bricks_part_options_shared(),
				ECT_Bricks_Meta_Combo::ect_bricks_meta_combo_options_all()
			);
		}

		/** Part slugs that render as button-styled CTA rows. */
		public const CTA_PARTS = array( 'read_more' );

		/** Part slugs that support interactive hover (color / decoration). */
		public static function ect_bricks_hover_interactive_types() {
			return array_merge( array( 'title', 'categories', 'tags' ), self::CTA_PARTS );
		}

		/** Part slugs that show hover controls (interactive + featured image). */
		public static function ect_bricks_hover_part_types() {
			return array_merge( self::ect_bricks_hover_interactive_types(), array( 'image' ) );
		}
	}
}
