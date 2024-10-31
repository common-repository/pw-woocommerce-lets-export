<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Field' ) ) :

class PWLE_Field {
    public $name = '';
    public $property = '';
    public $is_meta = false;
    public $default = false;
    public $is_builtin = false;
}

endif;

?>