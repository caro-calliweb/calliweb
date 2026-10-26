<?php
/**
 * ECT_Bricks_Controls facade — delegates to control registrar service classes.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

ECT_Bricks_Plugin::ect_bricks_require_files(
	ECT_BRICKS_DIR . 'includes/controls/',
	array(
		'ect-bricks-hover-controls.php',
		'ect-bricks-part-fields.php',
		'ect-bricks-card-style-controls.php',
		'ect-bricks-layout-controls.php',
		'ect-bricks-query-controls.php',
		'ect-bricks-parts-repeater-controls.php',
	)
);

if ( ! class_exists( 'ECT_Bricks_Controls', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Controls extends ECT_Bricks_Service_Facade {

		/** @return array<string,array<int,string>> */
		protected static function delegate_map(): array {
			return array(
				'ECT_Bricks_Parts_Repeater_Controls' => array(
					'ect_bricks_register_controls',
				),
			);
		}
	}
}
