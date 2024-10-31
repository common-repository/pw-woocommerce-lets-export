<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Input_Product' ) ) :

class PWLE_Input_Product extends PWLE_Input {

    public $title = 'Product';
    public $title_plural = 'Products';
    public $color = '#345995';

    function get_fields() {
        global $pw_lets_export;
        global $wpdb;

        if ( empty( $this->fields ) ) {

            $this->fields = $this->get_cached_fields();
            if ( !empty( $this->fields ) ) { return $this->fields; }

            $this->add_field( 'Product ID', 'get_id' );
            $this->add_field( 'Type', 'get_type' );
            $this->add_field( 'Product name', 'get_name' );
            $this->add_field( 'Description', 'get_description' );
            $this->add_field( 'SKU', 'get_sku' );
            $this->add_field( 'Current price', 'get_price' );

            $meta_keys = array();
            $rows = $wpdb->get_results( "
                SELECT
                    DISTINCT m.meta_key
                FROM
                    {$wpdb->posts} AS products
                JOIN
                    {$wpdb->postmeta} AS m ON (m.post_id = products.ID)
                WHERE
                    products.post_type IN ( 'product', 'product_variation' )
                    AND m.meta_key NOT LIKE 'wp_%'
                    AND m.meta_key NOT LIKE 'attribute_%'
                    AND m.meta_key NOT IN ('_sku', '_price')
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
        global $pw_lets_export;

        if ( empty( $this->filters ) ) {
            $this->add_filter( 'Status', 'status', 'checkbox', get_post_statuses(), array( 'publish' ) );

            $product_types = array();
            foreach ( wc_get_product_types() as $key => $product_type ) {
                $product_types[ $key ] = str_replace( ' product', '', $product_type );
            }
            $this->add_filter( 'Types', 'type', 'checkbox', $product_types, array_merge( array_keys( $product_types ) ) );
            $this->add_filter( 'Variations', 'include_variations', 'checkbox', array( 'yes' => 'Include all variations' ), array() );

            $categories = get_terms( 'product_cat', 'orderby=name&hide_empty=0' );
            $selected_categories = array();
            foreach ( $categories as $term ) {
                $selected_categories[] = $term->slug;
            }
            $this->add_filter( 'Categories', 'category', 'categories', $categories, $selected_categories );
        }

        return $this->filters;
    }

    function load_processing_table( $filters ) {
        global $pw_lets_export;
        global $wpdb;

        $filters['limit'] = '-1';
        $filters['return'] = 'ids';

        $product_ids = wc_get_products( $filters );

        $pw_lets_export->insert_processing_records( get_class( $this ), $product_ids );

        $variation_count = 0;
        if ( isset( $filters['include_variations'] ) && $filters['include_variations'] == 'yes' ) {
            $variation_count = $wpdb->query(
                $wpdb->prepare( "
                        INSERT INTO {$wpdb->pw_lets_export}
                            (class_name, id, sort_order)

                        SELECT
                            e.class_name,
                            variation.ID,
                            CONCAT(e.sort_order, '.', variation.menu_order)
                        FROM
                            {$wpdb->pw_lets_export} AS e
                        JOIN
                            {$wpdb->posts} AS variation ON (variation.post_parent = e.ID and variation.post_type = 'product_variation')
                        WHERE
                            e.class_name = %s
                    ",
                    get_class( $this )
            ) );
        }

        return count( $product_ids ) + $variation_count;
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

        $product = wc_get_product( $id );
        if ( !$product ) { return; }

        $row = array();
        foreach ( $fields as $field_name => $field_value ) {
            $field = $all_fields[ $field_name ];

            if ( $field->property == 'get_type' ) {
                $value = ucwords( $product->get_type() );

            } else if ( 0 === strpos( $field->property, 'attribute_pa_' ) ) {
                $value = $product->get_attribute( str_replace( 'attribute_pa_', 'pa_', $field->property ) );

            } else if ( true === $field->is_meta ) {
                if ( $pw_lets_export->wc_min_version( '3.0' ) ) {
                    $value = $product->get_meta( $field->property );
                } else {
                    $value = get_post_meta( $id, $field->property, true );
                }

            } else if ( method_exists( $product, $field->property ) ) {
                $value = $product->{$field->property}();

            } else if ( !$pw_lets_export->wc_min_version( '3.0' ) ) {

                switch ( $field->property ) {
                    case 'get_name':
                        $value = $product->post->post_title;
                        break;

                    case 'get_description':
                        $value = $product->post->post_content;
                        break;

                    default:
                        if ( 0 === strpos( $field->property, 'get_' ) ) {
                            $value = get_post_meta( $id, substr( $field->property, 3), true );
                        } else {
                            $value = '## FIELD ERROR ' . $field->property . ' ##';
                        }
                    break;
                }

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
$input_class = new PWLE_Input_Product();
$input_class->pro_message = 'Including sale prices, attributes, variations and more!';
$pw_lets_export->input_classes[ get_class( $input_class ) ] = $input_class;

endif;

?>