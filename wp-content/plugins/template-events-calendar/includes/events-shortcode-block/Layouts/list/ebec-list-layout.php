<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralText
//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$monthNumber = $event_value['event_start_date_details_month'];

$formatterLong = new IntlDateFormatter(
    get_locale(), // Current WordPress locale
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE,
    null,
    null,
    'LLLL' // Full month name
);
$formatterShort = new IntlDateFormatter(
    get_locale(),
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE,
    null,
    null,
    'LLL' // Short month name
);

$dateObject = DateTime::createFromFormat('!m', $monthNumber);

$longMonthStart  = $formatterLong->format($dateObject);
$shortMonthStart = $formatterShort->format($dateObject);

$event_type      = tribe( 'tec.featured_events' )->is_featured( $event_id ) ? 'ebec-featured-event' : 'ebec-simple-event';
$description     = ! empty( $event_value['event_description'] ) ? $event_value['event_description'] : tribe_events_get_the_excerpt( $event_id );
if ( 'full' !== $desc_type ) {
	$description_excerpt = has_excerpt( $event_id ) ? get_the_excerpt( $event_id ) : $event_value['event_description'];
	$filter_desc         = wp_strip_all_tags( $description_excerpt );
	$excerpt             = wpautop(
		// wp_trim_words() gets the first X words from a text string.
		wp_trim_words(
			$filter_desc, // We'll use the post's content as our text string.
			55, // We want the first 55 words.
			'[...]' // This is what comes after the first 55 words.
		)
	);

	$description = $excerpt;
}

// Layout
if ( $display_header === true && $attributes['event_header_type'] === 'show_header' && 'minimal' !== $layout ) {
	$html .= '<div class="ebec-month-header ' . esc_attr( $event_type ) . '"><span class="ebec-header-year" >' . esc_html( $longMonthStart ) . ' ' . esc_html( $event_value['event_start_date_details_year'] ) . '</span><span class="ebec-header-line"></span></div>';
}

	$html .= '<div id="event-' . esc_attr( $event_id ) . '" class="ebec-list-posts style-1 ' . esc_attr( $event_type ) . '">';
	$html .= '<div class="ebec-event-date-tag"><div class="ebec-event-datetimes">
            <span class="ev-mo" >' . esc_html( $shortMonthStart ) . '</span>
            <span class="ebec-ev-day" >' . esc_html( $event_value['event_start_date_details_day'] ) . '</span>
            </div></div>';
	$html .= '<div class="ebec-event-details" >';
	$html .= '<div class="ebec-event-datetime">
             <span class="ebec-minimal-list-time">
             ' . ebec_date_style( $event, $attributes ) . '
             </span>
             </div>';
	$html .= '<a href="' . esc_url( $event_value['event_url'] ) . '" class="ebec-events-title" >' . wp_kses_post( $event_value['event_title'] ) . '</a>';
if ( $attributes['ebec_venue'] == 'no' && tribe_has_venue( $event_id ) && 'minimal' !== $layout ) {
	$html .= '<div class="ebec-list-venue" >';
	if ( $event_value['have_venue_address'] ) {
		$html .= '<span class="ebec-icon"><i class="ebec-icon-location" aria-hidden="true"></i></span>';
	}
	$venue_details = array_filter( (array) $event_value['venue_details'], 'is_string' );
	$html .= implode( ',', array_map( 'wp_kses_post', $venue_details ) );
	$html .= '</div>';
}


if ( $attributes['ebec_display_desc'] == 'yes' && ! empty( $description ) && 'minimal' !== $layout ) {
	$html .= '<div class="ebec-minimal-list-desc">
                <div class="ebec-event-content" itemprop="description" >
                <div>' . wp_kses_post( $description ) . '</div>
                </div>
              </div>';
}
if ( ! empty( $event_value['event_cost'] ) && 'minimal' !== $layout ) {
	$html .= '<div class="ebec-list-cost">' . esc_html( $event_value['event_cost'] ) . '</div>';
}
		$html .= '<div class="ebec-style-1-more" ><a href="' . esc_url( $event_value['event_url'] ) . '" class="ebec-events-read-more" rel="bookmark" >' . esc_html( $attributes['event_link_name']) . '</a></div>';
	$html     .= '</div>';
if ( 'minimal' !== $layout ) {
	$html .= '<div class="ebec-right-wrapper">';
	if ( $event_value['image'] != null ) {
		$html .= '<a class="ebec-static-small-list-ev-img" href="' . esc_url( $event_value['event_url'] ) . '">
				<img src="' . esc_url( $event_value['image'] ) . '"></img><span class="ebec-image-overlay ebec-overlay-type-extern"><span class="ebec-image-overlay-inside"></span></span>
				</a>';
	}
		$html .= '  </div>';
}
	$html .= '</div>';
