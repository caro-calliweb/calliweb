<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$main_skin_color = isset($attributes['main_skin_color']) ? sanitize_hex_color($attributes['main_skin_color']) : "";

//dynamic title style
$event_title_color = isset($attributes['event_title_color']) ? sanitize_hex_color($attributes['event_title_color']) : "";
$event_title_font = isset($attributes['event_title_font']) ? sanitize_text_field($attributes['event_title_font']) : "";
$event_title_family = isset($attributes['event_title_family']) ? sanitize_text_field($attributes['event_title_family']) : "";
$event_title_weight = isset($attributes['event_title_weight']) ? sanitize_text_field($attributes['event_title_weight']) : "";
$event_title_transform = isset($attributes['event_title_transform']) ? sanitize_text_field($attributes['event_title_transform']) : "";
$event_title_style = isset($attributes['event_title_style']) ? sanitize_text_field($attributes['event_title_style']) : "";
$event_title_decoration = isset($attributes['event_title_decoration']) ? sanitize_text_field($attributes['event_title_decoration']) : "";
$event_title_line_height = isset($attributes['event_title_line_height']) ? sanitize_text_field($attributes['event_title_line_height']) : "";
$event_title_letter_spacing = isset($attributes['event_title_letter_spacing']) ? sanitize_text_field($attributes['event_title_letter_spacing']) : "";

//dynamic date style
$event_date_color = isset($attributes['event_date_color']) ? sanitize_hex_color($attributes['event_date_color']) : "";
$event_date_font = isset($attributes['event_date_font']) ? sanitize_text_field($attributes['event_date_font']) : "";
$event_date_family = isset($attributes['event_date_family']) ? sanitize_text_field($attributes['event_date_family']) : "";
$event_date_weight = isset($attributes['event_date_weight']) ? sanitize_text_field($attributes['event_date_weight']) : "";
$event_date_transform = isset($attributes['event_date_transform']) ? sanitize_text_field($attributes['event_date_transform']) : "";
$event_date_style = isset($attributes['event_date_style']) ? sanitize_text_field($attributes['event_date_style']) : "";
$event_date_decoration = isset($attributes['event_date_decoration']) ? sanitize_text_field($attributes['event_date_decoration']) : "";
$event_date_line_height = isset($attributes['event_date_line_height']) ? sanitize_text_field($attributes['event_date_line_height']) : "";
$event_date_letter_spacing = isset($attributes['event_date_letter_spacing']) ? sanitize_text_field($attributes['event_date_letter_spacing']) : "";

//dynamic venue style
$event_venue_color = isset($attributes['event_venue_color']) ? sanitize_hex_color($attributes['event_venue_color']) : "";
$event_venue_font = isset($attributes['event_venue_font']) ? sanitize_text_field($attributes['event_venue_font']) : "";
$event_venue_family = isset($attributes['event_venue_family']) ? sanitize_text_field($attributes['event_venue_family']) : "";
$event_venue_weight = isset($attributes['event_venue_weight']) ? sanitize_text_field($attributes['event_venue_weight']) : "";
$event_venue_transform = isset($attributes['event_venue_transform']) ? sanitize_text_field($attributes['event_venue_transform']) : "";
$event_venue_style = isset($attributes['event_venue_style']) ? sanitize_text_field($attributes['event_venue_style']) : "";
$event_venue_decoration = isset($attributes['event_venue_decoration']) ? sanitize_text_field($attributes['event_venue_decoration']) : "";
$event_venue_line_height = isset($attributes['event_venue_line_height']) ? sanitize_text_field($attributes['event_venue_line_height']) : "";
$event_venue_letter_spacing = isset($attributes['event_venue_letter_spacing']) ? sanitize_text_field($attributes['event_venue_letter_spacing']) : "";

//dynamic description style
$event_description_color = isset($attributes['event_description_color']) ? sanitize_hex_color($attributes['event_description_color']) : "";
$event_description_font = isset($attributes['event_description_font']) ? sanitize_text_field($attributes['event_description_font']) : "";
$event_description_family = isset($attributes['event_description_family']) ? sanitize_text_field($attributes['event_description_family']) : "";
$event_description_weight = isset($attributes['event_description_weight']) ? sanitize_text_field($attributes['event_description_weight']) : "";
$event_description_transform = isset($attributes['event_description_transform']) ? sanitize_text_field($attributes['event_description_transform']) : "";
$event_description_style = isset($attributes['event_description_style']) ? sanitize_text_field($attributes['event_description_style']) : "";
$event_description_decoration = isset($attributes['event_description_decoration']) ? sanitize_text_field($attributes['event_description_decoration']) : "";
$event_description_line_height = isset($attributes['event_description_line_height']) ? sanitize_text_field($attributes['event_description_line_height']) : "";
$event_description_letter_spacing = isset($attributes['event_description_letter_spacing']) ? sanitize_text_field($attributes['event_description_letter_spacing']) : "";

//dynamic link style
$event_link_color = isset($attributes['event_link_color']) ? sanitize_hex_color($attributes['event_link_color']) : "";
$event_link_font = isset($attributes['event_link_font']) ? sanitize_text_field($attributes['event_link_font']) : "";
$event_link_family = isset($attributes['event_link_family']) ? sanitize_text_field($attributes['event_link_family']) : "";
$event_link_weight = isset($attributes['event_link_weight']) ? sanitize_text_field($attributes['event_link_weight']) : "";
$event_link_transform = isset($attributes['event_link_transform']) ? sanitize_text_field($attributes['event_link_transform']) : "";
$event_link_style = isset($attributes['event_link_style']) ? sanitize_text_field($attributes['event_link_style']) : "";
$event_link_decoration = isset($attributes['event_link_decoration']) ? sanitize_text_field($attributes['event_link_decoration']) : "";
$event_link_line_height = isset($attributes['event_link_line_height']) ? sanitize_text_field($attributes['event_link_line_height']) : "";
$event_link_letter_spacing = isset($attributes['event_link_letter_spacing']) ? sanitize_text_field($attributes['event_link_letter_spacing']) : "";

// Simple Event Style
$event_simple_color = isset($attributes['event_simple_color']) ? sanitize_hex_color($attributes['event_simple_color']) : "";

// Featured Event Style
$event_featured_color = isset($attributes['event_featured_color']) ? sanitize_hex_color($attributes['event_featured_color']) : "";