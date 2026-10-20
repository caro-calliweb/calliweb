<?php
if (!defined('ABSPATH')) {
    exit;
}

//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function ect_gutenberg_block_assets() {
	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_style(
		'ect-block-css',
		plugins_url( '/dist/block.css', __FILE__ ),
		array( 'wp-block-editor' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'dist/block.css' )
	);
}
add_action( 'enqueue_block_assets', 'ect_gutenberg_block_assets' );

//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function ect_gutenberg_scripts() {
	if ( ! is_admin() ) {
		return;
	}

	wp_enqueue_script(
		'ect-block-js',
		plugins_url( '/dist/block.js', __FILE__ ),
		array(
			'wp-i18n',
			'wp-blocks',
			'wp-element',
			'wp-components',
			'wp-block-editor',
			'wp-data',
			'wp-api',
		),
		filemtime( plugin_dir_path( __FILE__ ) . 'dist/block.js' ),
		true
	);
	wp_localize_script( 'ect-block-js', 'ectUrl', array(  ECT_PLUGIN_URL )  );
}
add_action( 'enqueue_block_editor_assets', 'ect_gutenberg_scripts' );

/**
 * Block Initializer
 * */
add_action( 'plugins_loaded', function () {
	if ( function_exists( 'register_block_type' ) ) {
		register_block_type(
			'ect/shortcode',
			array(
				'api_version'     => 3,
				'render_callback' => 'ect_block_callback',
				'attributes'      => array(
					'category' => array(
						'type'    => 'string',
						'default' => 'all',
					),
					'template' => array(
						'type'    => 'string',
						'default' => 'default',
					),
					'style' => array(
						'type'    => 'string',
						'default' => 'style-1',
					),
					'dateformat' => array(
						'type'    => 'string',
						'default' => 'default',
					),
					'limit' => array(
						'type'    => 'string',
						'default' => '10',
					),
					'order' => array(
						'type'    => 'string',
						'default' => 'ASC',
					),
					'hideVenue' => array(
						'type'    => 'string',
						'default' => 'no',
					),
					'time' => array(
						'type'    => 'string',
						'default' => 'future',
					),
					'startDate' => array(
						'type'    => 'string',
						'default' => '',
					),
					'endDate' => array(
						'type'    => 'string',
						'default' => '',
					),
					'socialshare' => array(
						'type'    => 'string',
						'default' => 'no',
					),
				),
			)
		);
	}
} );

/**
 * Block Output
 * */
//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function ect_block_callback( $attr ) {
    $category    = isset( $attr['category'] )    ? sanitize_text_field( $attr['category'] )    : 'all';
    $template    = isset( $attr['template'] )    ? sanitize_text_field( $attr['template'] )    : 'default';
    $style       = isset( $attr['style'] )       ? sanitize_text_field( $attr['style'] )       : 'style-1';
    $dateformat  = isset( $attr['dateformat'] )  ? sanitize_text_field( $attr['dateformat'] )  : 'default';
    $limit       = isset( $attr['limit'] )       ? intval( $attr['limit'] )                    : 10;
    $order       = isset( $attr['order'] )       ? sanitize_text_field( $attr['order'] )       : 'ASC';
    $hideVenue   = isset( $attr['hideVenue'] )   ? sanitize_text_field( $attr['hideVenue'] )   : 'no';
    $time        = isset( $attr['time'] )        ? sanitize_text_field( $attr['time'] )        : 'future';
    $startDate   = isset( $attr['startDate'] )   ? sanitize_text_field( $attr['startDate'] )   : '';
    $endDate     = isset( $attr['endDate'] )     ? sanitize_text_field( $attr['endDate'] )     : '';
    $socialshare = isset( $attr['socialshare'] ) ? sanitize_text_field( $attr['socialshare'] ) : 'no';

    if ( ! empty( $template ) ) {
        $shortcode_string = '[events-calendar-templates
            category="%s"
            template="%s"
            style="%s" 
            date_format="%s"
            limit="%s"
            order="%s"
            hide-venue="%s"
            time="%s"
            start_date="%s"
            end_date="%s"
            socialshare="%s"]';

        return sprintf(
            $shortcode_string,
            esc_attr( $category ),
            esc_attr( $template ),
            esc_attr( $style ),
            esc_attr( $dateformat ),
            esc_attr( $limit ),
            esc_attr( $order ),
            esc_attr( $hideVenue ),
            esc_attr( $time ),
            esc_attr( $startDate ),
            esc_attr( $endDate ),
            esc_attr( $socialshare )
        );
    }
}
