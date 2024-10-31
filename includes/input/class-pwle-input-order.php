<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Input_Order' ) ) :

class PWLE_Input_Order extends PWLE_Input {

    public $title = 'Order';
    public $title_plural = 'Orders';
    public $color = '#DC6E02';

    function get_fields() {
        global $wpdb;

        if ( empty( $this->fields ) ) {

            $this->fields = $this->get_cached_fields();
            if ( !empty( $this->fields ) ) { return $this->fields; }

            $this->add_field( 'Order #', 'get_order_number' );
            $this->add_field( 'Total', 'get_total' );
            $this->add_field( 'Date created', 'get_date_created' );

            $meta_keys = array();
            $rows = $wpdb->get_results( "
                SELECT
                    DISTINCT m.meta_key
                FROM
                    {$wpdb->posts} AS orders
                JOIN
                    {$wpdb->postmeta} AS m ON (m.post_id = orders.ID)
                WHERE
                    orders.post_type = 'shop_order'
                    AND m.meta_key NOT IN ('_order_total', '_order_number')
                ORDER BY
                    m.meta_key
            " );
            foreach ( $rows as $row ) {
                $meta_keys[] = $row->meta_key;
            }

            $this->add_additional_meta_fields( $meta_keys );

            $this->cache_fields();
        }

        return $this->fields;
    }

    function get_filters() {
        if ( empty( $this->filters ) ) {
            $this->add_filter( 'Dates', 'date', 'date_before_after' );
            $this->add_filter( 'Status', 'status', 'checkbox', wc_get_order_statuses(), array( 'wc-processing' ) );
        }

        return $this->filters;
    }

    function load_processing_table( $filters ) {
        global $pw_lets_export;

        $filters['limit'] = '-1';
        $filters['type'] = 'shop_order';
        $filters['return'] = 'ids';

        $order_ids = wc_get_orders( $filters );

        $pw_lets_export->insert_processing_records( get_class( $this ), $order_ids );

        return count( $order_ids );
    }

    function get_row( $id, $fields ) {
        global $pw_lets_export;

        if ( empty( $fields ) ) {
            return array();
        }

        // Reset the timeout and clear the cache before each
        // run to prevent memory errors and timeouts.
        set_time_limit( 30 );
        wp_cache_flush();

        $all_fields = $this->get_fields();

        $order = wc_get_order( $id );
        if ( !$order ) { return; }

        $row = array();
        foreach ( $fields as $field_name => $field_value ) {
            $field = $all_fields[ $field_name ];

            if ( true === $field->is_meta ) {
                if ( $pw_lets_export->wc_min_version( '3.0' ) ) {
                    $value = $order->get_meta( $field->property );
                } else {
                    $value = get_post_meta( $id, $field->property, true );
                }

            } else if ( method_exists( $order, $field->property ) ) {
                $value = $order->{$field->property}();

            } else if ( !$pw_lets_export->wc_min_version( '3.0' ) && 0 === strpos( $field->property, 'get_' ) ) {
                $value = get_post_meta( $id, substr( $field->property, 3), true );
            } else {
                $value = '## FIELD ERROR ' . $field->property . ' ##';
            }

            if ( is_a( $value, 'WC_DateTime' ) ) {
                $value = $value->date( wc_date_format() . ' ' . wc_time_format() );
            } else if ( is_array( $value ) || is_object( $value ) ) {
                $value = print_r( $value, true );
            }

            $row[] = $value;
        }

        return $row;
    }
}

global $pw_lets_export;
$input_class = new PWLE_Input_Order();
$input_class->pro_message = 'Including email, billing/shipping address information, and more!';
$pw_lets_export->input_classes[ get_class( $input_class ) ] = $input_class;

endif;

?>