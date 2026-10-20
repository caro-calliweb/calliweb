<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function zoho_flow_load(){
	if(did_action('plugins_loaded') === 1){

		require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/includes/capabilities.php';
		require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/includes/utils.php';
		require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/integrations.php';
		if ( is_admin() ) {
			require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/admin/admin.php';
			require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/admin/system-info.php';
		}

		require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/includes/rest-api.php';
		require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/includes/zoho-flow-service.php';
		require_once WP_ZOHO_FLOW_PLUGIN_DIR . '/includes/zoho-flow-services.php';
		$zoho_flow_services = Zoho_Flow_Services::get_instance();
		if ( ! function_exists( 'get_plugins' ) ) {
		    require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ($zoho_flow_services_config as $service) {
			$zoho_flow_services->add_service($service);
		}
        do_action('zoho_flow_init_services');
	}
}
add_action( 'plugins_loaded', 'zoho_flow_load', 10, 0 );

function zoho_flow_activation(){

    if(!post_type_exists(WP_ZOHO_FLOW_WEBHOOK_POST_TYPE)){
        register_post_type( WP_ZOHO_FLOW_WEBHOOK_POST_TYPE, array(
            'labels' => array(
                'name' => __( 'Zoho Flow Webhooks', 'zoho-flow' ),
                'singular_name' => __( 'Zoho Flow Webhook', 'zoho-flow' ),
            ),
            'rewrite' => false,
            'query_var' => false,
            'public' => false,
            'capability_type' => 'webhook',
            'capabilities' => array(
                'edit_post' => 'zoho_flow_edit_webhook',
                'read_post' => 'zoho_flow_read_webhook',
                'delete_post' => 'zoho_flow_delete_webhook'
            ),
        ) );
    }

    if(!post_type_exists(WP_ZOHO_FLOW_API_KEY_POST_TYPE)){
        register_post_type( WP_ZOHO_FLOW_API_KEY_POST_TYPE, array(
            'labels' => array(
                'name' => __( 'Zoho Flow API Keys', 'zoho-flow' ),
                'singular_name' => __( 'Zoho Flow API Keys', 'zoho-flow' ),
            ),
            'rewrite' => false,
            'query_var' => false,
            'public' => false,
            'capability_type' => 'api_key',
            'capabilities' => array(
                'edit_post' => 'zoho_flow_edit_api_key',
                'read_post' => 'zoho_flow_read_api_key',
                'delete_post' => 'zoho_flow_delete_api_key'
            ),
        ) );
    }
}
register_activation_hook( __FILE__, 'zoho_flow_activation' );

function zoho_flow_uninstall(){
	if(post_type_exists(WP_ZOHO_FLOW_WEBHOOK_POST_TYPE)){
        $webhook_post_ids = get_posts( array(
            'post_type'      => WP_ZOHO_FLOW_WEBHOOK_POST_TYPE,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        foreach ( $webhook_post_ids as $post_id ) {
            wp_delete_post( $post_id, true );
        }
	    unregister_post_type(WP_ZOHO_FLOW_WEBHOOK_POST_TYPE);

	}
	if(post_type_exists(WP_ZOHO_FLOW_API_KEY_POST_TYPE)){
        $api_key_post_ids = get_posts( array(
            'post_type'      => WP_ZOHO_FLOW_API_KEY_POST_TYPE,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        foreach ( $api_key_post_ids as $post_id ) {
            wp_delete_post( $post_id, true );
        }
	    unregister_post_type( WP_ZOHO_FLOW_API_KEY_POST_TYPE );
	}

}
register_uninstall_hook(__FILE__, 'zoho_flow_uninstall');

add_action('admin_enqueue_scripts', 'zoho_flow_enqueue_thickbox');
function zoho_flow_enqueue_thickbox() {

    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');

	wp_enqueue_script(
        'zoho-flow-deactivation-js',
        plugins_url('assets/js/zoho-flow-deactivation.js', __FILE__),
        array('jquery', 'thickbox'), // Dependencies
        '1.0', // Version
        true // Load in footer
    );

	wp_localize_script('zoho-flow-deactivation-js', 'zohoFlow', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('zoho_flow_deactivate_nonce'),
    ));

	wp_enqueue_style(
        'zoho-flow-deactivation-css',
        plugins_url('assets/css/zoho-flow-deactivation.css', __FILE__),
        array('thickbox'),
        '1.0',
        'all'
    );
}

add_action('wp_ajax_zoho_flow_deactivate_plugin', 'zoho_flow_deactivate_plugin');
function zoho_flow_deactivate_plugin() {

    check_ajax_referer('zoho_flow_deactivate_nonce', 'zf_deactivate_nonce');

    if (!current_user_can('activate_plugins')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $plugin = WP_ZOHO_FLOW_PLUGIN_NAME.'/zoho-flow.php';
    deactivate_plugins($plugin);

    wp_send_json_success('Plugin deactivated');
}

add_filter('plugin_action_links_' . WP_ZOHO_FLOW_PLUGIN_NAME . '/zoho-flow.php', 'zoho_flow_deactivation_link');
function zoho_flow_deactivation_link($links) {

    $form_url = 'https://creatorapp.zohopublic.in/zohointranet/zoho-flow/form-embed/Wordpress_showcase_page_deactivation_form/vmkOy5mh2201nr0Odr4KxVsHZgr9M0NQveBR57Kuk2wTkJ1yOSpCpWuGZRxObxaT9SXZhu7bkbAYyZMdx1D25rOzP2qqx22Mgwk1';
    
    $links['deactivate'] = '<a href="' . esc_url($form_url) . '?zc_BdrClr=ffffff&zc_Header=false&TB_iframe=true&width=320&height=440" class="thickbox zf-deactivation-popup" title="Deactivate Zoho Flow?">Deactivate</a>';
    return $links;
}

