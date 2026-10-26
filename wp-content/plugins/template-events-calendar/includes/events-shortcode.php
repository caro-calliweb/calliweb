<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound
class EventsShortcode {

	/**
	 * Register all hooks
	 */
	public static function registers() {
		$this_plugin = new self();
		/*** ECT main shortcode */
		add_shortcode( 'events-calendar-templates', array( $this_plugin, 'ect_shortcodes' ) );

		require_once ECT_PLUGIN_DIR . 'includes/ect-styles.php';
		EctStyles::registers();
	}

	/**
	 * ECT main shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string|void
	 */
	public function ect_shortcodes( $atts ) {
		if ( ! function_exists( 'tribe_get_events' ) ) {
			return;
		}

		global $wp_query, $post, $more;
		$more = false;

		$attribute = shortcode_atts(
			apply_filters(
				'ect_shortcode_atts',
				array(
					'template'    => 'default',
					'style'       => 'style-1',
					'category'    => 'all',
					'date_format' => 'default',
					'start_date'  => '',
					'end_date'    => '',
					'time'        => 'future',
					'order'       => 'ASC',
					'limit'       => '10',
					'hide-venue'  => 'no',
					'event_tax'   => '',
					'month'       => '',
					'tags'        => '',
					'icons'       => '',
					'layout'      => '',
					'title'       => '',
					'design'      => '',
					'socialshare' => '',
				),
				$atts
			),
			$atts
		);

		$attribute = self::events_attr_filter( $attribute );

		$template            = isset( $attribute['template'] ) ? sanitize_text_field( $attribute['template'] ) : 'default';
		$style               = isset( $attribute['style'] ) ? sanitize_text_field( $attribute['style'] ) : 'style-1';
		$enable_share_button = isset( $attribute['socialshare'] ) ? sanitize_text_field( $attribute['socialshare'] ) : 'no';
		$time                = isset( $attribute['time'] ) ? sanitize_text_field( $attribute['time'] ) : '';
		$tect_settings       = get_option( 'ects_options' );
		if ( ! is_array( $tect_settings ) ) {
			$tect_settings = array();
		}

		EctStyles::ect_load_requried_assets( $template, $style );

		$ect_args   = $this->build_query_args( $attribute );
		$all_events = tribe_get_events( $ect_args );
		$events_html = '';
		$no_events   = '';

		if ( $all_events ) {
			$events_more_info_btn  = ! empty( $tect_settings['events_more_info'] ) ? sanitize_text_field( $tect_settings['events_more_info'] ) : esc_html__( 'Find out more', 'template-events-calendar' );
			$events_more_info_text = sanitize_text_field( $events_more_info_btn );
			$prev_event_month      = '';
			$prev_event_year       = '';
			$i                     = 0;

			foreach ( $all_events as $post ) {
				setup_postdata( $post );
				$events_html .= $this->render_event_item(
					$post,
					$attribute,
					$template,
					$style,
					$time,
					$enable_share_button,
					$events_more_info_text,
					$prev_event_month,
					$prev_event_year,
					$i
				);
			}
			wp_reset_postdata();
		} else {
			$no_event_found_text = ! empty( $tect_settings['events_not_found'] ) ? sanitize_text_field( $tect_settings['events_not_found'] ) : '';
			if ( ! empty( $no_event_found_text ) ) {
				$not_found_msz = wp_kses_post( $no_event_found_text );
			} else {
				$not_found_msz = '<div class="ect-no-events"><p>' . esc_html__( 'There are no upcoming events at this time.', 'template-events-calendar' ) . '</p></div>';
			}
			$no_events = '<span class="ect-icon"><i class="ect-icon-bell"></i></span>' . wp_kses_post( $not_found_msz );
		}

		return $this->wrap_output( $template, $style, $attribute, $events_html, $no_events );
	}

	/**
	 * Build tribe_get_events query args from shortcode attributes.
	 *
	 * @param array $attribute Shortcode attributes (mutated for category/meta).
	 * @return array
	 */
	private function build_query_args( &$attribute ) {
		if ( 'all' !== $attribute['category'] && $attribute['category'] ) {
			if ( strpos( $attribute['category'], ',' ) !== false ) {
				$attribute['category'] = array_map( 'trim', explode( ',', $attribute['category'] ) );
			}
			$attribute['event_tax'] = array(
				'relation' => 'OR',
				array(
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'name',
					'terms'    => $attribute['category'],
				),
				array(
					'taxonomy' => 'tribe_events_cat',
					'field'    => 'slug',
					'terms'    => $attribute['category'],
				),
			);
		}

		$meta_date_compare = '>=';
		if ( 'past' === $attribute['time'] ) {
			$meta_date_compare = '<';
		} elseif ( 'all' === $attribute['time'] ) {
			$meta_date_compare = '';
		}

		$attribute['key']       = '_EventStartDate';
		$attribute['meta_date'] = '';
		$meta_date_date         = '';

		if ( '' !== $meta_date_compare ) {
			$meta_date_date         = current_time( 'Y-m-d H:i:s' );
			$attribute['meta_date'] = array(
				array(
					'key'     => '_EventEndDate',
					'value'   => $meta_date_date,
					'compare' => $meta_date_compare,
					'type'    => 'DATETIME',
				),
			);
		}

		$ect_args = apply_filters(
			'ect_args_filter',
			array(
				'post_status'    => 'publish',
				'hide_upcoming'  => true,
				'posts_per_page' => $attribute['limit'],
				'tax_query'      => $attribute['event_tax'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'meta_key'       => $attribute['key'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => 'event_date',
				'order'          => $attribute['order'],
				'meta_query'     => $attribute['meta_date'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			),
			$attribute,
			$meta_date_date,
			$meta_date_compare
		);

		if ( ! empty( $attribute['start_date'] ) ) {
			$ect_args['start_date'] = $attribute['start_date'];
		}
		if ( ! empty( $attribute['end_date'] ) ) {
			$ect_args['end_date'] = $attribute['end_date'];
		}

		return $ect_args;
	}

	/**
	 * Render a single event item HTML for the current template.
	 *
	 * @param WP_Post $post                 Event post.
	 * @param array   $attribute            Shortcode attributes.
	 * @param string  $template             Template slug.
	 * @param string  $style                Style slug.
	 * @param string  $time                 Time filter.
	 * @param string  $enable_share_button  Whether social share is enabled.
	 * @param string  $events_more_info_text Read more label.
	 * @param string  $prev_event_month     Previous event month (by ref).
	 * @param string  $prev_event_year      Previous event year (by ref).
	 * @param int     $i                    Loop index (by ref).
	 * @return string
	 */
	private function render_event_item( $post, $attribute, $template, $style, $time, $enable_share_button, $events_more_info_text, &$prev_event_month, &$prev_event_year, &$i ) {
		$events_html        = '';
		$event_cost         = '';
		$events_date_header = '';
		$event_type         = tribe( 'tec.featured_events' )->is_featured( $post->ID ) ? sanitize_text_field( 'ect-featured-event' ) : sanitize_text_field( 'ect-simple-event' );
		$event_id           = $post->ID;
		$share_buttons      = '';

		if ( 'yes' === $enable_share_button ) {
			wp_enqueue_script( 'ect-sharebutton', ECT_PLUGIN_URL . 'assets/js/ect-sharebutton.min.js', array( 'jquery' ), ECT_VERSION, true );
			wp_enqueue_style( 'ect-sharebutton-css', ECT_PLUGIN_URL . 'assets/css/ect-sharebutton.min.css', null, ECT_VERSION, 'all' );
			$share_buttons = ect_share_button( $event_id );
		}

		$show_headers = apply_filters( 'tribe_events_list_show_date_headers', true );
		if ( $show_headers ) {
			$event_year  = esc_html( tribe_get_start_date( $post, false, 'Y' ) );
			$event_month = esc_html( tribe_get_start_date( $post, false, 'm' ) );
			if ( $prev_event_month !== $event_month || ( $prev_event_month === $event_month && $prev_event_year !== $event_year ) ) {
				$prev_event_month    = $event_month;
				$prev_event_year     = $event_year;
				$date_header         = sprintf( "<span class='month-year-box'>%s</span>", esc_html( tribe_get_start_date( $post, false, 'M Y' ) ) );
				$events_date_header .= '<!-- Month / Year Headers -->';
				$events_date_header .= $date_header;
			}
		}

		$venue_details_html  = '';
		$venue_details_html1 = '';
		$venue_details       = tribe_get_venue_details();

		if ( 'yes' !== $attribute['hide-venue'] ) {
			if ( in_array( $template, array( 'classic-list', 'modern-list', 'default', 'minimal-list' ), true ) ) {
				$venue_details_html .= '<div class="ect-list-venue ' . esc_attr( $template ) . '-venue">';
			} else {
				$venue_details_html .= '<div class="' . esc_attr( $template ) . '-venue">';
			}

			if ( tribe_has_venue() ) {
				if ( 'minimal-list' === $template ) {
					$venue_details_html1 = '<div class="' . $template . '-venue">';
					if ( isset( $venue_details['linked_name'] ) ) {
						$venue_details_html1 .= '<span class="ect-icon"><i class="ect-icon-location" aria-hidden="true"></i></span>';
						$venue_details_html1 .= '<span class="ect-venue-name">' . wp_kses_post( $venue_details['linked_name'] ) . '</span>';
						if ( tribe_get_map_link() ) {
							$venue_details_html1 .= '<span class="ect-google">' . wp_kses_post( tribe_get_map_link_html() ) . '</span>';
						}
					}
					$venue_details_html1 .= '</div>';
				} else {
					if ( ! empty( $venue_details['address'] ) && isset( $venue_details['linked_name'] ) ) {
						$venue_details_html .= '<span class="ect-icon"><i class="ect-icon-location"></i></span>';
					}
					$venue_details_html .= '<!-- Event Venue Info --><span class="ect-venue-details ect-address"><div>';
					$safe_values         = array_map(
						'wp_kses_post',
						array_filter( $venue_details, 'is_string' )
					);
					$venue_details_html .= implode( ',', $safe_values );
					$venue_details_html .= '</div>';
					if ( tribe_get_map_link() ) {
						$venue_details_html .= '<span class="ect-google">' . wp_kses_post( tribe_get_map_link_html() ) . '</span>';
					}
					$venue_details_html .= '</span>';
				}
			}

			$venue_details_html .= '</div>';
		}

		if ( tribe_get_cost( $event_id ) ) {
			$event_cost = '<!-- Event Ticket Price Info -->
                 <div class="ect-rate-area">
                 <span class="ect-icon"><i class="ect-icon-ticket"></i></span>
                 <span class="ect-rate">' . esc_html( tribe_get_cost( $event_id, true ) ) . '</span>
                 </div>';
		}

		$event_day      = '<span class="event-day">' . esc_html( tribe_get_start_date( $event_id, true, 'l' ) ) . '</span>';
		$ev_time        = $this->ect_tribe_event_time( $event_id, false );
		$event_schedule = ect_custom_date_formats( $attribute['date_format'], $template, $event_id, $ev_time );
		$event_title    = '<a class="ect-event-url" href="' . esc_url( tribe_get_event_link( $event_id ) ) . '" rel="bookmark">' . wp_kses_post( get_the_title( $event_id ) ) . '</a>';
		$event_content  = '<!-- Event Content --><div class="ect-event-content">';
		$event_content .= tribe_events_get_the_excerpt( $event_id, wp_kses_allowed_html( 'post' ) );
		$event_content .= '</div>';

		if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ), true ) ) {
			include ECT_PLUGIN_DIR . '/templates/timeline/timeline.php';
		} elseif ( in_array( $template, array( 'default', 'classic-list', 'modern-list' ), true ) ) {
			include ECT_PLUGIN_DIR . '/templates/list/list.php';
		} elseif ( 'minimal-list' === $template ) {
			include ECT_PLUGIN_DIR . '/templates/minimal-list/minimal-list.php';
		} else {
			include ECT_PLUGIN_DIR . '/templates/list/list.php';
		}

		return $events_html;
	}

	/**
	 * Wrap rendered events HTML (or no-events message) in the template wrapper.
	 *
	 * @param string $template    Template slug.
	 * @param string $style       Style slug.
	 * @param array  $attribute   Shortcode attributes.
	 * @param string $events_html Events markup.
	 * @param string $no_events   No-events markup.
	 * @return string
	 */
	private function wrap_output( $template, $style, $attribute, $events_html, $no_events ) {
		$output  = '';
		$cat_cls = is_array( $attribute['category'] ) ? implode( ' ', $attribute['category'] ) : $attribute['category'];

		if ( $no_events ) {
			return '<div id="ect-no-events"><p>' . wp_kses_post( $no_events ) . '</p></div>';
		}

		if ( in_array( $template, array( 'timeline', 'classic-timeline', 'timeline-view' ), true ) ) {
			if ( 'timeline' === $template ) {
				$style = 'style-1';
			} elseif ( 'classic-timeline' === $template ) {
				$style = 'style-2';
			}

			$output .= '<!----- Events Timeline Template ' . ECT_VERSION . ' ----->';
			$output .= '<div id="event-timeline-wrapper" class="' . esc_attr( $cat_cls ) . ' ' . esc_attr( $style ) . '">';
			$output .= '<div class="cool-event-timeline">';
			$output .= $events_html;
			$output .= '</div></div>';
		} elseif ( 'minimal-list' === $template ) {
			$output .= '<!----- Events Static list Template ' . ECT_VERSION . ' ----->';
			$output .= '<div id="ect-events-minimal-list-content">';
			$output .= '<div id="ect-minimal-list-wrp" class="ect-minimal-list-wrapper ' . esc_attr( $cat_cls ) . '">';
			$output .= $events_html;
			$output .= '</div></div>';
		} else {
			$output .= '<!----- Events list Template ' . ECT_VERSION . ' ----->';
			$output .= '<div id="ect-events-list-content">';
			$output .= '<div id="list-wrp" class="ect-list-wrapper ' . esc_attr( $cat_cls ) . '">';
			$output .= $events_html;
			$output .= '</div></div>';
		}

		return $output;
	}

	/**
	 * Sanitize shortcode attributes.
	 *
	 * @param array $attr Raw attributes.
	 * @return array
	 */
	public static function events_attr_filter( $attr ) {
		$attributes = array();

		foreach ( (array) $attr as $key => $value ) {
			$key = sanitize_key( $key );

			switch ( $key ) {
				case 'category':
				case 'tags':
					$attributes[ $key ] = sanitize_text_field( wp_unslash( $value ) );
					break;

				case 'limit':
				case 'posts_per_page':
					$attributes[ $key ] = absint( $value );
					break;

				default:
					$attributes[ $key ] = sanitize_text_field( wp_unslash( $value ) );
					break;
			}
		}

		return $attributes;
	}

	/**
	 * Get event dates and time.
	 *
	 * @param int  $event_id Event ID.
	 * @param bool $display  Whether to echo instead of return.
	 * @return string|void
	 */
	public function ect_tribe_event_time( $event_id, $display = true ) {
		global $post;
		$event = $event_id;
		if ( tribe_event_is_multiday( $event ) ) {
			$start_date = tribe_get_start_date( $event, false );
			$end_date   = tribe_get_end_date( $event, false );
			if ( $display ) {
				printf( esc_html__( '%1$s - %2$s', 'template-events-calendar' ), esc_html( $start_date ), esc_html( $end_date ) );
			} else {
				return sprintf( esc_html__( '%1$s - %2$s', 'template-events-calendar' ), esc_html( $start_date ), esc_html( $end_date ) );
			}
		} elseif ( tribe_event_is_all_day( $event ) ) {
			if ( $display ) {
				printf( esc_html__( 'All day', 'template-events-calendar' ) );
			} else {
				return sprintf( esc_html__( 'All day', 'template-events-calendar' ) );
			}
		} else {
			$time_format = get_option( 'time_format' );
			$start_date  = tribe_get_start_date( $event, false, $time_format );
			$end_date    = tribe_get_end_date( $event, false, $time_format );
			if ( $start_date !== $end_date ) {
				if ( $display ) {
					printf( esc_html__( '%1$s - %2$s', 'template-events-calendar' ), esc_html( $start_date ), esc_html( $end_date ) );
				} else {
					return sprintf( esc_html__( '%1$s - %2$s', 'template-events-calendar' ), esc_html( $start_date ), esc_html( $end_date ) );
				}
			} elseif ( $display ) {
				printf( esc_html__( '%s', 'template-events-calendar' ), esc_html( $start_date ) ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
			} else {
				return sprintf( esc_html__( '%s', 'template-events-calendar' ), esc_html( $start_date ) ); // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
			}
		}
	}
}
