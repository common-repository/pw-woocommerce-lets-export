<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Output' ) ) :

abstract class PWLE_Output {

    public $name;
    public $icon;
    public $color;
    public $extension;

    abstract function add_row( $input_class, $row );
    abstract function finish();

    function get_filename( $with_extension = true ) {
        // Before setting the date for the output file, make sure we're using the configured timezone.
        $configured_timezone = wc_timezone_string();
        if ( !empty( $configured_timezone ) ) {
            $original_timezone = date_default_timezone_get();
            date_default_timezone_set( $configured_timezone );
        }

        $filename = "Let's Export " . date( 'Y-m-d' );

        if ( true === $with_extension ) {
            $filename .= $this->extension;
        }

        // Now that we're done formatting, switch it back.
        if ( isset( $original_timezone ) ) {
            date_default_timezone_set( $original_timezone );
        }

        return $filename;
    }

    abstract function send_file();
}

endif;

?>