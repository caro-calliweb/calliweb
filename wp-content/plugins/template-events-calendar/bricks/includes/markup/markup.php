<?php
/**
 * Service facade for Events Widget render and settings helpers.
 *
 * Not a Bricks element class. Styling is via control `css` arrays and static CSS files.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ECT_Bricks_Plugin::ect_bricks_require_shared_element_data();

ECT_Bricks_Plugin::ect_bricks_require_files(
	ECT_BRICKS_DIR . 'includes/markup/',
	array(
		'ect-bricks-value-utils.php',
		'ect-bricks-settings.php',
		'ect-bricks-cost.php',
		'ect-bricks-event-data.php',
		'ect-bricks-dates-format.php',
		'ect-bricks-repeater-parts.php',
		'ect-bricks-layout-cards.php',
		'ect-bricks-part-chrome.php',
	)
);

if ( ! class_exists( 'ECT_Bricks_Markup', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Markup extends ECT_Bricks_Service_Facade {

		/** @return array<string,array<int,string>> */
		protected static function delegate_map(): array {
			return array(
				'ECT_Bricks_Settings_Normalizer' => array(
					'ect_bricks_sanitize_layout_template',
					'ect_bricks_parts_clean',
					'ect_bricks_parts_preserve_bricks_rows',
					'ect_bricks_upgrade_layout_parts',
					'ect_bricks_parts_is_empty',
					'ect_bricks_parts_assign_ids',
					'ect_bricks_norm_widget_settings',
					'ect_bricks_resolve_parts',
					'ect_bricks_shell_style_root_classes',
					'ect_bricks_show_event_image',
					'ect_bricks_show_shell_category_badge',
					'ect_bricks_show_style2_date_badge',
					'ect_bricks_show_list1_date_column',
				),
				'ECT_Bricks_Layout_Shell'        => array(
					'ect_bricks_list1_date_column',
					'ect_bricks_list2_date_badge',
					'ect_bricks_render_layout_parts_sequence',
					'ect_bricks_render_meta_li',
					'ect_bricks_shell_featured_image',
					'ect_bricks_shell_category_badge',
				),
				'ECT_Bricks_Part_Chrome'         => array(
					'ect_bricks_image_size_opts',
				),
			);
		}
	}
}
