<?php
/**
 * ECT_Bricks_Parts_Repeater_Controls service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Parts_Repeater_Controls', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Parts_Repeater_Controls {

		public static function ect_bricks_register_controls( $element ) {
			$event_category_options = ECT_Bricks_Query_Controls::ect_bricks_get_event_category_options();

			ECT_Bricks_Layout_Controls::ect_bricks_register_layout_controls( $element );
			ECT_Bricks_Query_Controls::ect_bricks_register_query_controls( $element, $event_category_options );
			ECT_Bricks_Query_Controls::ect_bricks_register_messages_controls( $element );
			ECT_Bricks_Card_Style_Controls::ect_bricks_register_style_controls( $element );
			self::ect_bricks_register_parts_repeaters( $element );
		}

		private static function ect_bricks_register_parts_repeaters( $element ) {
			$configs = array(
				'parts_style1' => array(
					'style'  => 'style-1',
					'fields' => ECT_Bricks_Part_Fields::ect_bricks_part_fields_for_style1(),
				),
				'parts_style2' => array(
					'style'  => 'style-2',
					'fields' => ECT_Bricks_Part_Fields::ect_bricks_part_fields_for_style2(),
				),
			);

			foreach ( $configs as $key => $config ) {
				$element->controls[ $key ] = self::ect_bricks_parts_repeater_config(
					ECT_Bricks_Hover_Controls::ect_bricks_req_list_style( $config['style'] ),
					class_exists( 'ECT_Bricks_List_Defaults', false )
						? \ECT_Bricks_List_Defaults::ect_bricks_default_parts( $config['style'] )
						: array(),
					$config['fields']
				);
			}
		}

		private static function ect_bricks_parts_repeater_config( $required, array $default, array $fields ) {
			return array(
				'tab'           => 'content',
				'group'         => 'elements',
				'type'          => 'repeater',
				'selector'      => 'fieldId',
				'description'   => esc_html__( 'Drag rows to reorder. Expand a row to edit content and style.', 'template-events-calendar' ),
				'titleProperty' => 'part',
				'placeholder'   => esc_html__( 'event part', 'template-events-calendar' ),
				'rerender'      => true,
				'required'      => $required,
				'default'       => $default,
				'fields'        => $fields,
			);
		}
	}
}
