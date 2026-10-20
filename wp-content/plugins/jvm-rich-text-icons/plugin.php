<?php
/**
 * Plugin Name: JVM Rich Text Icons
 * Description: Insert icons anywhere in your content. Inline in text, as a block, or in ACF fields. Includes Font Awesome and supports custom SVG icons.
 * Version: 1.8.2
 * Author: Joris van Montfort
 * Author URI: https://jorisvm.nl
 * Text Domain: jvm-rich-text-icons
 * Domain Path: languages
 *
 * @category Gutenberg
 * @author Joris van Montfort
 * @version 1.8.2
 * @package JVM Rich Text Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Initializer.
 */
load_plugin_textdomain( 'jvm-rich-text-icons', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

require_once plugin_dir_path( __FILE__ ) . 'src/svg-sanitizer.php';
require_once plugin_dir_path( __FILE__ ) . 'src/renderer.php';
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';

if (is_admin()) {
   require_once plugin_dir_path( __FILE__ ) . 'src/settings.php';
}

// Track activation date for review notice timing.
register_activation_hook(__FILE__, function() {
    if (!get_option('jvm_richtext_icons_activated')) {
        update_option('jvm_richtext_icons_activated', time());
    }
});

// Load the ACF field only for sites using the ACF plugin.
if (function_exists('the_field')) {
    require_once plugin_dir_path( __FILE__ ) . 'src/acf_plugin_jvm_rich_text_icons.php';
}
