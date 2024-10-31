<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Output_Screen' ) ) :

class PWLE_Output_Screen extends PWLE_Output {

    public $output_filename;

    private $html_file;
    private $current_class;

    function __construct() {
        $this->name = 'Screen';
        $this->title = 'Screen / Print';
        $this->icon = 'fa-laptop';
        $this->color = '#06A3C9';
        $this->extension = '.html';
    }

    function add_row( $input_class, $row ) {
        $class_name = get_class( $input_class );

        if ( $this->current_class != $class_name ) {
            $this->current_class = $class_name;

            if ( isset( $this->html_file ) ) {
                fwrite( $this->html_file, '</table>' );

            } else {
                $this->html_file = fopen( $this->output_filename, 'w' );
                fwrite( $this->html_file, '<p class="pwle-print-button"><a href="#" class="button" onClick="window.print(); return false;"><i class="fa fa-print" aria-hidden="true"></i> Print</a></p>' );
            }
            $this->write_table_header( $input_class, $row );

        } else {
            $this->write_table_row( $row );
        }
    }

    function finish() {
        if ( isset( $this->html_file ) ) {
            fwrite( $this->html_file, '</table>' );
            fclose( $this->html_file );
        }
    }

    function write_table_header( $input_class, $row ) {

        ob_start();

        ?>
        <div class="pwle-screen-export-table-title"><?php echo $input_class->title_plural; ?></div>
        <table class="pwle-screen-export-table">
            <tr>
                <?php
                    foreach ( $row as $column ) {
                        ?>
                        <th style="background-color: <?php echo $input_class->color; ?>;">
                            <?php echo esc_html( $column ); ?>
                        </th>
                        <?php
                    }
                ?>
            </tr>
        <?php

        $html = ob_get_clean();
        fwrite( $this->html_file, $html );
    }

    function write_table_row( $row ) {

        ob_start();

        ?>
        <tr>
        <?php
            foreach ( $row as $column ) {
                ?>
                <td>
                    <?php echo esc_html( $column ); ?>
                </td>
                <?php
            }
        ?>
        </tr>
        <?php

        $html = ob_get_clean();
        fwrite( $this->html_file, $html );
    }

    function send_file() {
        if ( file_exists( $this->output_filename ) ) {
            header( 'Content-Type: text/html; charset=utf-8' );
            header( 'Content-Disposition: inline; filename="' . $this->get_filename() . '"' );
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
}

global $pw_lets_export;
$pw_lets_export->output_classes[] = new PWLE_Output_Screen();

endif;

?>