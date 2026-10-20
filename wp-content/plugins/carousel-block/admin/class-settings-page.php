<?php

namespace CarouselSliderBlock\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retired settings page entry point.
 *
 * Legacy setting values remain readable from the database through Settings_Utils,
 * and can be overridden with the cb_carousel_block_setting_* filters.
 */
class Settings_Page {
    /**
     * Keep the historical initializer callable without registering a wp-admin page.
     */
    public static function init() {
    }
}
