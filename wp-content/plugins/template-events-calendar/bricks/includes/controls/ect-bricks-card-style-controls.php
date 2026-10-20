<?php
/**
 * ECT_Bricks_Card_Style_Controls service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude	
if ( ! class_exists( 'ECT_Bricks_Card_Style_Controls', false ) ) {

	final class ECT_Bricks_Card_Style_Controls {

		private static function ect_bricks_style_card_selectors() {
			return array(
				'& .ect-list',
				'& .ect-card',
			);
		}

		private static function ect_bricks_register_events_card_style_controls( $element ) {
			$card_sel = self::ect_bricks_style_card_selector();

			$element->controls['ect_bricks_card_background'] = array(
				'tab'         => 'style',
				'group'       => 'events_card',
				'label'       => esc_html__( 'Background', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => '#ffffff',
				'responsive'  => true,
				'css'         => array(
					array(
						'property' => '--ect-bricks-card-bg',
						'selector' => '&',
					),
					array(
						'property' => 'background-color',
						'selector' => $card_sel,
					),
				),
			);

			ECT_Bricks_Layout_Controls::ect_bricks_register_pro_lock_control(
				$element,
				'ect_bricks_sep_featured_event',
				array(
					'tab'   => 'style',
					'group' => 'events_card',
					'label' => esc_html__( 'Featured events', 'template-events-calendar' ),
					'type'  => 'separator',
				)
			);

			ECT_Bricks_Layout_Controls::ect_bricks_register_pro_lock_control(
				$element,
				'ect_bricks_featured_event_background',
				array(
					'tab'         => 'style',
					'group'       => 'events_card',
					'label'       => esc_html__( 'Background', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => esc_html__( 'Default', 'template-events-calendar' ),
				)
			);

			ECT_Bricks_Layout_Controls::ect_bricks_register_pro_lock_control(
				$element,
				'ect_bricks_featured_event_text_color',
				array(
					'tab'         => 'style',
					'group'       => 'events_card',
					'label'       => esc_html__( 'Text color', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => esc_html__( 'Default', 'template-events-calendar' ),
				)
			);

			$element->controls['ect_bricks_sep_card_border'] = array(
				'tab'   => 'style',
				'group' => 'events_card',
				'label' => esc_html__( 'Border', 'template-events-calendar' ),
				'type'  => 'separator',
			);

			$element->controls['ect_bricks_card_border_width'] = array(
				'tab'         => 'style',
				'group'       => 'events_card',
				'label'       => esc_html__( 'Border width', 'template-events-calendar' ),
				'type'        => 'number',
				'units'       => array( 'px' ),
				'unit'        => 'px',
				'placeholder' => '1',
				'responsive'  => true,
				'css'         => array(
					array(
						'property' => '--ect-bricks-card-border-width',
						'selector' => '&',
					),
					array(
						'property' => 'border-width',
						'selector' => $card_sel,
					),
				),
			);

			$element->controls['ect_bricks_card_border_color'] = array(
				'tab'         => 'style',
				'group'       => 'events_card',
				'label'       => esc_html__( 'Border color', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => '#e5eaf2',
				'responsive'  => true,
				'css'         => array(
					array(
						'property' => '--ect-bricks-card-border-color',
						'selector' => '&',
					),
					array(
						'property' => 'border-color',
						'selector' => $card_sel,
					),
				),
			);

			$element->controls['ect_bricks_card_padding'] = array(
				'tab'        => 'style',
				'group'      => 'events_card',
				'label'      => esc_html__( 'Padding', 'template-events-calendar' ),
				'type'       => 'spacing',
				'responsive' => true,
				'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'padding', $card_sel ),
			);

			$element->controls['ect_bricks_card_margin'] = array(
				'tab'        => 'style',
				'group'      => 'events_card',
				'label'      => esc_html__( 'Margin', 'template-events-calendar' ),
				'type'       => 'spacing',
				'responsive' => true,
				'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'margin', '& .ect-ev__item' ),
			);

			$element->controls['ect_bricks_sep_card_hover'] = array(
				'tab'   => 'style',
				'group' => 'events_card',
				'label' => esc_html__( 'Card hover', 'template-events-calendar' ),
				'type'  => 'separator',
			);

			$element->controls['ect_bricks_card_hover_animation'] = ECT_Bricks_Hover_Controls::ect_bricks_hover_animation_control( 'events_card' );

			$element->controls['ect_bricks_sep_style2_divider'] = array(
				'tab'      => 'style',
				'group'    => 'events_card',
				'label'    => esc_html__( 'Separator', 'template-events-calendar' ),
				'type'     => 'separator',
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_style2_divider_style(),
			);

			$element->controls['ect_bricks_style2_divider_color'] = array(
				'tab'         => 'style',
				'group'       => 'events_card',
				'label'       => esc_html__( 'Separator color', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => '#edf1f7',
				'responsive'  => true,
				'required'    => ECT_Bricks_Hover_Controls::ect_bricks_req_style2_divider_style(),
				'css'         => array(
					array(
						'property' => 'background-color',
						'selector' => '& .ect-card__divider',
					),
				),
			);
		}

		private static function ect_bricks_style_card_selector() {
			return implode( ', ', self::ect_bricks_style_card_selectors() );
		}

		private static function ect_bricks_shell_setting_key( $prefix, $name ) {
			return $prefix . $name;
		}

		/**
		 * Shared Style-tab chrome for image-shell badges (category / date).
		 *
		 * @param object              $element Bricks element.
		 * @param array<string,mixed> $config  {
		 *     @type string           $prefix             Setting key prefix.
		 *     @type string           $separator_key      Control key for the section separator.
		 *     @type string           $separator_label    Separator label.
		 *     @type array            $required           Bricks required conditions.
		 *     @type string           $target             CSS selector for the badge.
		 *     @type string           $wrap               Optional wrap selector (margin).
		 *     @type string           $var_prefix         CSS custom-property prefix (e.g. --ect-bricks-shell-cat).
		 *     @type string[]         $fields             typography|color|background|padding|margin|hover.
		 *     @type array            $placeholders       Optional color placeholders.
		 *     @type string[]         $typography_exclude Typography exclude list.
		 *     @type array            $typography_extra_css Extra CSS rows for typography.
		 *     @type string           $typography_selector Override typography selector.
		 * }
		 * @return void
		 */
		private static function ect_bricks_register_shell_badge_style_controls( $element, array $config ) {
			$prefix     = (string) ( $config['prefix'] ?? '' );
			$required   = $config['required'] ?? array();
			$target     = (string) ( $config['target'] ?? '' );
			$wrap       = (string) ( $config['wrap'] ?? $target );
			$var_prefix = (string) ( $config['var_prefix'] ?? '' );
			$fields     = $config['fields'] ?? array();
			$ph         = $config['placeholders'] ?? array();
			$typo_sel   = (string) ( $config['typography_selector'] ?? $target );
			$typo_extra = $config['typography_extra_css'] ?? array();
			$typo_excl  = $config['typography_exclude'] ?? array( 'text-align' );
			$key        = static function ( $name ) use ( $prefix ) {
				return self::ect_bricks_shell_setting_key( $prefix, $name );
			};

			$element->controls[ (string) ( $config['separator_key'] ?? $key( 'sep' ) ) ] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => (string) ( $config['separator_label'] ?? '' ),
				'type'     => 'separator',
				'required' => $required,
			);

			$field_map = array(
				'typography' => array(
					'tab'        => 'style',
					'group'      => 'featured_image',
					'label'      => esc_html__( 'Typography', 'template-events-calendar' ),
					'type'       => 'typography',
					'exclude'    => $typo_excl,
					'responsive' => true,
					'required'   => $required,
					'css'        => array_merge(
						array(
							array(
								'property' => 'typography',
								'selector' => $typo_sel,
							),
						),
						$typo_extra
					),
				),
				'color'      => array(
					'tab'         => 'style',
					'group'       => 'featured_image',
					'label'       => esc_html__( 'Color', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => (string) ( $ph['color'] ?? '#ffffff' ),
					'responsive'  => true,
					'required'    => $required,
					// Direct paint on badges only — avoid CSS vars on `&` so Bricks `:hover`
					// cannot retarget every card via `#brxe:hover { --shell-cat-color }`.
					// Control keys containing `_hover` are treated as Color:hover, so a var
					// on the widget root would recolor every image category on widget hover.
					'css'         => array(
						array(
							'property' => 'color',
							'selector' => $target,
						),
					),
				),
				'background' => array(
					'tab'         => 'style',
					'group'       => 'featured_image',
					'label'       => esc_html__( 'Background', 'template-events-calendar' ),
					'type'        => 'color',
					'placeholder' => (string) ( $ph['background'] ?? '#244ee7' ),
					'responsive'  => true,
					'required'    => $required,
					'css'         => array(
						array(
							'property' => 'background-color',
							'selector' => $target,
						),
					),
				),
				'padding'    => array(
					'tab'        => 'style',
					'group'      => 'featured_image',
					'label'      => esc_html__( 'Padding', 'template-events-calendar' ),
					'type'       => 'spacing',
					'responsive' => true,
					'required'   => $required,
					'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'padding', $target ),
				),
				'margin'     => array(
					'tab'        => 'style',
					'group'      => 'featured_image',
					'label'      => esc_html__( 'Margin', 'template-events-calendar' ),
					'type'       => 'spacing',
					'responsive' => true,
					'required'   => $required,
					'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'margin', $wrap ),
				),
			);

			foreach ( $fields as $field ) {
				if ( 'hover' === $field ) {
					self::ect_bricks_register_shell_badge_hover_controls( $element, $key, $required, $var_prefix, $ph );
					continue;
				}
				if ( isset( $field_map[ $field ] ) ) {
					$element->controls[ $key( $field ) ] = $field_map[ $field ];
				}
			}
		}

		/**
		 * @param object   $element
		 * @param callable $key
		 * @param array    $required
		 * @param string   $var_prefix
		 * @param array    $ph
		 * @return void
		 */
		private static function ect_bricks_register_shell_badge_hover_controls( $element, $key, array $required, $var_prefix, array $ph ) {
			$element->controls[ $key( 'sep_hover' ) ] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => esc_html__( 'Category hover', 'template-events-calendar' ),
				'type'     => 'separator',
				'required' => $required,
			);

			// Store hover tokens on the root only. Never use `& .event-badge--blue:hover` in
			// Bricks `css` maps — Bricks rewrites that to `#brxe:hover .event-badge--blue`
			// and every badge in the widget changes when any card is hovered.
			// Actual paint happens in base CSS on `.event-badge--blue:hover` only.
			$element->controls[ $key( 'hover_color' ) ] = array(
				'tab'         => 'style',
				'group'       => 'featured_image',
				'label'       => esc_html__( 'Hover color', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => (string) ( $ph['hover_color'] ?? '#ffffff' ),
				'responsive'  => true,
				'required'    => $required,
				'css'         => array(
					array(
						'property' => $var_prefix . '-hover-color',
						'selector' => '&',
					),
				),
			);

			$element->controls[ $key( 'hover_background' ) ] = array(
				'tab'         => 'style',
				'group'       => 'featured_image',
				'label'       => esc_html__( 'Hover background', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => (string) ( $ph['hover_background'] ?? '#1a3fc4' ),
				'responsive'  => true,
				'required'    => $required,
				'css'         => array(
					array(
						'property' => $var_prefix . '-hover-bg',
						'selector' => '&',
					),
				),
			);

			$element->controls[ $key( 'hover_text_decoration' ) ] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => esc_html__( 'Text decoration (hover)', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => ECT_Bricks_Hover_Controls::ect_bricks_text_decoration_options(),
				'default'  => '',
				'required' => $required,
				'css'      => array(
					array(
						'property' => $var_prefix . '-hover-text-decoration',
						'selector' => '&',
					),
				),
			);

			$element->controls[ $key( 'hover_animation' ) ] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => esc_html__( 'Hover animation', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => ECT_Bricks_Hover_Controls::ect_bricks_hover_animation_options( false, true ),
				'default'  => '',
				'rerender' => true,
				'required' => $required,
			);
		}

		private static function ect_bricks_register_category_badge_style_controls( $element ) {
			self::ect_bricks_register_shell_badge_style_controls(
				$element,
				array(
					'prefix'               => 'ect_bricks_shell_category_',
					'separator_key'        => 'ect_bricks_sep_style_image_category_list1',
					'separator_label'      => esc_html__( 'Image category', 'template-events-calendar' ),
					'required'             => ECT_Bricks_Hover_Controls::ect_bricks_req_shell_category_style_list1(),
					'target'               => '& .event-badge--blue',
					'wrap'                 => '& .event-badge',
					'var_prefix'           => '--ect-bricks-shell-cat',
					'fields'               => array( 'typography', 'color', 'background', 'padding', 'margin', 'hover' ),
					'typography_exclude'   => array( 'text-align', 'color' ),
					'typography_extra_css' => array(
						array(
							'property' => '--ect-bricks-shell-cat-font-size',
							'selector' => '& .event-badge--blue',
						),
						array(
							'property' => '--ect-bricks-shell-cat-line-height',
							'selector' => '& .event-badge--blue',
						),
						array(
							'property' => '--ect-bricks-shell-cat-text-transform',
							'selector' => '& .event-badge--blue',
						),
					),
					'placeholders'         => array(
						'color'            => '#ffffff',
						'background'       => '#244ee7',
						'hover_color'      => '#ffffff',
						'hover_background' => '#1a3fc4',
					),
				)
			);
		}

		public static function ect_bricks_register_category_badge_layout_toggle( $element ) {
			$element->controls['list1_show_category_badge'] = array(
				'tab'      => 'content',
				'group'    => 'layouts',
				'label'    => esc_html__( 'Hide category on image', 'template-events-calendar' ),
				'type'     => 'checkbox',
				'default'  => false,
				'rerender' => true,
				'required' => ECT_Bricks_Hover_Controls::ect_bricks_req_list_style_with_image( 'style-1' ),
			);
		}

		private static function ect_bricks_register_image_dimension_controls( $element, array $image_required ) {
			$shared_number = array(
				'tab'        => 'style',
				'group'      => 'featured_image',
				'type'       => 'number',
				'min'        => 0,
				'step'       => 1,
				'units'      => array(
					'px' => 'px',
					'vh' => 'vh',
				),
				'unit'       => 'px',
				'responsive' => true,
				'required'   => $image_required,
			);

			$element->controls['ect_bricks_featured_image_min_height'] = array_merge(
				$shared_number,
				array(
					'label'       => esc_html__( 'Min height', 'template-events-calendar' ),
					'placeholder' => '220',
					'css'         => array(
						array(
							'property' => '--ect-bricks-featured-image-min-height',
							'selector' => '&',
						),
					),
				)
			);

			$element->controls['ect_bricks_featured_image_height'] = array_merge(
				$shared_number,
				array(
					'label'       => esc_html__( 'Height', 'template-events-calendar' ),
					'placeholder' => '178',
					'css'         => array(
						array(
							'property' => '--ect-bricks-featured-image-height',
							'selector' => '&',
						),
					),
				)
			);
		}

		private static function ect_bricks_register_vignette_controls( $element, array $image_required ) {
			$element->controls['ect_bricks_sep_featured_image_vignette'] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => esc_html__( 'Overlay', 'template-events-calendar' ),
				'type'     => 'separator',
				'required' => $image_required,
			);

			$element->controls['ect_bricks_featured_image_vignette'] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => esc_html__( 'Pattern', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => array(
					'none'          => esc_html__( 'None', 'template-events-calendar' ),
					'radial-center' => esc_html__( 'Radial vignette', 'template-events-calendar' ),
					'bottom-fade'   => esc_html__( 'Bottom fade', 'template-events-calendar' ),
					'top-fade'      => esc_html__( 'Top fade', 'template-events-calendar' ),
					'left-fade'     => esc_html__( 'Left fade', 'template-events-calendar' ),
					'right-fade'    => esc_html__( 'Right fade', 'template-events-calendar' ),
					'tint'          => esc_html__( 'Color tint', 'template-events-calendar' ),
				),
				'default'  => 'none',
				'rerender' => true,
				'required' => $image_required,
			);

			$element->controls['ect_bricks_featured_image_vignette_color'] = array(
				'tab'         => 'style',
				'group'       => 'featured_image',
				'label'       => esc_html__( 'Overlay color', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => '#0f172a',
				'responsive'  => true,
				'required'    => ECT_Bricks_Hover_Controls::ect_bricks_req_featured_image_vignette(),
				'css'         => array(
					array(
						'property' => '--ect-bricks-vignette-color',
						'selector' => '&',
					),
				),
			);

			$element->controls['ect_bricks_featured_image_vignette_opacity'] = array(
				'tab'         => 'style',
				'group'       => 'featured_image',
				'label'       => esc_html__( 'Overlay strength', 'template-events-calendar' ),
				'type'        => 'number',
				'min'         => 0,
				'max'         => 100,
				'step'        => 1,
				'placeholder' => '45',
				'default'     => 45,
				'responsive'  => true,
				'required'    => ECT_Bricks_Hover_Controls::ect_bricks_req_featured_image_vignette(),
				'css'         => array(
					array(
						'property' => '--ect-bricks-vignette-opacity',
						'selector' => '&',
					),
				),
			);
		}

		private static function ect_bricks_register_featured_image_style_controls( $element ) {
			$image_required = ECT_Bricks_Hover_Controls::ect_bricks_req_featured_image_style();
			self::ect_bricks_register_image_dimension_controls( $element, $image_required );
			self::ect_bricks_register_image_hover_controls( $element, $image_required );
			self::ect_bricks_register_vignette_controls( $element, $image_required );

			self::ect_bricks_register_category_badge_style_controls( $element );

			self::ect_bricks_register_shell_badge_style_controls(
				$element,
				array(
					'prefix'               => 'ect_bricks_shell_date_',
					'separator_key'        => 'ect_bricks_sep_style_date_badge',
					'separator_label'      => esc_html__( 'Image date', 'template-events-calendar' ),
					'required'             => ECT_Bricks_Hover_Controls::ect_bricks_req_shell_date_badge_style(),
					'target'               => '& .ect-card__date-badge',
					'var_prefix'           => '--ect-bricks-shell-date',
					'fields'               => array( 'background', 'typography', 'padding' ),
					'typography_selector'  => '& .ect-card__date-badge, & .ect-card__date-badge span, & .ect-card__date-badge strong',
					'typography_extra_css' => array(
						array(
							'property' => '--ect-bricks-shell-date-font-size',
							'selector' => '& .ect-card__date-badge',
						),
						array(
							'property' => '--ect-bricks-shell-date-line-height',
							'selector' => '& .ect-card__date-badge',
						),
						array(
							'property' => '--ect-bricks-shell-date-text-transform',
							'selector' => '& .ect-card__date-badge',
						),
					),
					'placeholders'         => array(
						'background' => '#2147c7',
					),
				)
			);
		}

		private static function ect_bricks_register_style1_date_style_controls( $element ) {
			$date_required  = ECT_Bricks_Hover_Controls::ect_bricks_req_list1_date_column_style();
			$date_typo_sel  = '& .ect-list__date-inner, & .ect-list__date .ect-list__day, & .ect-list__date .ect-list__month';
			$date_inner_sel = '& .ect-list__date-inner';

			$element->controls['ect_bricks_list1_content_background'] = array(
				'tab'         => 'style',
				'group'       => 'style1_date',
				'label'       => esc_html__( 'Inner background', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => 'transparent',
				'responsive'  => true,
				'required'    => $date_required,
				'css'         => array(
					array(
						'property' => '--ect-bricks-list1-date-bg',
						'selector' => '&',
					),
					array(
						'property' => 'background-color',
						'selector' => $date_inner_sel,
					),
				),
			);

			$element->controls['ect_bricks_list1_date_align'] = array(
				'tab'      => 'style',
				'group'    => 'style1_date',
				'label'    => esc_html__( 'Alignment', 'template-events-calendar' ),
				'type'     => 'select',
				'options'  => array(
					'top'    => esc_html__( 'Top', 'template-events-calendar' ),
					'center' => esc_html__( 'Middle', 'template-events-calendar' ),
					'bottom' => esc_html__( 'Bottom', 'template-events-calendar' ),
				),
				'default'  => 'top',
				'inline'   => true,
				'rerender' => true,
				'required' => $date_required,
			);

			$element->controls['ect_bricks_list1_date_typography'] = array(
				'tab'        => 'style',
				'group'      => 'style1_date',
				'label'      => esc_html__( 'Typography', 'template-events-calendar' ),
				'type'       => 'typography',
				'exclude'    => array( 'text-align' ),
				'responsive' => true,
				'required'   => $date_required,
				'css'        => array(
					array(
						'property' => 'typography',
						'selector' => $date_typo_sel,
					),
				),
			);

			$element->controls['ect_bricks_list1_date_inner_padding'] = array(
				'tab'        => 'style',
				'group'      => 'style1_date',
				'label'      => esc_html__( 'Inner padding', 'template-events-calendar' ),
				'type'       => 'spacing',
				'responsive' => true,
				'required'   => $date_required,
				'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'padding', $date_inner_sel ),
			);

			$element->controls['ect_bricks_list1_date_inner_margin'] = array(
				'tab'        => 'style',
				'group'      => 'style1_date',
				'label'      => esc_html__( 'Inner margin', 'template-events-calendar' ),
				'type'       => 'spacing',
				'responsive' => true,
				'required'   => $date_required,
				'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'margin', $date_inner_sel ),
			);

			$element->controls['ect_bricks_list1_date_inner_border'] = array(
				'tab'        => 'style',
				'group'      => 'style1_date',
				'label'      => esc_html__( 'Inner border', 'template-events-calendar' ),
				'type'       => 'border',
				'responsive' => true,
				'required'   => $date_required,
				'css'        => ECT_Bricks_Part_Fields::ect_bricks_field_css( 'border', $date_inner_sel ),
			);

			$element->controls['ect_bricks_list1_date_column_border'] = array(
				'tab'         => 'style',
				'group'       => 'style1_date',
				'label'       => esc_html__( 'Divider color', 'template-events-calendar' ),
				'type'        => 'color',
				'placeholder' => '#e5eaf2',
				'responsive'  => true,
				'required'    => $date_required,
				'css'         => array(
					array(
						'property' => 'border-color',
						'selector' => '& .ect-list__date',
					),
				),
			);
		}

		public static function ect_bricks_register_style_controls( $element ) {
			self::ect_bricks_register_events_card_style_controls( $element );
			self::ect_bricks_register_style1_date_style_controls( $element );
			self::ect_bricks_register_featured_image_style_controls( $element );
		}

		private static function ect_bricks_register_image_hover_controls( $element, array $image_required ) {
			$element->controls['ect_bricks_sep_featured_image_hover'] = array(
				'tab'      => 'style',
				'group'    => 'featured_image',
				'label'    => esc_html__( 'Image hover', 'template-events-calendar' ),
				'type'     => 'separator',
				'required' => $image_required,
			);

			$element->controls['ect_bricks_image_hover_animation'] = ECT_Bricks_Hover_Controls::ect_bricks_hover_animation_control( 'featured_image', $image_required );
		}
	}
}
