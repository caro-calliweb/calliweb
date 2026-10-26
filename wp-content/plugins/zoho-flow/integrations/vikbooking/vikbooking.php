<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow      2.9.0
 * @since vikbooking    1.6.9
 */
class Zoho_Flow_VikBooking extends Zoho_Flow_Service{
    
    /**
     * List custom fields
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int     $limit         Number of results. Default: 200.
     * @type string  $order_by      List order by the field. Default: ordering.
     * @type string  $order         List order Values: ASC|DESC. Default: ASC.
     *
     * @return WP_REST_Response    WP_REST_Response array with custom field details
     */
    public function list_custom_fields( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'name',
            'ordering'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( $request['order_by'] && ( in_array( $request['order_by'], $order_by_allowed, true ) ) ) ? $request['order_by'] : 'ordering';
        $order = ( $request['order'] && ( in_array( $request['order'], $order_allowed, true ) ) ) ? $request['order'] : 'ASC';
        $limit = isset( $request['limit'] ) ? absint( $request['limit'] ) : 200;
        $order_by_sql = esc_sql( $order_by );
        $order_sql = esc_sql( $order );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vikbooking_custfields ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
            $limit
        );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above; custom table read is required and must return live data.
        $results = $wpdb->get_results( $query, 'ARRAY_A' );
        return rest_ensure_response( $results );
    }
    
    /**
     * List customers
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $limit              Number of results. Default: 200.
     * @type    string  $order_by           List order by the field. Default: id.
     * @type    string  $order              List order Values: ASC|DESC. Default: DESC.
     * @type    string  $last_triggerd_id   Customers created after the mentioned id will be returned.
     *
     * @return WP_REST_Response    WP_REST_Response array with customer details
     */
    public function list_customers( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'first_name',
            'last_name',
            'email'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( isset( $request['order_by'] ) && ( in_array( $request['order_by'], $order_by_allowed, true ) ) ) ? $request['order_by'] : 'id';
        $order = ( isset( $request['order'] ) && ( in_array( $request['order'], $order_allowed, true ) ) ) ? $request['order'] : 'DESC';
        $limit = isset( $request['limit'] ) ? absint( $request['limit'] ) : 200;
        $last_triggerd_id = $request['last_triggerd_id'];
        $order_by_sql = esc_sql( $order_by );
        $order_sql = esc_sql( $order );
        $query = "SELECT * FROM {$wpdb->prefix}vikbooking_customers";
        if ( ! empty( $last_triggerd_id ) ) {
            $query .= ' WHERE id > %d';
            $query .= ' ORDER BY ' . $order_by_sql . ' ' . $order_sql . ' LIMIT %d';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL template is assembled from allowlisted/escaped fragments before prepare.
            $query = $wpdb->prepare( $query, absint( $last_triggerd_id ), $limit );
        } else {
            $query .= ' ORDER BY ' . $order_by_sql . ' ' . $order_sql . ' LIMIT %d';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL template is assembled from allowlisted/escaped fragments before prepare.
            $query = $wpdb->prepare( $query, $limit );
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above; custom table read is required and must return live data.
        $results = $wpdb->get_results( $query, 'ARRAY_A' );
        foreach ($results as $index => $row ){
            $results[$index]['cfields'] = json_decode( $results[$index]['cfields'],true );
        }
        return rest_ensure_response( $results );
    }
    
    /**
     * List orders
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $limit              Number of results. Default: 200.
     * @type    string  $order_by           List order by the field. Default: id.
     * @type    string  $order              List order Values: ASC|DESC. Default: DESC.
     * @type    string  $last_triggerd_id   Orders created after the mentioned id will be returned.
     *
     * @return WP_REST_Response    WP_REST_Response array with order details
     */
    public function list_orders( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'first_name',
            'last_name',
            'email'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( isset( $request['order_by'] ) && ( in_array( $request['order_by'], $order_by_allowed, true ) ) ) ? $request['order_by'] : 'id';
        $order = ( isset( $request['order'] ) && ( in_array( $request['order'], $order_allowed, true ) ) ) ? $request['order'] : 'DESC';
        $limit = isset( $request['limit'] ) ? absint( $request['limit'] ) : 200;
        $last_triggerd_id = $request['last_triggerd_id'];
        $order_by_sql = esc_sql( $order_by );
        $order_sql = esc_sql( $order );
        $query = "SELECT * FROM {$wpdb->prefix}vikbooking_orders";
        if ( ! empty( $last_triggerd_id ) ) {
            $query .= ' WHERE id > %d';
            $query .= ' ORDER BY ' . $order_by_sql . ' ' . $order_sql . ' LIMIT %d';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL template is assembled from allowlisted/escaped fragments before prepare.
            $query = $wpdb->prepare( $query, absint( $last_triggerd_id ), $limit );
        } else {
            $query .= ' ORDER BY ' . $order_by_sql . ' ' . $order_sql . ' LIMIT %d';
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL template is assembled from allowlisted/escaped fragments before prepare.
            $query = $wpdb->prepare( $query, $limit );
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above; custom table read is required and must return live data.
        $results = $wpdb->get_results( $query, 'ARRAY_A' );
        return rest_ensure_response( $results );
    }
    
    /**
     * Fetch customer
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type string $fetch_field   Field for fetch.
     * @type string $fetch_value   Field value for fetch.
     *
     * @return WP_REST_Response    WP_REST_Response Array of customer details.
     */
    public function fetch_customer( $request ){
        $fetch_field = $request['fetch_field'];
        $fetch_value = $request['fetch_value'];
        $allowed_fetch_feilds = array(
            'id',
            'first_name',
            'last_name',
            'email',
            'phone',
            'company',
            'bdate',
            
        );
        if( isset( $fetch_field ) && isset( $fetch_value ) && in_array( $fetch_field, $allowed_fetch_feilds, true ) ){
            global $wpdb;
            $fetch_field_sql = esc_sql( $fetch_field );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Field identifier is allowlisted and escaped before concatenation.
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vikbooking_customers WHERE " . $fetch_field_sql . ' = %s ORDER BY id DESC LIMIT 200', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic field is allowlisted.
                $fetch_value
            );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Query is prepared above; custom table read is required and must return live data.
            $results = $wpdb->get_results( $query, 'ARRAY_A' );
            if( $results ){
                foreach ($results as $index => $row ){
                    $results[$index]['cfields'] = json_decode( $results[$index]['cfields'],true );
                }
                return rest_ensure_response( $results );
            }
        }
        return new WP_Error( 'rest_bad_request', 'Customer does not exist!', array( 'status' => 404 ) );
    }
    
    /*
     * Get user and system info.
     * Default API
     *
     * @return WP_REST_Response|WP_Error  WP_REST_Response system and logged in user details | WP_Error object with error details.
     */
    public function get_system_info(){
        $system_info = parent::get_system_info();
        if( ! function_exists( 'get_plugin_data' ) ){
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_dir = ABSPATH . 'wp-content/plugins/vikbooking/vikbooking.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['vikbooking'] = @$plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}