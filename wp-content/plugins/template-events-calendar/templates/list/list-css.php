<?php
if ( ! defined( 'ABSPATH' ) ) exit;
//phpcs:disable	WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
// Function to validate hex color

// Default List Main Skin Color
switch ($style) {
    case 'style-1':
        if ($event_desc_bg_color === '#ffffff') {
            $ect_output_css .= '#ect-events-list-content .ect-simple-event.style-1 .ect-list-post-right .ect-list-venue{
                background: ' . esc_html(tinycolor($main_skin_color)->lighten(40)->toString()) . ';
                border: 1px solid;
                border-color: ' . esc_html($main_skin_color) . ';
            }';
            $ect_output_css .= '#ect-events-list-content .ect-featured-event.style-1 .ect-list-post-right .ect-list-venue{
                background: ' . esc_html(tinycolor($featured_event_skin_color)->lighten(40)->toString()) . ';
                border: 1px solid;
                border-color: ' . esc_html($featured_event_skin_color) . ';
            }';
        }
        $ect_output_css .= '
        #ect-events-list-content .style-1.ect-simple-event .ect-list-post-left .ect-list-date {
            background: ' . esc_html(self::ect_hex2rgba($main_skin_color, .96)) . ';
        }';
        // Default List Featured event skin color
        $ect_output_css .= '
        #ect-events-list-content .style-1.ect-featured-event .ect-list-post-left .ect-list-date {
            background: ' . esc_html(self::ect_hex2rgba($featured_event_skin_color, .85)) . ';
        }';
        break;
    case 'style-2':
        $ect_output_css .= '#ect-events-list-content .style-2 .modern-list-right-side{
            background: ' . esc_html($main_skin_color) . ';
        }';
        // Default List Featured event skin color
        $ect_output_css .= '#ect-events-list-content .ect-featured-event.style-2 .modern-list-right-side{
            background: ' . esc_html($featured_event_skin_color) . ';
        }
        #ect-events-list-content .ect-featured-event.style-2 .ect-list-venue .ect-icon,
        #ect-events-list-content .ect-featured-event.style-2 .ect-list-venue .ect-venue-details,
        #ect-events-list-content .ect-featured-event.style-2 .ect-list-venue .ect-venue-details span,
        #ect-events-list-content .ect-featured-event.style-2 .ect-list-venue .ect-venue-details .ect-google a{
            color: ' . esc_html($featured_event_font_color) . ';
        }';
        $ect_output_css .= '#ect-events-list-content .ect-simple-event.style-2 .ect-list-venue .ect-icon,
        #ect-events-list-content .ect-simple-event.style-2 .ect-list-venue .ect-venue-details,
        #ect-events-list-content .ect-simple-event.style-2 .ect-venue-details span,
        #ect-events-list-content .ect-simple-event.style-2 .ect-venue-details .ect-google a{
            color: ' . esc_html($main_skin_alternate_color) . ';
        }';
        break;
    case 'style-3':
        $ect_output_css .= '#ect-events-list-content .style-3 .ect-list-date {
            background: ' . esc_html($main_skin_color) . ';
        }';
        // Default List Featured event skin color
        $ect_output_css .= '
        #ect-events-list-content .ect-featured-event.style-3 .ect-list-date {
            background: ' . esc_html($featured_event_skin_color) . ';
        }';
        $ect_output_css .= '#ect-events-list-content .style-3 .ev-smalltime{
            ' . wp_strip_all_tags($ect_venue_styles) . ';
        }';
        break;
}

$ect_output_css .= '
#ect-events-list-content .ect-list-img {
    background-color: ' . esc_html(tinycolor($main_skin_color)->lighten(3)->toString()) . ';
}

#ect-events-list-content .ect-featured-event .ect-list-img {
    background-color: ' . esc_html(tinycolor($featured_event_skin_color)->lighten(3)->toString()) . ';
}';

// Default List Title Style
$ect_output_css .= '#ect-events-list-content h2.ect-list-title,
#ect-events-list-content h2.ect-list-title a.ect-event-url,
.ect-clslist-event-info .ect-clslist-title a.ect-event-url,
#ect-no-events p{
    ' . wp_strip_all_tags($title_styles) . ';
}
#ect-events-list-content h2.ect-list-title a:hover {
    color: ' . esc_html(tinycolor($ect_title_color)->lighten(10)->toString()) . ';
}';
// Default List Description Style
$ect_output_css .= '#ect-events-list-content .ect-list-post-right .ect-list-description .ect-event-content,
#ect-events-list-content .ect-list-post-right .ect-list-description .ect-event-content p {
    ' . wp_strip_all_tags($ect_desc_styles) . ';
}';

// Default List venue Styles
$ect_output_css .= '
#ect-events-list-content .ect-list-venue .ect-icon,
#ect-events-list-content .ect-list-venue .ect-venue-details,
#ect-events-list-content .ect-list-venue .ect-venue-details a,
#ect-events-list-content .ect-list-venue .ect-venue-details span{
    ' . wp_strip_all_tags($ect_venue_styles) . ';
}
#ect-events-list-content .ect-list-venue .ect-venue-details .ect-google a {
    color: ' . esc_html(tinycolor($ect_venue_color)->darken(3)->toString()) . ';
}';

/*--- Default List Dates Styles - CSS ---*/
$ect_output_css .= '#ect-events-list-content .ect-list-date .ect-date-area {
    ' . wp_strip_all_tags($ect_date_style) . ';
}';
// feature and main skin font color to date
$ect_output_css .= '
#ect-events-list-content .ect-featured-event .ect-list-date .ect-date-area {
    color: ' . esc_html($featured_event_font_color) . ';
}';

$ect_output_css .= '
#ect-events-list-content .ect-simple-event .ect-list-date .ect-date-area {
    color: ' . esc_html($main_skin_alternate_color) . ';
}';
// title color to read more button
$ect_output_css .= '
#ect-events-list-content .ect-events-read-more {
    color: ' . esc_html($ect_title_color) . ';
}';

// Cost color to title color and styles of desc
$ect_output_css .= '#ect-events-list-content .ect-rate-area {
    ' . wp_strip_all_tags($ect_desc_styles) . ';
}
#ect-events-list-content .ect-rate-area {
    color: ' . esc_html($ect_title_color) . ';
}';

$ect_output_css .= '#ect-events-list-content .ect-list-post {
    background-color: ' . esc_html($event_desc_bg_color) . ';
}';
$ect_output_css .= '
#ect-events-list-content .ect-list-post.ect-simple-event .ect-share-wrapper .ect-social-share-list a:hover {
    color: ' . esc_html($main_skin_color) . ';
}
#ect-events-list-content .ect-list-post.ect-featured-event .ect-share-wrapper .ect-social-share-list a:hover {
    color: ' . esc_html($featured_event_skin_color) . ';
}

#ect-events-list-content .ect-list-post:not(.style-2).ect-featured-event .ect-share-wrapper i.ect-icon-share:before {
    background: ' . esc_html($featured_event_font_color) . ';
    color: ' . esc_html($featured_event_skin_color) . ';
}';
if ($main_skin_alternate_color != '') {
    $ect_output_css .= '#ect-events-list-content .ect-list-post:not(.style-2).ect-simple-event .ect-share-wrapper i.ect-icon-share:before {
        background: ' . esc_html($main_skin_alternate_color) . ';
        color: ' . esc_html($main_skin_color) . ';
    }';
} else {
    $ect_output_css .= '#ect-events-list-content .ect-list-post:not(.style-2).ect-simple-event .ect-share-wrapper i.ect-icon-share:before {
        background: #ffffff;
        color: ' . esc_html($main_skin_color) . ';
    }';
}
