<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PWLE_Export' ) ) :

class PWLE_Export extends WP_Async_Request {

	protected $action = 'pwle_export';

	protected function handle() {
        global $pw_lets_export;

        $output_classname = 'PWLE_Output_' . $_POST['export_type'];
        $output_class = new $output_classname();

        if ( !is_a( $output_class, 'PWLE_Output' ) ) {
            wp_die( 'Invalid export type.' );
        }

        $output_class->output_filename = $_POST['output_filename'];

        $input_class = null;

        foreach ( $_POST['classes'] as $class_name => $class_settings ) {
            if ( $pw_lets_export->export_is_canceled() ) { return; }

            $fields = isset( $class_settings['fields'] ) ? $class_settings['fields'] : array();
            $filters = isset( $class_settings['filters'] ) ? $class_settings['filters'] : array();

            $pw_lets_export->get_input_class( $class_name, $input_class );

            $header_row = $input_class->get_header_row( $fields );
            $output_class->add_row( $input_class, $header_row );

            $records = $pw_lets_export->get_records_to_process( $class_name );
            foreach ( $records as $record ) {
                if ( $pw_lets_export->export_is_canceled() ) { return; }

                $row = $input_class->get_row( $record->id, $fields );
                if ( $row ) {
                    $output_class->add_row( $input_class, $row );
                }

                $pw_lets_export->mark_record_as_processed( $class_name, $record->id );
            }
        }

        $output_class->finish();
	}
}

endif;

?>