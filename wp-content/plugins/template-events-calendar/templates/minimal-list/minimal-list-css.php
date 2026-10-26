<?php
if (!defined('ABSPATH')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}
//phpcs:disable	WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

/**
 * This file is used only for dynamic styles in minimal layouts.
 */

 switch ($style) {
    case "style-1":
        $ect_output_css .= '
        .ect-list-posts.style-1.ect-simple-event .ect-event-date-tag{
            color: ' . esc_attr($main_skin_color) . ';
        }
        #ect-minimal-list-wrp .ect-list-posts.style-1.ect-simple-event{
            border: 1px solid ' . esc_attr($main_skin_color) . ';
        }
        ';
        $ect_output_css .= '.ect-list-posts.style-1.ect-featured-event .ect-event-date-tag{
            color: ' . esc_attr($featured_event_skin_color) . ';
        }
        #ect-minimal-list-wrp .ect-list-posts.style-1.ect-featured-event{
            border: 1px solid ' . esc_attr($featured_event_skin_color) . ';
        }
       ';
        $ect_output_css .= '
        #ect-minimal-list-wrp .style-1 .ect-events-title a{
            ' . wp_strip_all_tags($title_styles) . '
        }';
        $ect_output_css .= ' #ect-minimal-list-wrp .ect-list-posts.style-1 .ect-event-date-tag .ect-event-datetimes span,
        #ect-minimal-list-wrp .style-1 span.ect-minimal-list-time{
            font-family: ' . esc_attr($ect_date_font_family) . ';
            font-style:' . esc_attr($ect_date_font_style) . ';
            line-height:' . esc_attr($ect_date_line_height) . ';
        }

        #ect-minimal-list-wrp .style-1 .ect-event-datetime{
            color: ' . esc_html(tinycolor($ect_title_color)->lighten(10)->toString()) . ';
        }
        ';
        break;

    case "style-2":
        $ect_output_css .= '
         .ect-list-posts.style-2.ect-simple-event .ect-event-date{
             color: ' . esc_attr($main_skin_color) . ';
         }
         ';
        $ect_output_css .= '.ect-list-posts.style-2.ect-featured-event .ect-event-date{
             color: ' . esc_attr($featured_event_skin_color) . ';
         }
        ';

        $ect_output_css .= '#ect-minimal-list-wrp .style-2 span.ect-event-title a{
            ' . wp_strip_all_tags($title_styles) . '
        }';
        $ect_output_css .= '#ect-minimal-list-wrp .style-2 .minimal-list-venue span,
        #ect-minimal-list-wrp .style-2 span.ect-google a {
            ' . wp_strip_all_tags($ect_venue_styles) . '
        }';
        break;

    case "style-3":
        $ect_output_css .= '#ect-minimal-list-wrp .ect-list-posts.style-3.ect-featured-event{
            border-left: 4px solid ' . esc_attr($featured_event_skin_color) . ';
        }';
        $ect_output_css .= '#ect-minimal-list-wrp .ect-list-posts.style-3.ect-simple-event{
            border-left: 4px solid ' . esc_attr($main_skin_color) . ';
        }';

        $ect_output_css .= '#ect-minimal-list-wrp .ect-list-posts.style-3.ect-featured-event .ect-left-wrapper{
            background: ' . esc_html(tinycolor($featured_event_skin_color)->lighten(20)->toString()) . ';
        }';

        $ect_output_css .= '#ect-minimal-list-wrp .ect-list-posts.style-3.ect-simple-event .ect-left-wrapper{
            background: ' . esc_html(tinycolor($main_skin_color)->lighten(17)->toString()) . ';
        }';

        $ect_output_css .= ' #ect-minimal-list-wrp .style-3 .ect-events-title a{
            ' . wp_strip_all_tags($title_styles) . '
        }';

        $ect_output_css .= '
        #ect-minimal-list-wrp .style-3 .ect-minimal-list-time{
            font-family: ' . esc_attr($ect_date_font_family) . ';
            color: ' . esc_html(tinycolor($ect_title_color)->lighten(10)->toString()) . ';
            font-style:' . esc_attr($ect_date_font_style) . ';
            line-height:' . esc_attr($ect_date_line_height) . ';
        }
        ';
        $ect_output_css .= '.ect-list-posts.style-3 .ect-event-dates{
            font-family: ' . esc_attr($ect_date_font_family) . ';
            font-style:' . esc_attr($ect_date_font_style) . ';
            line-height:' . esc_attr($ect_date_line_height) . ';
            color: ' . esc_attr($ect_date_color) . ';
        }
       ';
        break;
}
