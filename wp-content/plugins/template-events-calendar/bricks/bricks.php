<?php
/**
 * Bricks Builder integration bootstrap.
 *
 * Loaded only when the Bricks theme is active.
 *
 * @package template-events-calendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'ECT_BRICKS_LOADED' ) ) {
	return;
}

define( 'ECT_BRICKS_LOADED', true );

if ( ! defined( 'ECT_BRICKS_DIR' ) ) {
	define( 'ECT_BRICKS_DIR', ECT_PLUGIN_DIR . 'bricks/' );
}

if ( ! defined( 'ECT_BRICKS_URL' ) ) {
	define( 'ECT_BRICKS_URL', ECT_PLUGIN_URL . 'bricks/' );
}

if ( ! class_exists( 'ECT_Bricks_Service_Facade', false ) ) {
	require_once ECT_BRICKS_DIR . 'includes/class-ect-bricks-service-facade.php';
}

if ( ! class_exists( 'ECT_Bricks_Plugin', false ) ) {
	require_once ECT_BRICKS_DIR . 'includes/class-ect-bricks-plugin.php';
}

new ECT_Bricks_Plugin();
