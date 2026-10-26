<?php
/**
 * ECT_Bricks_Part_Fields service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ECT_Bricks_Part_Fields', false ) ) {
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
	final class ECT_Bricks_Part_Fields {

		private static function ect_bricks_part_button_fields() {
			$btn_parts_req  = array( 'part', '=', ECT_Bricks_Hover_Controls::ect_bricks_btn_part_types() );
			$btn_styled_req = ECT_Bricks_Hover_Controls::ect_bricks_req_btn_styled();

			return array(
				'btn_sep'           => array(
					'label'    => esc_html__( 'Button', 'template-events-calendar' ),
					'type'     => 'separator',
					'required' => self::ect_bricks_require_tab( $btn_parts_req, 'style' ),
				),
				'btn_style'         => array(
					'label'    => esc_html__( 'Button styles', 'template-events-calendar' ),
					'type'     => 'checkbox',
					'default'  => false,
					'rerender' => true,
					'required' => self::ect_bricks_require_tab( $btn_parts_req, 'style' ),
				),
				'btn_sep_border'    => array(
					'label'    => esc_html__( 'Border & padding', 'template-events-calendar' ),
					'type'     => 'separator',
					'required' => self::ect_bricks_require_tab( $btn_styled_req, 'style' ),
				),
				'btn_border_type'   => array(
					'label'    => esc_html__( 'Border type', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => array(
						'solid'  => esc_html__( 'Solid', 'template-events-calendar' ),
						'dashed' => esc_html__( 'Dashed', 'template-events-calendar' ),
						'dotted' => esc_html__( 'Dotted', 'template-events-calendar' ),
						'double' => esc_html__( 'Double', 'template-events-calendar' ),
						'none'   => esc_html__( 'None', 'template-events-calendar' ),
					),
					'default'  => 'solid',
					'required' => self::ect_bricks_require_tab( $btn_styled_req, 'style' ),
					'css'      => self::ect_bricks_repeater_var_css( '--ect-bricks-btn-border-style' ),
				),
				'btn_border_width'  => array(
					'label'       => esc_html__( 'Border width', 'template-events-calendar' ),
					'type'        => 'number',
					'units'       => array( 'px' ),
					'unit'        => 'px',
					'placeholder' => '1',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( $btn_styled_req, 'style' ),
					'css'         => self::ect_bricks_repeater_var_css( '--ect-bricks-btn-border-width' ),
				),
				'btn_border_color'  => array(
					'label'       => esc_html__( 'Border color', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => '#cccccc',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( $btn_styled_req, 'style' ),
					'css'         => self::ect_bricks_repeater_var_css( '--ect-bricks-btn-border-color' ),
				),
				'btn_padding'       => array(
					'label'    => esc_html__( 'Button padding', 'template-events-calendar' ),
					'type'     => 'spacing',
					'required' => self::ect_bricks_require_tab( $btn_styled_req, 'style' ),
					'css'      => self::ect_bricks_field_css( 'padding', self::ect_bricks_repeater_btn_surface_selectors() ),
				),
				'btn_border_radius' => array(
					'label'       => esc_html__( 'Border radius', 'template-events-calendar' ),
					'type'        => 'dimensions',
					'placeholder' => '0px',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( $btn_styled_req, 'style' ),
					'css'         => self::ect_bricks_field_css( 'border-radius', self::ect_bricks_repeater_btn_surface_selectors() ),
				),
			);
		}

		private static function ect_bricks_part_hover_fields() {
			// No enable/disable toggle: hover styling simply applies on the
			// frontend when any hover value is set (see
			// ECT_Bricks_Part_Chrome::ect_bricks_hover_style_active()).
			$hover_controls_req    = ECT_Bricks_Hover_Controls::ect_bricks_req_hover_controls();
			$hover_interactive_req = ECT_Bricks_Hover_Controls::ect_bricks_req_hover_interactive();
			$image_req             = array( 'part', '=', 'image' );

			return array(
				'ect_bricks_sep_hover'              => array(
					'type'     => 'separator',
					'label'    => esc_html__( 'Hover effects', 'template-events-calendar' ),
					'required' => self::ect_bricks_require_tab( $hover_controls_req, 'style' ),
				),
				'ect_bricks_hover_color'            => array(
					'label'       => esc_html__( 'Hover color', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => '#000000',
					'required'    => self::ect_bricks_require_tab( $hover_controls_req, 'style' ),
					'rerender'    => true,
					// Bricks canvas only live-updates `&` — vars + static CSS paint inner surfaces.
					'css'         => array(
						array(
							'property' => '--ect-bricks-hover-fg',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_image_hover_color_note' => array(
					'type'     => 'info',
					'content'  => esc_html__( 'For featured images, hover color changes the image border color on hover.', 'template-events-calendar' ),
					'required' => self::ect_bricks_require_tab( $image_req, 'style' ),
				),
				'ect_bricks_hover_background'       => array(
					'label'       => esc_html__( 'Hover background', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => '#666666',
					'required'    => self::ect_bricks_require_tab( $hover_interactive_req, 'style' ),
					'rerender'    => true,
					// Bricks canvas only live-updates `&` — vars + static CSS paint buttons/chips.
					'css'         => array(
						array(
							'property' => '--ect-bricks-hover-bg',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_hover_text_decoration'  => array(
					'label'    => esc_html__( 'Text decoration (hover)', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => ECT_Bricks_Hover_Controls::ect_bricks_text_decoration_options(),
					'default'  => '',
					'required' => self::ect_bricks_require_tab( $hover_interactive_req, 'style' ),
					'rerender' => true,
					'css'      => array(
						array(
							'property' => '--ect-bricks-hover-text-decoration',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_hover_animation'        => array(
					'label'    => esc_html__( 'Hover animation', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => ECT_Bricks_Hover_Controls::ect_bricks_hover_animation_options( false, true ),
					'default'  => '',
					'required' => self::ect_bricks_require_tab( $hover_controls_req, 'style' ),
					'rerender' => true,
				),
			);
		}

		private static function ect_bricks_part_image_fields() {
			$image_req = array( 'part', '=', 'image' );

			return array(
				'image_size'              => array(
					'label'       => esc_html__( 'Image size', 'template-events-calendar' ),
					'type'        => 'select',
					'options'     => class_exists( 'ECT_Bricks_Markup', false ) ? \ECT_Bricks_Markup::ect_bricks_image_size_opts() : array(
						'medium' => 'medium',
						'large'  => 'large',
						'full'   => 'full',
					),
					'default'     => 'medium',
					'placeholder' => esc_html__( 'Default (medium — 300×300)', 'template-events-calendar' ),
					'required'    => self::ect_bricks_require_tab( $image_req, 'content' ),
					'rerender'    => true,
				),
				'image_link'              => array(
					'label'    => esc_html__( 'Link image to event', 'template-events-calendar' ),
					'type'     => 'checkbox',
					'default'  => true,
					'required' => self::ect_bricks_require_tab( $image_req, 'content' ),
				),
				'ect_bricks_image_width'  => array(
					'label'       => esc_html__( 'Image width', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => 'auto',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( $image_req, 'style' ),
					'rerender'    => true,
					'css'         => self::ect_bricks_field_css( 'width', '& .ect-fld__img' ),
				),
				'ect_bricks_image_height' => array(
					'label'       => esc_html__( 'Image height', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => 'auto',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( $image_req, 'style' ),
					'rerender'    => true,
					'css'         => self::ect_bricks_field_css( 'height', '& .ect-fld__img' ),
				),
			);
		}

		private static function ect_bricks_part_display_fields( array $venue_parts ) {
			return array(
				'venue_display'      => array(
					'label'    => esc_html__( 'Venue display', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => array(
						'full_details'   => esc_html__( 'Full venue details', 'template-events-calendar' ),
						'name_and_city'  => esc_html__( 'Venue name and city', 'template-events-calendar' ),
						'name_and_state' => esc_html__( 'Venue name and state', 'template-events-calendar' ),
						'name'           => esc_html__( 'Venue name only', 'template-events-calendar' ),
						'full_address'   => esc_html__( 'Full address only', 'template-events-calendar' ),
						'street'         => esc_html__( 'Street', 'template-events-calendar' ),
						'city'           => esc_html__( 'City', 'template-events-calendar' ),
						'state'          => esc_html__( 'State / province', 'template-events-calendar' ),
						'zip'            => esc_html__( 'ZIP / postal', 'template-events-calendar' ),
						'country'        => esc_html__( 'Country', 'template-events-calendar' ),
						'phone'          => esc_html__( 'Phone', 'template-events-calendar' ),
						'website'        => esc_html__( 'Website', 'template-events-calendar' ),
					),
					'default'  => 'full_details',
					'required' => self::ect_bricks_require_tab( array( 'part', '=', $venue_parts ), 'content' ),
				),
				'website_link_text' => array(
					'label'       => esc_html__( 'Link text', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Visit website', 'template-events-calendar' ),
					'description' => esc_html__( 'Links to the venue website. Leave empty to use the URL as the link text.', 'template-events-calendar' ),
					'rerender'    => true,
					'required'    => self::ect_bricks_require_tab(
						array(
							array( 'part', '=', $venue_parts ),
							array( 'venue_display', '=', 'website' ),
						),
						'content'
					),
				),
				'organizer_display'  => array(
					'label'    => esc_html__( 'Organizer display', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => array(
						'full_details' => esc_html__( 'Full organizer details', 'template-events-calendar' ),
						'name'         => esc_html__( 'Organizer name only', 'template-events-calendar' ),
						'email'        => esc_html__( 'Email', 'template-events-calendar' ),
						'phone'        => esc_html__( 'Phone', 'template-events-calendar' ),
						'website'      => esc_html__( 'Website', 'template-events-calendar' ),
					),
					'default'  => 'full_details',
					'required' => self::ect_bricks_require_tab( array( 'part', '=', 'organizer' ), 'content' ),
				),
				'organizer_website_link_text' => array(
					'label'       => esc_html__( 'Link text', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'Visit website', 'template-events-calendar' ),
					'description' => esc_html__( 'Links to the organizer website. Leave empty to use the URL as the link text.', 'template-events-calendar' ),
					'rerender'    => true,
					'required'    => self::ect_bricks_require_tab(
						array(
							array( 'part', '=', 'organizer' ),
							array( 'organizer_display', '=', 'website' ),
						),
						'content'
					),
				),
			);
		}

		public static function ect_bricks_part_fields_for_style2() {
			$options = class_exists( 'ECT_Bricks_Styles', false )
			? \ECT_Bricks_Styles::ect_bricks_get_part_options()
			: array();
			$fields  = self::ect_bricks_get_part_fields( $options, true );
			$out     = array();

			foreach ( $fields as $key => $field ) {
				$out[ $key ] = $field;
				if ( $key === 'ect_bricks_background' ) {
					foreach ( self::ect_bricks_part_fields_style2_meta_icon() as $meta_key => $meta_field ) {
						$out[ $meta_key ] = $meta_field;
					}
				}
			}

			return $out;
		}

		public static function ect_bricks_get_part_fields( ?array $part_options = null, $hide_combo_background = false ) {
			if ( $part_options === null ) {
				$part_options = class_exists( 'ECT_Bricks_Styles', false )
					? \ECT_Bricks_Styles::ect_bricks_part_options_shared()
					: array( 'title' => esc_html__( 'Title', 'template-events-calendar' ) );
			}

			// Part + tab first, then style fields, then content — so STYLE tab controls
			// sit directly under CONTENT|STYLE without hidden content rows in between.
			return array_merge(
				self::ect_bricks_part_selector_fields( $part_options ),
				self::ect_bricks_part_typography_fields( $hide_combo_background ),
				self::ect_bricks_part_button_fields(),
				self::ect_bricks_part_hover_fields(),
				self::ect_bricks_part_image_border_fields(),
				self::ect_bricks_part_content_fields( $part_options )
			);
		}

		public static function ect_bricks_part_fields_style2_meta_icon() {
			$meta_icon_parts_req = array( 'part', '=', ECT_Bricks_Hover_Controls::ect_bricks_style2_meta_icon_ui_parts() );

			return array(
				'ect_bricks_sep_meta_icon'        => array(
					'type'     => 'separator',
					'label'    => esc_html__( 'Meta icon', 'template-events-calendar' ),
					'required' => self::ect_bricks_require_tab( $meta_icon_parts_req, 'style' ),
				),
				'ect_bricks_meta_icon_color'      => array(
					'label'       => esc_html__( 'Icon color', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => '#0d55d8',
					'responsive'  => true,
					'rerender'    => true,
					'required'    => self::ect_bricks_require_tab( $meta_icon_parts_req, 'style' ),
					'css'         => self::ect_bricks_repeater_var_css( '--ect-bricks-meta-icon-color' ),
				),
				'ect_bricks_meta_icon_background' => array(
					'label'       => esc_html__( 'Icon background', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => '#eaf2ff',
					'responsive'  => true,
					'rerender'    => true,
					'required'    => self::ect_bricks_require_tab( $meta_icon_parts_req, 'style' ),
					'css'         => self::ect_bricks_repeater_var_css( '--ect-bricks-meta-icon-bg' ),
				),
			);
		}

		private static function ect_bricks_part_text_fields() {
			return array(
				'link'              => array(
					'label'    => esc_html__( 'Link title to event', 'template-events-calendar' ),
					'type'     => 'checkbox',
					'required' => self::ect_bricks_require_tab( array( 'part', '=', 'title' ), 'content' ),
				),
				'terms_separator'   => array(
					'label'       => esc_html__( 'Separator', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => ', ',
					'default'     => ', ',
					'required'    => self::ect_bricks_require_tab( array( 'part', '=', 'tags' ), 'content' ),
				),
				'read_more_sep'     => array(
					'type'     => 'separator',
					'label'    => esc_html__( 'Read more', 'template-events-calendar' ),
					'required' => self::ect_bricks_require_tab( array( 'part', '=', 'read_more' ), 'content' ),
				),
				'read_more_text'    => array(
					'label'       => esc_html__( 'Read more text', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => esc_html__( 'View Details', 'template-events-calendar' ),
					'required'    => self::ect_bricks_require_tab( array( 'part', '=', 'read_more' ), 'content' ),
				),
			);
		}

		/**
		 * Combined venue/time/cost part slugs (Style 2: hide row background control).
		 *
		 * @return string[]
		 */
		private static function ect_bricks_meta_combo_slugs_for_controls() {
			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				return \ECT_Bricks_Styles::ect_bricks_meta_combo_all_slugs();
			}

			return \ECT_Bricks_Value_Utils::LEGACY_META_COMBO_SLUGS;
		}

		private static function ect_bricks_part_typography_fields( $hide_combo_background = false ) {
			$bg_required = array( array( 'part', '!=', 'image' ) );
			$combo_slugs = self::ect_bricks_meta_combo_slugs_for_controls();
			if ( $hide_combo_background ) {
				foreach ( $combo_slugs as $combo_slug ) {
					$bg_required[] = array( 'part', '!=', $combo_slug );
				}
			}

			$not_combo_typography_required = array( array( 'part', '!=', 'image' ) );
			foreach ( $combo_slugs as $combo_slug ) {
				$not_combo_typography_required[] = array( 'part', '!=', $combo_slug );
			}

			return array(
				'ect_bricks_typography' => array(
					'label'      => esc_html__( 'Typography', 'template-events-calendar' ),
					'type'       => 'typography',
					'exclude'    => array( 'text-align', 'color' ),//phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
					'responsive' => true,
					'required'   => self::ect_bricks_require_tab( $not_combo_typography_required, 'style' ),
					'rerender'   => true,
					// Color is a separate control. Size/transform/line-height come from this CSS map
					// so Bricks can live-update them without sticky PHP inline copies.
					'css'        => array(
						array(
							'property' => 'typography',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_typography_combo' => array(
					'label'      => esc_html__( 'Typography', 'template-events-calendar' ),
					'type'       => 'typography',
					'exclude'    => array( 'text-align', 'color', 'text-decoration' ),//phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
					'responsive' => true,
					'required'   => self::ect_bricks_require_tab(
						array(
							array( 'part', '!=', 'image' ),
							array( 'part', '=', $combo_slugs ),
						),
						'style'
					),
					'rerender'   => true,
					'css'        => array(
						array(
							'property' => 'typography',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_text_color' => array(
					'label'       => esc_html__( 'Text color', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => esc_html__( 'Default', 'template-events-calendar' ),
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( array( 'part', '!=', 'image' ), 'style' ),
					'rerender'    => true,
					'css'         => array(
						array(
							'property' => '--ect-bricks-btn-fg',
							'selector' => '&',
						),
						array(
							'property' => '--ect-bricks-chip-fg',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_text_align' => array(
					'label'      => esc_html__( 'Alignments', 'template-events-calendar' ),
					'type'       => 'text-align',
					'responsive' => true,
					'required'   => self::ect_bricks_require_tab( null, 'style' ),
					'rerender'   => true,
					'css'        => array_merge(
						self::ect_bricks_field_css( 'text-align', '&' ),
						self::ect_bricks_field_css( 'justify-content', '&' )
					),
				),
				'ect_bricks_background' => array(
					'label'       => esc_html__( 'Background', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => 'transparent',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( $bg_required, 'style' ),
					'rerender'    => true,
					// Bricks canvas only live-updates `&` — never set background-color here
					// (it paints the full-width CTA wrapper). Vars + static CSS paint surfaces.
					'css'         => array(
						array(
							'property' => '--ect-bricks-btn-bg',
							'selector' => '&',
						),
						array(
							'property' => '--ect-bricks-chip-bg',
							'selector' => '&',
						),
					),
				),
				'ect_bricks_margin'     => array(
					'label'    => esc_html__( 'Margin', 'template-events-calendar' ),
					'type'     => 'spacing',
					'required' => self::ect_bricks_require_tab( null, 'style' ),
					'css'      => self::ect_bricks_field_css( 'margin' ),
				),
				'ect_bricks_padding'    => array(
					'label'    => esc_html__( 'Padding', 'template-events-calendar' ),
					'type'     => 'spacing',
					'required' => self::ect_bricks_require_tab( null, 'style' ),
					'css'      => self::ect_bricks_field_css( 'padding' ),
				),
			);
		}

		public static function ect_bricks_part_fields_for_style1() {
			$options = class_exists( 'ECT_Bricks_Styles', false )
			? \ECT_Bricks_Styles::ect_bricks_get_part_options()
			: array();

			return self::ect_bricks_get_part_fields( $options );
		}

		private static function ect_bricks_part_date_fields( array $date_parts, array $date_format_options, array $time_format_options ) {
			return array(
				'date_display'       => array(
					'label'    => esc_html__( 'Visibility', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => array(
						'day_time_range' => esc_html__( 'Time', 'template-events-calendar' ),
						'date'           => esc_html__( 'Date', 'template-events-calendar' ),
					),
					'default'  => 'day_time_range',
					'rerender' => true,
					'required' => self::ect_bricks_require_tab( array( 'part', '=', $date_parts ), 'content' ),
				),
				'date_format_preset' => array(
					'label'    => esc_html__( 'Date Format', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => $date_format_options,
					'default'  => '',
					'required'    => self::ect_bricks_require_tab(
						array(
							array( 'part', '=', $date_parts ),
							array( 'date_display', '=', array( 'date', 'range' ) ),
						),
						'content'
					),
				),
				'date_format_custom' => array(
					'label'       => esc_html__( 'Custom PHP format', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => 'F j, Y',
					'required'    => self::ect_bricks_require_tab(
						array(
							array( 'part', '=', $date_parts ),
							array( 'date_display', '=', array( 'date', 'range' ) ),
							array( 'date_format_preset', '=', 'custom' ),
						),
						'content'
					),
				),
				'time_format_preset' => array(
					'label'    => esc_html__( 'Time Format', 'template-events-calendar' ),
					'type'     => 'select',
					'options'  => $time_format_options,
					'default'  => '',
					'required' => self::ect_bricks_require_tab(
						array(
							array( 'part', '=', $date_parts ),
							array( 'date_display', '=', array( 'day_time_range', 'time' ) ),
						),
						'content'
					),
				),
				'time_format_custom' => array(
					'label'       => esc_html__( 'Custom PHP format', 'template-events-calendar' ),
					'type'        => 'text',
					'placeholder' => 'g:i a',
					'description' => esc_html__( 'Time-only PHP format (e.g. g:i a, H:i).', 'template-events-calendar' ),
					'required'    => self::ect_bricks_require_tab(
						array(
							array( 'part', '=', $date_parts ),
							array( 'date_display', '=', array( 'day_time_range', 'time' ) ),
							array( 'time_format_preset', '=', 'custom' ),
						),
						'content'
					),
				),
			);
		}

		private static function ect_bricks_part_content_fields( ?array $part_options = null ) {
			$date_format_options = class_exists( 'ECT_Bricks_Styles', false )
			? \ECT_Bricks_Styles::ect_bricks_date_options()
			: array();
			$time_format_options = class_exists( 'ECT_Bricks_Styles', false )
			? \ECT_Bricks_Styles::ect_bricks_time_options()
			: array();

			if ( $part_options === null ) {
				$part_options = class_exists( 'ECT_Bricks_Styles', false )
					? \ECT_Bricks_Styles::ect_bricks_part_options_shared()
					: array( 'title' => esc_html__( 'Title', 'template-events-calendar' ) );
			}

			$venue_parts = array( 'venue' );
			$date_parts  = array( 'date' );
			if ( class_exists( 'ECT_Bricks_Styles', false ) ) {
				$venue_parts = array_merge( $venue_parts, \ECT_Bricks_Styles::ect_bricks_meta_combo_slugs_with_segment( 'venue' ) );
				$date_parts  = array_merge( $date_parts, \ECT_Bricks_Styles::ect_bricks_meta_combo_slugs_with_segment( 'time' ) );
			}

			return array_merge(
				self::ect_bricks_part_date_fields( $date_parts, $date_format_options, $time_format_options ),
				self::ect_bricks_part_display_fields( $venue_parts ),
				self::ect_bricks_part_text_fields(),
				self::ect_bricks_part_image_fields()
			);
		}

		private static function ect_bricks_part_image_surface_selectors() {
			return '& .ect-fld__img';
		}

		private static function ect_bricks_part_image_border_fields() {
			$img_selector = self::ect_bricks_part_image_surface_selectors();

			return array(
				'ect_bricks_image_border' => array(
					'label'      => esc_html__( 'Image border', 'template-events-calendar' ),
					'type'       => 'border',
					'responsive' => true,
					'required'   => self::ect_bricks_require_tab( array( 'part', '=', 'image' ), 'style' ),
					'rerender'   => true,
					'css'        => array(
						array(
							'property' => 'border',
							'selector' => $img_selector,
						),
						array(
							'property' => 'border-radius',
							'selector' => $img_selector,
						),
					),
				),
				'ect_bricks_image_radius' => array(
					'label'       => esc_html__( 'Image radius', 'template-events-calendar' ),
					'type'        => 'dimensions',
					'placeholder' => '0px',
					'responsive'  => true,
					'required'    => self::ect_bricks_require_tab( array( 'part', '=', 'image' ), 'style' ),
					'rerender'    => true,
					'css'         => self::ect_bricks_field_css( 'border-radius', $img_selector ),
				),
			);
		}

		private static function ect_bricks_part_selector_fields( array $part_options ) {
			return array(
				'part'           => array(
					'label'     => esc_html__( 'Part', 'template-events-calendar' ),
					'type'      => 'select',
					'options'   => $part_options,
					'default'   => 'title',
					'separator' => false,
				),
				// Builder-panel-only view switch: has no `css`/frontend effect,
				// it only exists so every other field's `required` below can
				// gate on it — Bricks' native conditional-visibility engine
				// then handles show/hide reactively, no custom JS/CSS needed
				// (replaces the old hand-built CONTENT/STYLE pill bar).
				// Type `toggle` is Bricks' native segmented-buttons control
				// (same one Bricks uses for its GET/POST switch); it renders
				// each option as a clickable pill using builder design tokens.
				'ect_bricks_tab' => array(
					'label'     => '',
					'type'      => 'toggle',
					'separator' => false,
					'options'   => array(
						'content' => esc_html__( 'CONTENT', 'template-events-calendar' ),
						'style'   => esc_html__( 'STYLE', 'template-events-calendar' ),
					),
					'default'   => 'content',
				),
			);
		}

		public static function ect_bricks_field_css( $css_property, $css_selector = '' ) {
			$css_rule = array( 'property' => (string) $css_property );
			if ( $css_selector !== '' ) {
				$css_rule['selector'] = $css_selector;
			}
			return array( $css_rule );
		}

		/**
		 * Button chrome surfaces (padding, radius) — paint the link, not wrapper CSS vars.
		 */
		public static function ect_bricks_repeater_btn_surface_selectors() {
			return '& a.ect-card__btn, & > a.ect-card__btn, '
				. '& a.event-button, & > a.event-button, '
				. '& .ect-fld__link.ect-card__btn, & > .ect-fld__link.ect-card__btn, '
				. '& .ect-fld__link, & > .ect-fld__link';
		}

		/**
		 * Bricks-native repeater CSS: map a control value to a custom property on `&`.
		 *
		 * @param string $custom_property CSS custom property name (e.g. --ect-bricks-btn-padding).
		 * @return array<int,array<string,string>>
		 */
		public static function ect_bricks_repeater_var_css( $custom_property ) {
			return array(
				array(
					'property' => (string) $custom_property,
					'selector' => '&',
				),
			);
		}

		/**
		 * AND the CONTENT/STYLE tab condition onto a field's existing
		 * `required` value, normalizing both possible shapes Bricks accepts
		 * ('required' as one [key, op, value] triplet, or an array of them)
		 * into a flat array of triplets — never nested.
		 *
		 * Bricks never stores a control's `default` in settings (and its
		 * toggle control even deletes the value when the active option is
		 * clicked again), so `ect_bricks_tab` is simply ABSENT on fresh rows.
		 * A `= content` check would fail against that empty value, hiding
		 * every content field. Using `!= style` for the content tab makes
		 * "unset" correctly mean CONTENT.
		 *
		 * @param array|null $base_required Existing 'required' value, or null.
		 * @param string     $tab           'content' or 'style'.
		 * @return array
		 */
		private static function ect_bricks_require_tab( $base_required, $tab ) {
			$tab_condition = $tab === 'content'
				? array( 'ect_bricks_tab', '!=', 'style' )
				: array( 'ect_bricks_tab', '=', 'style' );

			if ( empty( $base_required ) ) {
				return $tab_condition;
			}

			$is_single_condition = isset( $base_required[0] ) && ! is_array( $base_required[0] );
			$conditions          = $is_single_condition ? array( $base_required ) : $base_required;
			$conditions[]        = $tab_condition;

			return $conditions;
		}
	}
}
