<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *  Filter Start Time And End Time
 */
//phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function ebec_fetch_start_end_time( $setting ) {
	try {
		$start_range_date = ! empty( $setting['ebec_date_range_start'] )
			? new DateTime( sanitize_text_field( $setting['ebec_date_range_start'] ) )
			: new DateTime( '0000-01-01 00:00:00' );
	} catch ( Exception $e ) {
		$start_range_date = new DateTime( '0000-01-01 00:00:00' );
	}

	try {
		$end_range_date = ! empty( $setting['ebec_date_range_end'] )
			? new DateTime( sanitize_text_field( $setting['ebec_date_range_end'] ) )
			: new DateTime( '9999-12-31 23:59:59' );
	} catch ( Exception $e ) {
		$end_range_date = new DateTime( '9999-12-31 23:59:59' );
	}
	if ( $setting['ebec_type'] == 'past' ) {
		if ( $setting['ebec_event_source'] == false ) {
			$end_range_date   = new DateTime();
			$start_range_date = new DateTime( '0000-01-01 00:00:00' );

		} else {
			if ( $end_range_date > new DateTime() ) {
				$end_range_date = new DateTime();
			}
		}
	} elseif ( $setting['ebec_type'] == 'future' ) {
		if ( $setting['ebec_event_source'] == false ) {
			$start_range_date = new DateTime( '0000-01-01 00:00:00' );
			$end_range_date   = new DateTime( '9999-12-31 23:59:59' );
		} else {
			if ( $start_range_date < new DateTime() ) {
				$start_range_date = new DateTime();
			}
		}
	} else {
		if ( $setting['ebec_event_source'] == false ) {
			$start_range_date = new DateTime( '0000-01-01 00:00:00' );
			$end_range_date   = new DateTime( '9999-12-31 23:59:59' );
		}
	}
	return array( $start_range_date, $end_range_date );
}
/**
 * Return Date Format Style
 */
function ebec_date_style( $event_id, $settings ) {
	$date_style   = '';
	$ev_time      = ebec_tribe_event_time( false, $event_id );
	$alldayevents = tribe_event_is_all_day( $event_id );
	/*Date Format START*/
	$ev_day        = tribe_get_start_date( $event_id, false, 'd' );
	$ev_month      = tribe_get_start_date( $event_id, false, 'M' );
	$ev_full_month = tribe_get_start_date( $event_id, false, 'F' );
	$ev_year       = tribe_get_start_date( $event_id, false, 'Y' );
	$ev_time       = ! $alldayevents ? $ev_time : 'All Day';

	$date_format = isset( $settings['ebec_date_formats'] ) ? $settings['ebec_date_formats'] : 'DM';

	if ( $date_format == 'DM' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        <span class="ebec-ev-month">' . esc_html( $ev_month ) . '</span>
        </div>';
	} elseif ( $date_format == 'MD' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-month">' . esc_html( $ev_month ) . ' </span>
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . '</span>
        </div>';
	} elseif ( $date_format == 'FD' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . '</span>
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        </div>';
	} elseif ( $date_format == 'DF' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . '</span>
        </div>';
	} elseif ( $date_format == 'FD,Y' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . '</span>
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ', </span>
        <span class="ebec-ev-yr">' . esc_html( $ev_year ) . '</span>
        </div>';
	} elseif ( $date_format == 'MD,Y' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate" >
        <span class="ebec-ev-month">' . esc_html( $ev_month ) . '</span>
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ', </span>
        <span class="ebec-ev-yr">' . esc_html( $ev_year ) . '</span>
        </div>';
	} elseif ( $date_format == 'MD,YT' ) {
		$date_style  = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
         <span class="ebec-ev-month">' . esc_html( $ev_month ) . '</span>
         <span class="ebec-ev-day">' . esc_html( $ev_day ) . ', </span>
         <span class="ebec-ev-yr">' . esc_html( $ev_year ) . '</span>';
		$date_style .= '<span class="ebec-ev-time"><span class="ebec-icon"><i class="ebec-icon-clock" aria-hidden="true"></i></span>' . esc_html( $ev_time ) . '</span>';
		$date_style .= '</div>';
	} elseif ( $date_format == 'jMl' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
            <span class="ebec-ev-day">' . esc_html( tribe_get_start_date( $event_id, false, 'j' ) ) . ' </span>
            <span class="ebec-ev-month">' . esc_html( $ev_month ) . ' </span>
            <span class="ebec-ev-weekday">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
            </div>';
	} elseif ( $date_format == 'full' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
            <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
            <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . ' </span>
            <span class="ebec-ev-yr">' . esc_html( $ev_year ) . '</span>
            <span class="ebec-ev-time"><span class="ebec-icon"><i class="ebec-icon-clock" aria-hidden="true"></i></span>' . esc_html( $ev_time ) . '</span>
            </div>';
	} elseif ( $date_format == 'd.FY' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
            <span class="ebec-ev-day">' . esc_html( $ev_day ) . '. </span>
            <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . ' </span>
            <span class="ebec-ev-yr">' . esc_html( $ev_year ) . '</span>
            </div>';
	} elseif ( $date_format == 'd.F' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate" >
            <span class="ebec-ev-day">' . esc_html( $ev_day ) . '. </span>
            <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . '</span>
            </div>';
	} elseif ( $date_format == 'd.Ml' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . '. </span>
        <span class="ebec-ev-month">' . esc_html( $ev_month ) . ' </span>
        <span class="ebec-ev-weekday">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
        </div>';
	} elseif ( $date_format == 'ldF' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-weekday">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . ' </span>
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . '</span>
        </div>';
	} elseif ( $date_format == 'Mdl' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-month">' . esc_html( $ev_month ) . ' </span>
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        <span class="ebec-ev-weekday">' . esc_html( tribe_get_start_date( $event_id, false, 'l' ) ) . '</span>
        </div>';
	} elseif ( $date_format == 'dFT' ) {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . ' </span>
        <span class="ebec-ev-time"><span class="ebec-icon"><i class="ebec-icon-clock" aria-hidden="true"></i></span>' . esc_html( $ev_time ) . '</span>     
        </div>';
	} else {
		$date_style = '<div class="ebec-date-area default-schedule" itemprop="startDate"  >
        <span class="ebec-ev-day">' . esc_html( $ev_day ) . ' </span>
        <span class="ebec-ev-month">' . esc_html( $ev_full_month ) . ' </span>
        <span class="ebec-ev-yr">' . esc_html( $ev_year ) . '</span>
        </div>';
	}
	 return $date_style;
}


			// get events dates and time
function ebec_tribe_event_time( $display, $event ) {
	if ( tribe_event_is_multiday( $event ) ) { // multi-date event
		$start_date = tribe_get_start_date($event, false, 'F j, Y');
		$end_date   = tribe_get_end_date($event, false, 'F j, Y');
		if ( $display ) {
			printf( esc_html__( '%1$s - %2$s', 'template-events-calendar' ), esc_html( $start_date ), esc_html( $end_date ) );
		} else {
			return sprintf( esc_html__( '%1$s - %2$s', 'template-events-calendar' ), esc_html( $start_date ), esc_html( $end_date ) );
		}
	} elseif ( tribe_event_is_all_day( $event ) ) { // all day event
		if ( $display ) {
			esc_html_e( 'All day', 'template-events-calendar' );
		} else {
			return esc_html__( 'All day', 'template-events-calendar' );
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
		} else {
			if ( $display ) {
				printf( esc_html__( '%s', 'template-events-calendar' ), esc_html( $start_date ) );//phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
			} else {
				return sprintf( esc_html__( '%s', 'template-events-calendar' ), esc_html( $start_date ) );//phpcs:ignore WordPress.WP.I18n.NoEmptyStrings
			}
		}
	}
}
