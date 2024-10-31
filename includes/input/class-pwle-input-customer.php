<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Input_Customer' ) ) :

class PWLE_Input_Customer extends PWLE_Input {

    public $title = 'Customer';
    public $title_plural = 'Customers';
    public $color = '#398240';

    function get_fields() {
        global $wpdb;

        if ( empty( $this->fields ) ) {

            $this->fields = $this->get_cached_fields();
            if ( !empty( $this->fields ) ) { return $this->fields; }

            $this->add_field( 'First name', 'get_first_name' );
            $this->add_field( 'Last name', 'get_last_name' );
            $this->add_field( 'Order count', 'get_order_count' );
            $this->add_field( 'Money spent', 'get_total_spent' );

            $meta_keys = array();
            $rows = $wpdb->get_results( "
                SELECT
                    DISTINCT m.meta_key
                FROM
                    {$wpdb->usermeta} AS m
                WHERE
                    m.meta_key NOT LIKE '_woocommerce_persistent_cart%'
                    AND m.meta_key NOT LIKE 'closedpostboxes_%'
                    AND m.meta_key NOT LIKE 'metaboxhidden_%'
                    AND m.meta_key NOT LIKE 'meta-box-%'
                    AND m.meta_key NOT LIKE 'manageedit%'
                    AND m.meta_key NOT LIKE 'managenav%'
                    AND m.meta_key NOT LIKE 'screen_layout_%'
                    AND m.meta_key NOT LIKE 'wp_%'
                    AND m.meta_key NOT IN ('_money_spent', '_order_count', 'first_name', 'last_name')
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
            $this->add_filter( 'Minimum Order Count', 'min_order_count', 'numeric' );
            $this->add_filter( 'Minimum Money Spent', 'min_money_spent', 'currency' );
        }

        return $this->filters;
    }

    function load_processing_table( $filters ) {
        global $wpdb;

        $filter_joins = '';
        $filter_where = '';

        if ( !empty( $filters['min_order_count'] ) ) {
            $filter_joins .= "JOIN {$wpdb->usermeta} AS order_count ON (order_count.user_id = m.user_id AND order_count.meta_key = '_order_count')";
            $filter_where .= $wpdb->prepare( " AND CAST(order_count.meta_value AS SIGNED) >= %d ", $filters['min_order_count'] );
        }

        if ( !empty( $filters['min_money_spent'] ) ) {
            $filter_joins .= "JOIN {$wpdb->usermeta} AS money_spent ON (money_spent.user_id = m.user_id AND money_spent.meta_key = '_money_spent')";
            $filter_where .= $wpdb->prepare( " AND CAST(money_spent.meta_value AS DECIMAL(14, 4)) >= %f ", $filters['min_money_spent'] );
        }

        $record_count = $wpdb->query(
            $wpdb->prepare( "
                    INSERT INTO {$wpdb->pw_lets_export}
                        (class_name, id)

                    SELECT
                        DISTINCT
                        %s,
                        m.user_id
                    FROM
                        {$wpdb->usermeta} AS m
                    JOIN
                        {$wpdb->users} AS u ON (u.ID = m.user_id)

                    $filter_joins

                    WHERE
                        m.meta_value LIKE '%%customer%%'
                        $filter_where
                ",
                get_class( $this )
        ) );

        return $record_count;
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

        $customer = new WC_Customer( $id );
        if ( !$customer ) { return; }

        $row = array();
        foreach ( $fields as $field_name => $field_value ) {
            $field = $all_fields[ $field_name ];

            if ( true === $field->is_meta ) {
                $value = get_user_meta( $id, $field->property, true );

            } else if ( method_exists( $customer, $field->property ) ) {
                $value = $customer->{$field->property}();

            } else if ( !$pw_lets_export->wc_min_version( '3.0' ) && 0 === strpos( $field->property, 'get_' ) ) {
                $value = get_user_meta( $id, substr( $field->property, 4), true );
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
$input_class = new PWLE_Input_Customer();
$input_class->pro_message = 'Including email, billing/shipping address information, and more!';
$pw_lets_export->input_classes[ get_class( $input_class ) ] = $input_class;

endif;

?>