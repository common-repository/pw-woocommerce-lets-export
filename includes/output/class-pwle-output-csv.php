<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Output_CSV' ) ) :

class PWLE_Output_CSV extends PWLE_Output {

    public $output_filename;

    private $csv_file;

    function __construct() {
        $this->name = 'CSV';
        $this->title = 'Export to CSV';
        $this->icon = 'fa-file-text-o';
        $this->color = '#EA5C00';
        $this->extension = '.csv';
    }

    function add_row( $input_class, $row ) {
        if ( !isset( $this->csv_file ) ) {
            $this->csv_file = fopen( $this->output_filename, 'w' );
        }

        foreach( $row as &$value ) {
            $value = trim( preg_replace( '/\s+/', ' ', $value ) );
        }

        if ( PW_LETS_EXPORT_USE_FPUTCSV ) {
            fputcsv( $this->csv_file, $row );
        } else {
            fputs( $this->csv_file, implode( ',', array_map( array( $this, 'encode_for_csv' ), $row ) ) . "\n" );
        }
    }

    function finish() {
        if ( isset( $this->csv_file ) ) {
            fclose( $this->csv_file );
        }
    }

    function send_file() {
        if ( file_exists( $this->output_filename ) ) {

            header( 'Content-Type: application/octet-stream' );
            header( 'Content-Disposition: attachment; filename="' . $this->get_filename() . '"' );
            header( 'Content-Description: File Transfer' );
            header( 'Expires: 0' );
            header( 'Cache-Control: must-revalidate' );
            header( 'Pragma: public' );
            header( 'Content-Length: ' . filesize( $this->output_filename ) );
            readfile( $this->output_filename );
            unlink( $this->output_filename );
            exit;

        } else {
            wp_die( "Temporary file not found ({$this->output_filename})." );
        }
    }

    function encode_for_csv( $value ) {
        // remove any ESCAPED double quotes within string.
        $value = str_replace( '\\"', '"', $value );

        // then force escape these same double quotes And Any UNESCAPED Ones.
        $value = str_replace( '"', '\"', $value );

        // force wrap value in quotes and return
        return '"' . $value . '"';
    }
}

global $pw_lets_export;
$pw_lets_export->output_classes[] = new PWLE_Output_CSV();

endif;

?>