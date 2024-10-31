<?php
/**
 * Plugin Name: PW WooCommerce Let's Export!
 * Plugin URI: https://www.pimwick.com/pw-woocommerce-lets-export/
 * Description: Easily export data from WooCommerce.
 * Version: 1.31
 * Author: Pimwick, LLC
 * Author URI: https://www.pimwick.com
 * WC requires at least: 4.0
 * WC tested up to: 9.1
 * Requires Plugins: woocommerce
*/

/*
Copyright (C) Pimwick, LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !function_exists( 'pimwick_define' ) ) :
function pimwick_define( $constant_name, $default_value ) {
    defined( $constant_name ) or define( $constant_name, $default_value );
}
endif;

pimwick_define( 'PW_LETS_EXPORT_REQUIRES_PRIVILEGE', 'manage_woocommerce' );
pimwick_define( 'PW_LETS_EXPORT_USE_TRANSIENTS', true );
pimwick_define( 'PW_LETS_EXPORT_USE_FPUTCSV', true );

if ( ! class_exists( 'PW_Lets_Export' ) ) :

final class PW_Lets_Export {

    public $input_classes = array();
    public $output_classes = array();
    public $export;
    public $pro_url = 'https://pimwick.com/pw-woocommerce-lets-export/';

    function __construct() {
        global $wpdb;

        $wpdb->pw_lets_export = $wpdb->prefix . 'pw_lets_export';

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
        add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ) );
    }

    function plugins_loaded() {
        load_plugin_textdomain( 'pimwick', false, basename( dirname( __FILE__ ) ) . '/languages' );
    }

    function woocommerce_init() {
        if ( is_admin() ) {
            $this->load_required_files();

            $this->export = new PWLE_Export();

            add_action( 'init', array( 'PW_Lets_Export', 'register_post_types' ), 9 );

            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
            add_action( 'wp_ajax_pw-lets-export-save-settings', array( $this, 'ajax_save_settings' ) );
            add_action( 'wp_ajax_pw-lets-export-delete', array( $this, 'ajax_delete_export' ) );
            add_action( 'wp_ajax_pw-lets-export-prepare', array( $this, 'ajax_prepare_export' ) );
            add_action( 'wp_ajax_pw-lets-export-process', array( $this, 'ajax_process_export' ) );
            add_action( 'wp_ajax_pw-lets-export-status', array( $this, 'ajax_export_status' ) );
            add_action( 'wp_ajax_pw-lets-export-cancel', array( $this, 'ajax_cancel_export' ) );
        }

        add_action( 'activated_plugin', array( $this, 'clear_transients' ) );
        add_action( 'send_headers', array( $this, 'send_headers' ) );
    }

    function load_required_files() {
        if ( !$this->wc_min_version( '3.0' ) ) {
            require_once( 'includes/pwle-wc-legacy-functions.php' );
        }

        require_once( 'includes/wp-async-request.php' );
        require_once( 'includes/class-pwle-field.php' );
        require_once( 'includes/class-pwle-filter.php' );
        require_once( 'includes/class-pwle-export.php' );
        require_once( 'includes/abstracts/abstract-pwle-input.php' );
        require_once( 'includes/abstracts/abstract-pwle-output.php' );

        $input_class_files = array();
        foreach ( glob( trailingslashit( __DIR__ ) . 'includes/input/class-pwle-input-*.php' ) as $filename ) {
            $input_class_files[] = $filename;
        }
        $this->require_files( apply_filters( 'pwle_input_class_files', $input_class_files ) );

        $output_class_files = array();
        foreach ( glob( trailingslashit( __DIR__ ) . 'includes/output/class-pwle-output-*.php' ) as $filename ) {
            $output_class_files[] = $filename;
        }
        $this->require_files( apply_filters( 'pwle_output_class_files', $output_class_files ) );
    }

    function require_files( $files, $once = true ) {
        foreach ( $files as $file ) {
            if ( $once ) {
                require_once( $file );
            } else {
                require_once( $file );
            }
        }
    }

    function clear_transients() {
        // When another plugin is activated, we'll clear our transient so that we re-load the potential fields.
        foreach ( $this->input_classes as $input_class ) {
            $input_class->clear_transients();
        }
    }

    public static function plugin_activate() {
        global $wpdb;
        global $pw_lets_export;

        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        PW_Lets_Export::register_post_types();

        $collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

        $wpdb->query("
            CREATE TABLE IF NOT EXISTS {$wpdb->pw_lets_export} (
                class_name VARCHAR(30) NOT NULL,
                id BIGINT UNSIGNED NOT NULL,
                status SMALLINT NOT NULL DEFAULT 0,
                sort_order DECIMAL(12, 12) NULL,
                INDEX idx_{$wpdb->pw_lets_export}_covering (class_name, id)
            ) $collate;
        ");

        $pw_lets_export->clear_transients();
    }

    public static function plugin_deactivate() {
        global $wpdb;
        global $pw_lets_export;

        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->pw_lets_export}");
        $pw_lets_export->clear_transients();
    }

    public static function register_post_types() {
        if ( post_type_exists('pw_lets_export') ) {
            return;
        }

        $labels = array(
            'name'                  => _x( 'PW WooCommerce Let\'s Export', 'Post Type General Name', 'pimwick' ),
            'singular_name'         => _x( 'PW WooCommerce Let\'s Export', 'Post Type Singular Name', 'pimwick' ),
            'menu_name'             => __( 'PW Let\'s Export!', 'pimwick' ),
            'name_admin_bar'        => __( 'PW Let\'s Export!', 'pimwick' ),
            'archives'              => __( 'Export archives', 'pimwick' ),
            'parent_item_colon'     => __( 'Parent export:', 'pimwick' ),
            'all_items'             => __( 'All exports', 'pimwick' ),
            'add_new_item'          => __( 'Add new export', 'pimwick' ),
            'add_new'               => __( 'Create new export', 'pimwick' ),
            'new_item'              => __( 'New export', 'pimwick' ),
            'edit_item'             => __( 'Edit export', 'pimwick' ),
            'update_item'           => __( 'Update export', 'pimwick' ),
            'view_item'             => __( 'View export', 'pimwick' ),
            'search_items'          => __( 'Search exports', 'pimwick' ),
            'not_found'             => __( 'Not found', 'pimwick' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'pimwick' ),
            'featured_image'        => __( 'Logo', 'pimwick' ),
            'set_featured_image'    => __( 'Set Logo', 'pimwick' ),
            'remove_featured_image' => __( 'Remove Logo', 'pimwick' ),
            'use_featured_image'    => __( 'Use as Logo', 'pimwick' ),
            'insert_into_item'      => __( 'Insert into item', 'pimwick' ),
            'uploaded_to_this_item' => __( 'Uploaded to this item', 'pimwick' ),
            'items_list'            => __( 'Export list', 'pimwick' ),
            'items_list_navigation' => __( 'Export list navigation', 'pimwick' ),
            'filter_items_list'     => __( 'Filter export list', 'pimwick' ),
        );

        $args = array(
            'label'                 => __( 'PW Let\'s Export!', 'pimwick' ),
            'description'           => __( 'PW Let\'s Export!', 'pimwick' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'show_ui'               => true,
            'show_in_menu'          => false,
            'has_archive'           => true
        );

        register_post_type( 'pw_lets_export', $args );
    }

    function admin_menu() {
        if ( empty ( $GLOBALS['admin_page_hooks']['pimwick'] ) ) {
            add_menu_page(
                'PW Let\'s Export!',
                'Pimwick Plugins',
                PW_LETS_EXPORT_REQUIRES_PRIVILEGE,
                'pimwick',
                array( $this, 'index' ),
                plugins_url( '/assets/images/pimwick-icon-120x120.png', __FILE__ ),
                6
            );

            add_submenu_page(
                'pimwick',
                'PW Let\'s Export!',
                'Pimwick Plugins',
                PW_LETS_EXPORT_REQUIRES_PRIVILEGE,
                'pimwick',
                array( $this, 'index' )
            );

            remove_submenu_page( 'pimwick', 'pimwick' );
        }

        add_submenu_page(
            'pimwick',
            'PW Let\'s Export!',
            'PW Let\'s Export!',
            PW_LETS_EXPORT_REQUIRES_PRIVILEGE,
            'pw-lets-export',
            array( $this, 'index' )
        );
    }

    function index() {
        $data = get_plugin_data( __FILE__ );
        $version = $data['Version'];

        if ( isset( $_REQUEST['export_id'] ) ) {
            $export_id = absint( $_REQUEST['export_id'] );

            if ( !empty( $export_id ) ) {
                $pwle_export = get_post( $export_id );
                $pwle_export->settings = (array) maybe_unserialize( get_post_meta( $export_id, 'settings', true ) );
                $pwle_export->classes = $this->extract_classes( $pwle_export->settings );
            }
        }

        require( 'ui/index.php' );
    }

    function admin_enqueue_scripts( $hook ) {
        global $wp_scripts;

        if ( !empty( $hook ) && substr( $hook, -strlen( 'pw-lets-export' ) ) === 'pw-lets-export' ) {
            wp_register_style( 'pw-lets-export-style', $this->relative_url( '/assets/css/style.css' ), array(), $this->version() );
            wp_enqueue_style( 'pw-lets-export-style' );

            wp_register_style( 'pw-lets-export-font-awesome', $this->relative_url( '/assets/css/font-awesome.min.css' ), array(), $this->version() );
            wp_enqueue_style( 'pw-lets-export-font-awesome' );

            wp_enqueue_script( 'pw-lets-export-script', $this->relative_url( '/assets/js/script.js' ), array( 'jquery' ), $this->version() );

            wp_register_style( 'jquery-ui-style', $this->relative_url( '/assets/css/jquery-ui-style.min.css' ), array(), $this->version() );
            wp_enqueue_style( 'jquery-ui-style' );

            wp_enqueue_script( 'jquery-ui-datepicker' );
        }

        wp_register_style( 'pw-lets-export-icon', plugins_url( '/assets/css/icon-style.css', __FILE__ ), array(), $this->version() );
        wp_enqueue_style( 'pw-lets-export-icon' );
    }

    function ajax_save_settings() {
        if ( !isset( $_REQUEST['settings'] ) ) {
            wp_die('Invalid query string.');
        }

        $settings = array();
        parse_str( $_REQUEST['settings'], $settings );

        $export_id = absint( $settings['export_id'] );
        $title = wc_clean( stripslashes( $settings['title'] ) );

        if ( empty( $title ) ) {
            return;
        }

        if ( empty( $export_id ) ) {
            $export = array();
            $export['post_type'] = 'pw_lets_export';
            $export['post_status'] = 'publish';
            $export['post_title'] = $title;
            $export_id = wp_insert_post( $export );
        } else {
            $export = get_post( $export_id );
            $export->post_title = $title;
            wp_update_post( $export );
        }

        if ( !is_wp_error( $export_id ) ) {
            update_post_meta( $export_id, 'settings', $settings );
            wp_send_json( array( 'complete' => true ) );

        } else {
            wp_die( $export_id->get_error_message() );
        }
    }

    function ajax_delete_export() {
        $export_id = absint( $_POST['export_id'] );
        wp_delete_post( $export_id );
        wp_die();
    }

    function ajax_prepare_export() {
        global $wpdb;

        $result['record_count'] = 0;
        $this->clear_processing_table();

        $classes = $this->extract_classes_from_request();

        // Export and output the data.
        $input_class = null;
        foreach ( $classes as $class_name => $class_settings ) {
            $this->get_input_class( $class_name, $input_class );

            $result['record_count'] += $input_class->load_processing_table( $class_settings['filters'] );
        }

        wp_send_json( $result );
    }

    function ajax_process_export() {

        $this->reset_processing_table();

        $output_filename = wp_tempnam();

        $this->export->data(
            array(
                'export_type' => $_REQUEST['export_type'],
                'output_filename' => $output_filename,
                'classes' => $this->extract_classes_from_request()
            )
        );
        $this->export->dispatch();

        wp_send_json(
            array(
                'output_filename' => $output_filename
            )
        );
    }

    function ajax_export_status() {
        global $wpdb;

        $counts = $this->get_processing_table_counts();

        $result['total'] = $counts->total;
        $result['pending'] = $counts->pending;
        $result['canceled'] = $counts->canceled;

        wp_send_json( $result );
    }

    function ajax_cancel_export() {
        global $wpdb;

        $wpdb->query( "UPDATE {$wpdb->pw_lets_export} SET status = 2" );

        wp_send_json( array( 'message' => 'Canceled' ) );
    }

    function get_processing_table_counts() {
        global $wpdb;

        $counts = $wpdb->get_row( "
            SELECT
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS processed,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS canceled,
                COUNT(id) AS total
            FROM
                {$wpdb->pw_lets_export}
        " );

        return $counts;
    }

    function export_is_canceled() {
        global $wpdb;

        $counts = $this->get_processing_table_counts();
        if ( $counts->canceled > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    function clear_processing_table() {
        global $wpdb;
        $wpdb->query( "TRUNCATE TABLE {$wpdb->pw_lets_export}" );
    }

    function reset_processing_table() {
        global $wpdb;
        $wpdb->query( "UPDATE {$wpdb->pw_lets_export} SET status = 0" );
    }

    function insert_processing_records( $class_name, $ids ) {
        global $wpdb;

        if ( count( $ids ) == 0 ) {
            return;
        }

        $values = array();
        foreach ( $ids as $index => $id ) {
            $values[] .= $wpdb->prepare( "(%s, %d, %d)", $class_name, $id, $index );
        }

         $wpdb->query( "INSERT INTO {$wpdb->pw_lets_export} (class_name, id, sort_order) VALUES " . implode( ',', $values ) );
    }

    function get_records_to_process( $class_name ) {
        global $wpdb;

        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->pw_lets_export} WHERE class_name = %s AND status = 0 ORDER BY sort_order", $class_name ) );
    }

    function mark_record_as_processed( $class_name, $id ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->pw_lets_export} SET status = 1 WHERE class_name = %s AND id = %d", $class_name, $id ) );
    }

    function extract_classes_from_request() {
        $export_id = isset( $_REQUEST['export_id'] ) ? absint( $_REQUEST['export_id'] ) : 0;
        if ( !empty( $export_id ) ) {
            $settings = (array) maybe_unserialize( get_post_meta( $export_id, 'settings', true ) );
        } else {
            $settings = array();
            parse_str( $_REQUEST['settings'], $settings );
        }

        return $this->extract_classes( $settings );
    }

    function extract_classes( $settings ) {
        $selected_classes = array();
        foreach ( $settings['selected_classes'] as $hidden_input ) {
            if ( !empty( $hidden_input ) ) {
                $selected_classes[] = $hidden_input;
            }
        }

        $classes = array();
        foreach ( $settings as $post_key => $post_value ) {
            $matches = array();
            preg_match( '/(?P<setting_name>.*)\|\|\|(?P<class_name>.*)\|\|\|(?P<field>.*)/', $post_key, $matches );
            if ( count( $matches ) > 2 ) {
                $setting_name = $matches['setting_name'];
                $class_name = $matches['class_name'];
                $field = $matches['field'];
            } else {
                continue;
            }

            if ( !in_array( $class_name, $selected_classes ) ) {
                continue;
            }

            if ( !isset( $classes[ $class_name ] ) ) {
                $classes[ $class_name ] = array();
                $classes[ $class_name ]['filters'] = array();
                $classes[ $class_name ]['fields'] = array();
            }

            $classes[ $class_name ][ $setting_name ][ $field ] = $post_value;
        }

        return $classes;
    }

    function get_input_class( $class_name, &$input_class ) {
        if ( empty( $input_class ) || get_class( $input_class ) !== $class_name ) {
            foreach ( $this->input_classes as $c ) {
                if ( get_class( $c ) === $class_name ) {
                    $input_class = $c;
                    break;
                }
            }
        }

        if ( !is_a( $input_class, 'PWLE_Input' ) ) {
            wp_die( 'Invalid class name: ' . $class_name );
        }
    }

    function send_headers() {
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'pwle_export' && isset( $_REQUEST['export_type'] ) && isset( $_REQUEST['filename'] ) ) {
            if ( !current_user_can( PW_LETS_EXPORT_REQUIRES_PRIVILEGE ) ) { wp_die( 'Unauthorized.' ); }

            $filename = $_REQUEST['filename'];
            $export_type = $_REQUEST['export_type'];

            $this->load_required_files();

            $output_classname = "PWLE_Output_$export_type";
            $output_class = new $output_classname();

            if ( !is_a( $output_class, 'PWLE_Output' ) ) {
                wp_die( 'Invalid export type.' );
            }

            $output_class->output_filename = $filename;
            $output_class->send_file();
        }
    }

    function relative_url( $url ) {
        return plugins_url( $url, __FILE__ );
    }

    function require_file( $filename, $once = false ) {
        $relative_path = trailingslashit( __DIR__ ) . ltrim( $filename, '/' );
        if ( $once === true ) {
            require_once( $relative_path );
        } else {
            require( $relative_path );
        }
    }

    function version() {
        $data = get_plugin_data( __FILE__ );
        return $data['Version'];
    }

    function wc_min_version( $version ) {
        return version_compare( WC()->version, $version, ">=" );
    }

    function starts_with( $needle, $haystack ) {
        $length = strlen( $needle );
        return ( substr( $haystack, 0, $length ) === $needle );
    }

    function format_date( $date ) {
        $format = wc_date_format() . ' ' . wc_time_format();

        if ( $this->wc_min_version( '3.0' ) ) {
            return wc_format_datetime( wc_string_to_datetime( $date ), $format );
        } else {

            $timezone = new DateTimeZone( wc_timezone_string() );
            if ( is_numeric( $date ) ) {
                $date = new DateTime( "@{$date}" );
            } else {
                $date = new DateTime( $date, $timezone );
            }

            return $date->format( $format );
        }
    }

    /**
     * Source: http://wordpress.stackexchange.com/questions/14652/how-to-show-a-hierarchical-terms-list
     * Recursively sort an array of taxonomy terms hierarchically. Child categories will be
     * placed under a 'children' member of their parent term.
     * @param Array   $cats     taxonomy term objects to sort
     * @param Array   $into     result array to put them in
     * @param integer $parentId the current parent ID to put them in
     */
    function sort_terms_hierarchicaly( array &$cats, array &$into, $parentId = 0 ) {
        foreach ( $cats as $i => $cat ) {
            if ( $cat->parent == $parentId ) {
                $into[$cat->term_id] = $cat;
                unset( $cats[$i] );
            }
        }

        foreach ( $into as $topCat ) {
            $topCat->children = array();
            $this->sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
        }
    }

    function hierarchical_select( $categories, $selected_category_ids, $level = 0, $parent = NULL, $prefix = '' ) {
        foreach ( $categories as $category ) {
            $selected = selected( in_array( $category->slug, $selected_category_ids ), true, false );
            echo "<option value='" . esc_attr( $category->slug ) . "' $selected>$prefix " . esc_html( $category->name ) . "</option>\n";

            if ( $category->parent == $parent ) {
                $level = 0;
            }

            if ( count( $category->children ) > 0 ) {
                echo $this->hierarchical_select( $category->children, $selected_category_ids, ( $level + 1 ), $category->parent, "$prefix " . esc_html( $category->name ) . " &#8594;" );
            }
        }
    }
}

register_activation_hook( __FILE__, array( 'PW_Lets_Export', 'plugin_activate' ) );
register_deactivation_hook( __FILE__, array( 'PW_Lets_Export', 'plugin_deactivate' ) );

global $pw_lets_export;
$pw_lets_export = new PW_Lets_Export();

endif;
