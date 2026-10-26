<?php
/**
 * ECT_Bricks_Query_Controls service.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
if ( ! class_exists( 'ECT_Bricks_Query_Controls', false ) ) {

	final class ECT_Bricks_Query_Controls {

		public static function ect_bricks_register_query_controls( $element, array $event_category_options ) {
			// -- Events Query --
			$element->controls['event_type'] = array(
				'tab'     => 'content',
				'group'   => 'event_query',
				'label'   => esc_html__( 'Types of events', 'template-events-calendar' ),
				'type'    => 'select',
				'options' => array(
					'past'   => esc_html__( 'Past', 'template-events-calendar' ),
					'future' => esc_html__( 'Future', 'template-events-calendar' ),
					'all'    => esc_html__( 'All', 'template-events-calendar' ),
				),
				'inline'  => true,
				'default' => 'all',
			);

			$element->controls['event_categories'] = array(
				'tab'         => 'content',
				'group'       => 'event_query',
				'label'       => esc_html__( 'Event categories', 'template-events-calendar' ),
				'type'        => 'select',
				'options'     => $event_category_options,
				'multiple'    => true,
				'placeholder' => esc_html__( 'All categories', 'template-events-calendar' ),
			);

			ECT_Bricks_Layout_Controls::ect_bricks_register_pro_lock_control(
				$element,
				'event_featured',
				array(
					'tab'       => 'content',
					'group'     => 'event_query',
					'label'     => esc_html__( 'Featured events', 'template-events-calendar' ),
					'type'      => 'select',
					'options'   => array(
						'all'          => esc_html__( 'All Events', 'template-events-calendar' ),
						'featured'     => esc_html__( 'Featured Events', 'template-events-calendar' ),
						'non_featured' => esc_html__( 'Non-featured Events', 'template-events-calendar' ),
					),
					'inline'    => true,
					'clearable' => false,
					'default'   => 'all',
				)
			);

			$element->controls['event_time_mode'] = array(
				'tab'     => 'content',
				'group'   => 'event_query',
				'label'   => esc_html__( 'Events time', 'template-events-calendar' ),
				'type'    => 'select',
				'options' => array(
					'all'     => esc_html__( 'All', 'template-events-calendar' ),
					'between' => esc_html__( 'Between date range', 'template-events-calendar' ),
				),
				'inline'  => true,
				'default' => 'all',
			);

			$element->controls['event_range_start'] = array(
				'tab'      => 'content',
				'group'    => 'event_query',
				'label'    => esc_html__( 'Range start', 'template-events-calendar' ),
				'type'     => 'datepicker',
				'required' => array( 'event_time_mode', '=', 'between' ),
			);

			$element->controls['event_range_end'] = array(
				'tab'      => 'content',
				'group'    => 'event_query',
				'label'    => esc_html__( 'Range end', 'template-events-calendar' ),
				'type'     => 'datepicker',
				'required' => array( 'event_time_mode', '=', 'between' ),
			);

			$element->controls['posts_per_page'] = array(
				'tab'         => 'content',
				'group'       => 'event_query',
				'label'       => esc_html__( 'Number of events', 'template-events-calendar' ),
				'type'        => 'number',
				'min'         => 1,
				'max'         => 100,
				'step'        => 1,
				'default'     => 10,
				'placeholder' => '10',
			);

			$element->controls['order'] = array(
				'tab'     => 'content',
				'group'   => 'event_query',
				'label'   => esc_html__( 'Events order', 'template-events-calendar' ),
				'type'    => 'select',
				'options' => array(
					'ASC'  => 'ASC',
					'DESC' => 'DESC',
				),
				'inline'  => true,
				'default' => 'ASC',
			);
		}

		public static function ect_bricks_register_messages_controls( $element ) {
			// -- Dynamic Messages (content) --
			$element->controls['no_events_text'] = array(
				'tab'         => 'content',
				'group'       => 'dynamic_messages',
				'label'       => esc_html__( 'No events found text', 'template-events-calendar' ),
				'type'        => 'text',
				'placeholder' => esc_html__( 'No events found', 'template-events-calendar' ),
				'default'     => __( 'No events found', 'template-events-calendar' ),
			);

			$element->controls['no_events_tag'] = array(
				'tab'     => 'content',
				'group'   => 'dynamic_messages',
				'label'   => esc_html__( 'HTML tag', 'template-events-calendar' ),
				'type'    => 'select',
				'options' => array(
					'h1'  => 'h1',
					'h2'  => 'h2',
					'h3'  => 'h3',
					'h4'  => 'h4',
					'h5'  => 'h5',
					'h6'  => 'h6',
					'p'   => 'p',
					'div' => 'div',
				),
				'default' => 'h2',
				'inline'  => true,
			);
		}

		public static function ect_bricks_get_event_category_options() {
			static $cached = null;
			if ( is_array( $cached ) ) {
				return $cached;
			}

			$event_category_options = array();
			if ( function_exists( 'taxonomy_exists' ) && taxonomy_exists( 'tribe_events_cat' ) ) {
				$max_terms   = (int) apply_filters( 'ect_bricks_event_category_options_limit', 500 );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$event_terms = get_terms(
					array(
						'taxonomy'   => 'tribe_events_cat',
						'hide_empty' => false,
						'number'     => max( 1, $max_terms ),
					)
				);
				if ( ! is_wp_error( $event_terms ) && is_array( $event_terms ) ) {
					foreach ( $event_terms as $event_term ) {
						if ( $event_term instanceof \WP_Term ) {
							$event_category_options[ $event_term->slug ] = esc_html( $event_term->name );
						}
					}
				}
			}

			$cached = $event_category_options;
			return $cached;
		}
	}
}
