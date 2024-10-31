<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Input' ) ) :

abstract class PWLE_Input {

    public $title = '';
    public $title_plural = '';
    public $color = '#06A3C9';
    public $is_default = false;

    protected $fields = array();
    protected $filters = array();

    abstract function get_fields();
    abstract function get_filters();
    abstract function load_processing_table( $filters );
    abstract function get_row( $id, $fields );

    protected function add_field( $name, $property, $is_meta = false, $is_default = true ) {

        $field = new PWLE_Field();
        $field->name = __( $name, 'woocommerce' );
        $field->property = $property;
        $field->is_meta = $is_meta;
        $field->is_default = $is_default;

        // Prevent adding duplicate slugs (shouldn't happen, but just in case).
        $index = 0;
        $clean_name = sanitize_title( $field->name );
        $slug = $clean_name;
        while ( isset( $fields[ $slug ] ) ) {
            $index++;
            $slug = $clean_name . '_' . $index;
        }

        $this->fields[ $slug ] = $field;
    }

    function clear_transients() {
        set_transient( $this->get_transient_name(), $this->fields, WEEK_IN_SECONDS );
    }

    protected function get_transient_name() {
        return 'pw_lets_export ' . get_class( $this ) . ' fields';
    }

    protected function get_cached_fields() {
        if ( true === PW_LETS_EXPORT_USE_TRANSIENTS ) {
            return get_transient( $this->get_transient_name() );
        } else {
            return array();
        }
    }

    protected function cache_fields() {
        if ( true === PW_LETS_EXPORT_USE_TRANSIENTS ) {
            set_transient( $this->get_transient_name(), $this->fields, WEEK_IN_SECONDS );
        }
    }

    protected function add_filter( $title, $name, $type, $options = array(), $default_data = array() ) {
        $filter = new PWLE_Filter( get_class( $this ) );
        $filter->title = __( $title, 'woocommerce' );
        $filter->name = $name;
        $filter->type = $type;
        $filter->options = $options;
        $filter->default_data = $default_data;
        $this->filters[] = $filter;
    }

    function add_additional_meta_fields( $meta_keys ) {
        // Ignore values we already pulled.
        $ignore_meta_fields = array();
        foreach ( $this->fields as $field ) {
            if ( true === $field->is_meta ) {
                $ignore_meta_fields[] = $field->property;
            }
        }

        // Add the fields to the export.
        foreach ( $meta_keys as $meta_key ) {
            if ( !in_array( $meta_key, $ignore_meta_fields ) ) {
                $title = apply_filters( 'woocommerce_attribute_label', $meta_key, $meta_key, false );
                $this->add_field( $title, $meta_key, true, false, false );
            }
        }
    }

    function get_header_row( $fields ) {
        if ( empty( $fields ) ) {
            return array();
        }

        $all_fields = $this->get_fields();

        $columns = array();
        foreach ( $fields as $field_name => $field_value ) {
            $field = $all_fields[ $field_name ];

            $columns[] = $field->name;
        }

        return $columns;
    }
}

endif;

?>