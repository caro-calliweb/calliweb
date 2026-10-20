<?php
/**
 * Default parts rows for List Style 1 / 2 (no layout class load required).
 *
 * Keeps builder control defaults independent of list-style-*.php so render
 * can load only the chosen layout file.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_List_Defaults', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_List_Defaults {

		/** @return array<string,mixed> */
		private static function ect_bricks_title_row() {
			return array(
				'part' => 'title',
				'link' => true,
			);
		}

		/** @return array<string,mixed> */
		private static function ect_bricks_description_row() {
			return array(
				'part' => 'description',
			);
		}

		/** @return array<string,mixed> */
		private static function ect_bricks_read_more_row() {
			return array(
				'part'           => 'read_more',
				'read_more_text' => __( 'View Details', 'template-events-calendar' ),
			);
		}

		/**
		 * Default repeater rows before id assignment.
		 *
		 * @param string $list_item_style style-1|style-2
		 * @return array<int,array<string,mixed>>
		 */
		public static function ect_bricks_default_rows( $list_item_style = 'style-1' ) {
			$style = class_exists( 'ECT_Bricks_Plugin', false )
				? \ECT_Bricks_Plugin::ect_bricks_sanitize_list_style( $list_item_style )
				: ( in_array( (string) $list_item_style, array( 'style-1', 'style-2' ), true ) ? (string) $list_item_style : 'style-1' );

			if ( 'style-2' === $style ) {
				return array(
					array(
						'part' => 'categories',
					),
					self::ect_bricks_title_row(),
					self::ect_bricks_description_row(),
					array(
						'part'          => 'venue_time_cost',
						'venue_display' => 'name_and_city',
						'date_display'  => 'time',
					),
					self::ect_bricks_read_more_row(),
				);
			}

			return array(
				self::ect_bricks_title_row(),
				self::ect_bricks_description_row(),
				array(
					'part'          => 'venue_time',
					'venue_display' => 'name_and_city',
					'date_display'  => 'time',
				),
				array(
					'part' => 'event_cost',
				),
				self::ect_bricks_read_more_row(),
			);
		}

		/**
		 * Default parts with Bricks row ids when markup helpers are available.
		 *
		 * @param string $list_item_style style-1|style-2
		 * @return array<int,array<string,mixed>>
		 */
		public static function ect_bricks_default_parts( $list_item_style = 'style-1' ) {
			$rows = self::ect_bricks_default_rows( $list_item_style );

			return class_exists( 'ECT_Bricks_Markup', false )
				? \ECT_Bricks_Markup::ect_bricks_parts_assign_ids( $rows )
				: $rows;
		}

		/**
		 * Minimal fallback parts (title / description / date) when no repeater is set.
		 *
		 * @return array<int,array<string,mixed>>
		 */
		public static function ect_bricks_minimal_parts() {
			$rows = array(
				self::ect_bricks_title_row(),
				self::ect_bricks_description_row(),
				array(
					'part'                => 'date',
					'date_text_transform' => 'uppercase',
				),
			);

			return class_exists( 'ECT_Bricks_Markup', false )
				? \ECT_Bricks_Markup::ect_bricks_parts_assign_ids( $rows )
				: $rows;
		}
	}
}
