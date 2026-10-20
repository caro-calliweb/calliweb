<?php
/**
 * ECT_Bricks_Hover_Controls service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Hover_Controls', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Hover_Controls {

		public static function ect_bricks_hover_animation_options( $include_default = false, $include_none = true ) {
			$options = array();
			if ( $include_default ) {
				$options[''] = esc_html__( 'Default', 'template-events-calendar' );
			}
			if ( $include_none ) {
				$options['none'] = esc_html__( 'None', 'template-events-calendar' );
			}
			return array_merge( $options, self::ect_bricks_hover_animation_labels() );
		}

		/**
		 * Shared Animation select for card / featured-image hover groups.
		 *
		 * @param string     $group    Control group key.
		 * @param array|null $required Optional Bricks `required` rows.
		 * @return array<string,mixed>
		 */
		public static function ect_bricks_hover_animation_control( $group, $required = null ) {
			$control = array(
				'tab'      => 'style',
				'group'    => $group,
				'label'    => esc_html__( 'Animation', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => self::ect_bricks_hover_animation_options( true, true ),
				'default'  => '',
				'rerender' => true,
			);
			if ( null !== $required ) {
				$control['required'] = $required;
			}

			return $control;
		}

		/**
		 * Text-decoration select options (Default + allowed CSS values).
		 *
		 * @return array<string,string>
		 */
		public static function ect_bricks_text_decoration_options() {
			$labels = array(
				'none'         => esc_html__( 'None', 'template-events-calendar' ),
				'underline'    => esc_html__( 'Underline', 'template-events-calendar' ),
				'overline'     => esc_html__( 'Overline', 'template-events-calendar' ),
				'line-through' => esc_html__( 'Line through', 'template-events-calendar' ),
			);

			$options = array( '' => esc_html__( 'Default', 'template-events-calendar' ) );
			foreach ( \ECT_Bricks_Value_Utils::ect_bricks_text_decoration_slugs() as $slug ) {
				if ( isset( $labels[ $slug ] ) ) {
					$options[ $slug ] = $labels[ $slug ];
				}
			}

			return $options;
		}

		private static function ect_bricks_hover_animation_labels() {
			$labels = array(
				'fade_in_up'    => esc_html__( 'Fade in up', 'template-events-calendar' ),
				'fade_in_right' => esc_html__( 'Fade in right', 'template-events-calendar' ),
				'fade_in_down'  => esc_html__( 'Fade in down', 'template-events-calendar' ),
				'fade_in_left'  => esc_html__( 'Fade in left', 'template-events-calendar' ),
				'zoom_in'       => esc_html__( 'Zoom in', 'template-events-calendar' ),
				'zoom_out'      => esc_html__( 'Zoom out', 'template-events-calendar' ),
			);

			$out = array();
			foreach ( \ECT_Bricks_Value_Utils::ect_bricks_hover_animation_slugs() as $slug ) {
				if ( isset( $labels[ $slug ] ) ) {
					$out[ $slug ] = $labels[ $slug ];
				}
			}

			return $out;
		}

		/**
		 * Bricks `required` when Template is List (or Default → list).
		 *
		 * Clearing a select to "Default" stores "" — still treat as list.
		 *
		 * @return array{0:string,1:string,2:string[]}
		 */
		public static function ect_bricks_req_layout_is_list() {
			return array( 'layout_template', '=', array( 'list', '' ) );
		}

		/**
		 * Bricks `required` for a list layout + list style.
		 *
		 * Empty list_item_style (Default) matches style-1, same as frontend sanitize.
		 *
		 * @param string           $style style-1|style-2
		 * @param array<int,array> $extra Extra required rows.
		 * @return array<int,array>
		 */
		public static function ect_bricks_req_list_style( $style, array $extra = array() ) {
			$style_match = ( 'style-1' === $style ) ? array( 'style-1', '' ) : $style;

			return array_merge(
				array(
					self::ect_bricks_req_layout_is_list(),
					array( 'list_item_style', '=', $style_match ),
				),
				$extra
			);
		}

		/**
		 * Bricks `required` for list style when featured image is visible.
		 *
		 * @param string           $style style-1|style-2
		 * @param array<int,array> $extra Extra required rows.
		 * @return array<int,array>
		 */
		public static function ect_bricks_req_list_style_with_image( $style, array $extra = array() ) {
			return self::ect_bricks_req_list_style(
				$style,
				array_merge(
					array( array( 'hide_event_image', '!=', true ) ),
					$extra
				)
			);
		}

		public static function ect_bricks_req_list1_date_column_style() {
			return self::ect_bricks_req_list_style(
				'style-1',
				array( array( 'list1_show_date_column', '!=', true ) )
			);
		}

		public static function ect_bricks_hover_part_types() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_hover_part_types()
				: array();
		}

		public static function ect_bricks_req_style2_divider_style() {
			return self::ect_bricks_req_list_style( 'style-2' );
		}

		public static function ect_bricks_style2_meta_icon_ui_parts() {
			$organizer_parts = class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_organizer_user_icon_part_slugs()
				: array( 'organizer', 'organizer_email', 'organizer_phone', 'organizer_website' );

			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				return array_values(
					array_unique(
						array_merge(
							array( 'venue', 'date', 'event_cost', 'tags' ),
							$organizer_parts,
							\ECT_Bricks_Styles::ect_bricks_meta_combo_slugs_style2()
						)
					)
				);
			}

			return array_merge(
				array( 'venue', 'date', 'event_cost', 'tags', 'venue_time_cost' ),
				$organizer_parts
			);
		}

		public static function ect_bricks_req_featured_image_style() {
			return array(
				array( 'hide_event_image', '!=', true ),
			);
		}

		public static function ect_bricks_req_featured_image_vignette() {
			return array(
				array( 'hide_event_image', '!=', true ),
				array( 'ect_bricks_featured_image_vignette', '!=', '' ),
				array( 'ect_bricks_featured_image_vignette', '!=', 'none' ),
			);
		}

		public static function ect_bricks_req_shell_category_style_list1() {
			return self::ect_bricks_req_list_style_with_image(
				'style-1',
				array( array( 'list1_show_category_badge', '!=', true ) )
			);
		}

		public static function ect_bricks_req_shell_date_badge_style() {
			return self::ect_bricks_req_list_style_with_image(
				'style-2',
				array( array( 'style2_show_date_badge', '!=', true ) )
			);
		}

		public static function ect_bricks_req_hover_controls() {
			// Hover fields show for every hover-capable part; styles apply on the
			// frontend only when a hover value is actually set.
			return array( 'part', '=', self::ect_bricks_hover_part_types() );
		}

		public static function ect_bricks_req_hover_interactive() {
			return array( 'part', '=', self::ect_bricks_hover_interactive_types() );
		}

		public static function ect_bricks_hover_interactive_types() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::ect_bricks_hover_interactive_types()
				: array();
		}

		public static function ect_bricks_btn_part_types() {
			return class_exists( 'ECT_Bricks_Part_Options', false )
				? \ECT_Bricks_Part_Options::CTA_PARTS
				: array( 'read_more' );
		}

		public static function ect_bricks_req_btn_styled() {
			return array(
				array( 'part', '=', self::ect_bricks_btn_part_types() ),
				// Bricks checkbox: unchecked is false/absent; `= true` fails visibility in the builder.
				array( 'btn_style', '!=', false ),
			);
		}
	}
}
