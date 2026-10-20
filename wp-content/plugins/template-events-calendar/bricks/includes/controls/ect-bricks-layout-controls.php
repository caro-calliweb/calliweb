<?php
/**
 * ECT_Bricks_Layout_Controls service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
if ( ! class_exists( 'ECT_Bricks_Layout_Controls', false ) ) {

	final class ECT_Bricks_Layout_Controls {

		/**
		 * Events Shortcodes Pro landing URL with campaign tags.
		 *
		 * @param string $utm_content utm_content value.
		 * @return string
		 */
		public static function ect_bricks_get_pro_url( $utm_content ) {
			return add_query_arg(
				array(
					'utm_source'   => 'ect_plugin',
					'utm_medium'   => 'inside',
					'utm_campaign' => 'get_pro',
					'utm_content'  => sanitize_key( $utm_content ),
				),
				'https://eventscalendaraddons.com/plugin/events-shortcodes-pro/'
			);
		}

		/**
		 * Compact Get Pro helper used as native control description text.
		 *
		 * @param string $url        Destination URL.
		 * @param string $after_link Unlinked trailing copy (already translated).
		 * @return string
		 */
		public static function ect_bricks_get_pro_notice_html( $url, $after_link ) {
			$link = '<a class="ect-bricks-get-pro-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Get Pro', 'template-events-calendar' ) . '</a>';

			return $link . ' ' . esc_html( $after_link );
		}

		/**
		 * Display-only Pro teaser controls (no CSS, no rerender, not responsive).
		 *
		 * @param object              $element Bricks element.
		 * @param string              $key     Control key.
		 * @param array<string,mixed> $control Control config.
		 * @return void
		 */
		public static function ect_bricks_register_pro_lock_control( $element, $key, array $control ) {
			unset( $control['css'] );
			$control['rerender']   = false;
			$control['responsive'] = false;
			$element->controls[ $key ] = $control;
		}

		/**
		 * Control keys wrapped by the gold Upgrade to Pro overlay.
		 * Each group keeps its own Get Pro tracking URL.
		 *
		 * @return array<int,array{keys:string[],url:string}>
		 */
		public static function ect_bricks_pro_lock_groups() {
			return array(
				array(
					'keys' => array(
						'show_pagination',
						'pagination_type',
						'pagination_alignment',
						'pagination_margin_top',
						'pagination_color',
						'pagination_background',
						'pagination_active_color',
						'pagination_active_background',
						'pagination_padding',
						'pagination_hover_animation',
					),
					'url'  => self::ect_bricks_get_pro_url( 'bricks_pagination_editor_panel' ),
				),
				array(
					'keys' => array( 'event_featured' ),
					'url'  => self::ect_bricks_get_pro_url( 'bricks_featured_editor_panel' ),
				),
				array(
					'keys' => array(
						'ect_bricks_sep_featured_event',
						'ect_bricks_featured_event_background',
						'ect_bricks_featured_event_text_color',
					),
					'url'  => self::ect_bricks_get_pro_url( 'bricks_featured_style_panel' ),
				),
			);
		}

		private static function ect_bricks_register_template_controls( $element ) {
			$element->controls['layout_template'] = array(
				'tab'         => 'content',
				'group'       => 'layouts',
				'label'       => esc_html__( 'Template', 'template-events-calendar' ),
				'type'        => 'select',
				'options'     => array(	
					'list' => esc_html__( 'List', 'template-events-calendar' ),
					'grid' => esc_html__( 'Grid (PRO only)', 'template-events-calendar' ),
				),
				'inline'      => true,
				'default'     => 'list',
				'description' => self::ect_bricks_get_pro_notice_html(
					self::ect_bricks_get_pro_url( 'bricks_layout_editor_panel' ),
					__( 'to unlock more Advanced Layouts', 'template-events-calendar' )
				),
			);

			$element->controls['list_item_style'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'List style', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => array(
					'style-1' => esc_html__( 'Style 1', 'template-events-calendar' ),
					'style-2' => esc_html__( 'Style 2', 'template-events-calendar' ),
				),
				'default'  => 'style-1',
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_layout_is_list(),
			);
		}

		private static function ect_bricks_register_layout_display_controls( $element ) {
			$element->controls['hide_event_image'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'Hide featured image', 'template-events-calendar' ),
				'type'     => 'checkbox',
				'default'  => false,
				'rerender' => true,
			);

			ECT_Bricks_Card_Style_Controls::ect_bricks_register_category_badge_layout_toggle( $element );

			$date_column_order_options = array(
				'month_day' => esc_html__( 'Month above, date below', 'template-events-calendar' ),
				'day_month' => esc_html__( 'Date above, month below', 'template-events-calendar' ),
			);

			$element->controls['list1_show_date_column'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'Hide date column', 'template-events-calendar' ),
				'type'     => 'checkbox',
				'default'  => false,
				'rerender' => true,
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_list_style( 'style-1' ),
			);

			$element->controls['list1_date_column_order'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'Date column order', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => $date_column_order_options,
				'default'  => 'day_month',
				'inline'   => true,
				'rerender' => true,
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_list1_date_column_style(),
			);

			$element->controls['style2_show_date_badge'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'Hide date badge on image', 'template-events-calendar' ),
				'type'     => 'checkbox',
				'default'  => false,
				'rerender' => true,
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_list_style_with_image( 'style-2' ),
			);

			$element->controls['style2_date_badge_order'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'Date badge order', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => $date_column_order_options,
				'default'  => 'month_day',
				'inline'   => true,
				'rerender' => true,
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_shell_date_badge_style(),
			);

			$element->controls['item_gap'] = array(
				'tab'         => 'content',
				'group'       => 'layouts',
				'label'       => esc_html__( 'Gap between events', 'template-events-calendar' ),
				'type'        => 'number',
				'min'         => 0,
				'step'        => 1,
				'placeholder' => '24',
				'default'     => 24,
				'units'       => array(
					'px'  => 'px',
					'rem' => 'rem',
					'em'  => 'em',
				),
				'unit'        => 'px',
				'responsive'  => true,
				'rerender'    => true,
				'description' => esc_html__( 'Space between each event card. Click the device icon on this control for tablet/mobile.', 'template-events-calendar' ),
				'css'         => array(
					array(
						'property' => '--ect-bricks-gap',
						'selector' => '&',
					),
				),
			);
		}

		private static function ect_bricks_register_pagination_upsell( $element ) {
			$base = array(
				'tab'   => 'content',
				'group' => 'pagination',
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'show_pagination',
				array_merge(
					$base,
					array(
						'label'   => esc_html__( 'Show Pagination', 'template-events-calendar' ),
						'type'    => 'checkbox',
						'inline'  => true,
						'default' => true,
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_type',
				array_merge(
					$base,
					array(
						'label'   => esc_html__( 'Select Pagination', 'template-events-calendar' ),
						'type'    => 'select',
						'options' => array(
							'default' => esc_html__( 'Default', 'template-events-calendar' ),
							'ajax'    => esc_html__( 'Load More Button', 'template-events-calendar' ),
						),
						'inline'  => true,
						'default' => 'default',
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_alignment',
				array_merge(
					$base,
					array(
						'label'   => esc_html__( 'Alignment', 'template-events-calendar' ),
						'type'    => 'justify-content',
						'exclude' => 'space',
						'inline'  => true,
						'default' => 'center',
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_margin_top',
				array_merge(
					$base,
					array(
						'label'       => esc_html__( 'Distance From Top', 'template-events-calendar' ),
						'type'        => 'number',
						'units'       => true,
						'placeholder' => 24,
						'inline'      => true,
					)
				)
			);

			// Compact teaser only — full color / spacing controls live in Pro.
			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_color',
				array_merge(
					$base,
					array(
						'label'       => esc_html__( 'Color', 'template-events-calendar' ),
						'type'        => 'text',
						'inline'      => true,
						'placeholder' => esc_html__( 'Color value', 'template-events-calendar' ),
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_background',
				array_merge(
					$base,
					array(
						'label'       => esc_html__( 'Background Color', 'template-events-calendar' ),
						'type'        => 'text',
						'inline'      => true,
						'placeholder' => esc_html__( 'Color value', 'template-events-calendar' ),
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_active_color',
				array_merge(
					$base,
					array(
						'label'       => esc_html__( 'Active Color', 'template-events-calendar' ),
						'type'        => 'text',
						'inline'      => true,
						'placeholder' => esc_html__( 'Color value', 'template-events-calendar' ),
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_active_background',
				array_merge(
					$base,
					array(
						'label'       => esc_html__( 'Active Background Color', 'template-events-calendar' ),
						'type'        => 'text',
						'inline'      => true,
						'placeholder' => esc_html__( 'Color value', 'template-events-calendar' ),
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_padding',
				array_merge(
					$base,
					array(
						'label'   => esc_html__( 'Padding', 'template-events-calendar' ),
						'type'    => 'text',
						'inline'  => true,
						'default' => '8 20 8 20',
					)
				)
			);

			self::ect_bricks_register_pro_lock_control(
				$element,
				'pagination_hover_animation',
				array_merge(
					$base,
					array(
						'label'   => esc_html__( 'Animation', 'template-events-calendar' ),
						'type'    => 'select',
						'options' => ECT_Bricks_Hover_Controls::ect_bricks_hover_animation_options( false, true ),
						'inline'  => true,
						'default' => 'none',
					)
				)
			);
		}

		public static function ect_bricks_register_layout_controls( $element ) {
			self::ect_bricks_register_template_controls( $element );
			self::ect_bricks_register_layout_display_controls( $element );
			self::ect_bricks_register_pagination_upsell( $element );
		}
	}
}
