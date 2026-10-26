<?php
/**
 * ECT_Bricks_Styles facade — delegates to element data service classes.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ECT_Bricks_Plugin::ect_bricks_require_shared_element_data();
ECT_Bricks_Plugin::ect_bricks_require_file( 'includes/markup/ect-bricks-dates-format.php' );
ECT_Bricks_Plugin::ect_bricks_require_files(
	ECT_BRICKS_DIR . 'includes/styles/',
	array(
		'ect-bricks-date-format-presets.php',
	)
);

if ( ! class_exists( 'ECT_Bricks_Styles', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Styles extends ECT_Bricks_Service_Facade {

		/** @return array<string,array<int,string>> */
		protected static function delegate_map(): array {
			return array(
				'ECT_Bricks_Date_Format_Presets' => array(
					'ect_bricks_date_php_format',
					'ect_bricks_date_options',
					'ect_bricks_time_php_format',
					'ect_bricks_time_options',
				),
				'ECT_Bricks_Meta_Combo'          => array(
					'ect_bricks_meta_combo_order',
					'ect_bricks_meta_combo_all_slugs',
					'ect_bricks_is_meta_combo_slug',
					'ect_bricks_meta_combo_slugs_style2',
					'ect_bricks_meta_combo_slugs_with_segment',
					'ect_bricks_normalize_layout_row',
					'ect_bricks_part_has_cost_segment',
					'ect_bricks_part_has_venue_segment',
					'ect_bricks_part_has_organizer_segment',
				),
				'ECT_Bricks_Part_Options'        => array(
					'ect_bricks_get_part_options',
					'ect_bricks_part_options_shared',
					'ect_bricks_clean_part',
				),
			);
		}
	}
}
