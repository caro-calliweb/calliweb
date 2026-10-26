<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * class used for APIs and Webhooks
 *
 * @since zohoflow  2.9.0
 * @since bookly    23.8
 */
class Zoho_Flow_Bookly extends Zoho_Flow_Service{
    
    /**
     * List services
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type int     $limit         Number of results. Default: 200.
     * @type string  $order_by      List order by the field. Default: id.
     * @type string  $order         List order Values: ASC|DESC. Default: DESC.
     *
     * @return WP_REST_Response    WP_REST_Response array with service details
     */
    public function list_services( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'title',
            'position'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ($request['order_by'] && (in_array($request['order_by'], $order_by_allowed, true))) ? $request['order_by'] : 'id';
        $order = ($request['order'] && (in_array($request['order'], $order_allowed, true))) ? $request['order'] : 'DESC';
        $limit = ($request['limit']) ? absint( $request['limit'] ) : 200;
        $order_by_sql = esc_sql( $order_by );
        $order_sql = esc_sql( $order );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}bookly_services ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
                $limit
            ), 'ARRAY_A'
            );
        return rest_ensure_response( $results );
    }
    
    /**
     * List customers
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $limit          Number of results. Default: 200.
     * @type    string  $order_by       List order by the field. Default: created_at.
     * @type    string  $order          List order Values: ASC|DESC. Default: DESC.
     * @type    string  $created_since  Customers created after the mentioned time will be returned.
     *
     * @return WP_REST_Response    WP_REST_Response array with customer details
     */
    public function list_customers( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'wp_user_id',
            'full_name',
            'email',
            'birthday',
            'created_at'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( isset( $request['order_by'] ) && ( in_array($request['order_by'], $order_by_allowed, true ) ) ) ? $request['order_by'] : 'created_at';
        $order = ( isset( $request['order'] ) && ( in_array( $request['order'], $order_allowed, true ) ) ) ? $request['order'] : 'DESC';
        $limit = isset( $request['limit'] ) ? absint( $request['limit'] ) : 200;
        $order_by_sql = esc_sql( $order_by );
        $order_sql = esc_sql( $order );
        $created_since = $request['created_since'];
        if (!empty($created_since)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
            $results = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bookly_customers WHERE created_at > %s ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
                    $created_since,
                    $limit
                ),
                'ARRAY_A'
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
            $results = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bookly_customers ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
                    $limit
                ),
                'ARRAY_A'
            );
        }
        foreach ($results as $index => $row ){
            unset( $results[$index]['token'], $results[$index]['collaborative_token'], $results[$index]['compound_token'] );
            $results[$index]['full_address'] = maybe_unserialize( $results[$index]['full_address'] );
            $results[$index]['info_fields'] = maybe_unserialize( $results[$index]['info_fields'] );
            $results[$index]['additional_address'] = maybe_unserialize( $results[$index]['additional_address'] );
            $results[$index]['tags'] = maybe_unserialize( $results[$index]['tags'] );
        }
        return rest_ensure_response( $results );
    }
    
    /**
     * List appointments
     *
     * @param WP_REST_Request $request WP_REST_Request object.
     *
     * Request path param  Mandatory.
     * @type    int     $limit          Number of results. Default: 200.
     * @type    string  $order_by       List order by the field. Default: created_at.
     * @type    string  $order          List order Values: ASC|DESC. Default: DESC.
     * @type    string  $created_since  Customers created after the mentioned time will be returned.
     * @type    string  $updated_since  Customers updated after the mentioned time will be returned.
     *
     * @return WP_REST_Response    WP_REST_Response array with appointment details
     */
    public function list_appointments( $request ){
        global $wpdb;
        $order_by_allowed = array(
            'id',
            'start_date',
            'end_date',
            'created_at',
            'updated_at'
        );
        $order_allowed = array('ASC', 'DESC');
        $order_by = ( isset( $request['order_by'] ) && ( in_array($request['order_by'], $order_by_allowed, true ) ) ) ? $request['order_by'] : 'created_at';
        $order = ( isset( $request['order'] ) && ( in_array( $request['order'], $order_allowed, true ) ) ) ? $request['order'] : 'DESC';
        $limit = isset( $request['limit'] ) ? absint( $request['limit'] ) : 200;
        $order_by_sql = esc_sql( $order_by );
        $order_sql = esc_sql( $order );
        if (!empty( $request['created_since'] ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
            $results = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bookly_appointments WHERE created_at > %s ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
                    $request['created_since'],
                    $limit
                ),
                'ARRAY_A'
            );
        }
        elseif (!empty( $request['updated_since'] ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
            $results = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bookly_appointments WHERE updated_at > %s ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
                    $request['updated_since'],
                    $limit
                ),
                'ARRAY_A'
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
            $results = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allowlisted and escaped before concatenation.
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}bookly_appointments ORDER BY " . $order_by_sql . ' ' . $order_sql . ' LIMIT %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Dynamic identifiers are allowlisted.
                    $limit
                ),
                'ARRAY_A'
            );
        }
        foreach ($results as $index => $row ){
            $results[$index]['customers'] = $this->get_appointment_customers( $row['id'] );
            if( !empty( $row['staff_id'] ) ){
                $results[$index]['staff'] = $this->get_staff( $row['staff_id'] );
            }
            if( !empty( $row['service_id'] ) ){
                $results[$index]['service'] = $this->get_service( $row['service_id'] );
            }
        }
        return rest_ensure_response( $results );
    }
    
    /**
     * Get array customer details of given appointment ID
     * 
     * @param   int     $appointment_id  Appointment ID.
     * @return  array   Array of customers
     */
    private function get_appointment_customers( $appointment_id ){
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
        $results = $wpdb->get_results(
            $wpdb->prepare("
                    SELECT bca.*,
                           bc.*
                    FROM {$wpdb->prefix}bookly_customer_appointments bca
                    INNER JOIN {$wpdb->prefix}bookly_customers bc
                    ON bc.id = bca.customer_id
                    WHERE bca.appointment_id = %d
                ", $appointment_id), 'ARRAY_A'
            );
        foreach ($results as $index => $row ){
            unset( $results[$index]['token'], $results[$index]['collaborative_token'], $results[$index]['compound_token'] );
            $results[$index]['extras'] = maybe_unserialize( $results[$index]['extras'] );
            $results[$index]['custom_fields'] = maybe_unserialize( $results[$index]['custom_fields'] );
            $results[$index]['full_address'] = maybe_unserialize( $results[$index]['full_address'] );
            $results[$index]['info_fields'] = maybe_unserialize( $results[$index]['info_fields'] );
            $results[$index]['additional_address'] = maybe_unserialize( $results[$index]['additional_address'] );
            $results[$index]['tags'] = maybe_unserialize( $results[$index]['tags'] );
        }
        return $results;
    }
    
    /**
     * Get staff details by staff ID
     * 
     * @param int $staff_id Staff ID 
     * @return  array   Staff detail array
     */
    private function get_staff( $staff_id ){
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
        $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}bookly_staff WHERE id = %d LIMIT 1",
                        $staff_id
                    ), 'ARRAY_A'
                );
        unset( $results[0]['icalendar_token'],
            $results[0]['zoom_authentication'],
            $results[0]['zoom_oauth_token'],
            $results[0]['cloud_msc_token'],
            $results[0]['google_data'],
            $results[0]['outlook_data']
            );
        return $results[0];
    }
    
    /**
     * Get service details by staff ID
     *
     * @param int $service_id   Service ID
     * @return  array   Service detail array
     */
    private function get_service( $service_id ){
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table read is required and must return live data.
        $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}bookly_services WHERE id = %d LIMIT 1",
                        $service_id
                    ), 'ARRAY_A'
                );
        return $results[0];
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
        $plugin_dir = ABSPATH . 'wp-content/plugins/bookly-responsive-appointment-booking-tool/main.php';
        if(file_exists( $plugin_dir ) ){
            $plugin_data = get_plugin_data( $plugin_dir );
            $system_info['bookly'] = @$plugin_data['Version'];
        }
        return rest_ensure_response( $system_info );
    }
}