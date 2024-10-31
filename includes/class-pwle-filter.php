<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'PWLE_Filter' ) ) :

class PWLE_Filter {

    private $parent_class = '';
    public $type = 'text';
    public $name = '';
    public $key = '';
    public $title = '';
    public $options = array();
    public $is_meta = false;
    public $placeholder = '';
    public $default_data = '';

    function __construct( $parent_class ) {
        $this->parent_class = $parent_class;
    }

    function default_html() {
        $this->html( null );
    }

    function html( $saved_filters ) {
        global $pw_lets_export;

        $filter_name = "filters|||{$this->parent_class}|||{$this->name}";
        if ( count( $this->options ) > 1 ) {
            $filter_name .= '[]';
        }

        $data = array();
        if ( is_null( $saved_filters ) ) {
            $data = $this->default_data;
        } else {
            if ( isset( $saved_filters[ $this->name ] ) ) {
                $data = $saved_filters[ $this->name ];
            }
            if ( !is_array( $data ) ) {
                $data = array( $data );
            }
        }

        ?>
        <div class="pwle-filter-title"><?php echo $this->title; ?></div>
        <?php

        switch ( strtolower( $this->type ) ) {
            case 'text':
            break;

            case 'checkbox':
                if ( !empty( $this->options ) ) {
                    echo '<div style="margin-left: 2.0em;">';

                    $index = 0;
                    foreach ( $this->options as $key => $value ) {
                        $id = str_replace( '[]', '', $filter_name ) . "_$index";

                        ?>
                        <label for="<?php echo $id; ?>" class="pwle-filter pwle-filter-checkbox pwle-noselect <?php echo ( in_array( $key, $data ) ) ? 'pwle-checkbox-checked' : ''; ?>">
                            <input type="checkbox" name="<?php echo $filter_name; ?>" id="<?php echo $id; ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $data ) ); ?>>
                            <?php echo esc_html( $value ); ?>
                        </label>
                        <?php

                        $index++;
                    }

                    echo '</div>';
                }

            break;

            case 'select':
            case 'multiselect':
                if ( !empty( $this->options ) ) {
                    ?>
                    <div style="margin-left: 2.0em;">
                        <select name="<?php echo $filter_name; ?>" <?php echo ( 'multiselect' == strtolower( $this->type ) ) ? 'multiple' : ''; ?>>
                        <?php
                            foreach ( $this->options as $key => $value ) {
                                ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( in_array( $key, $data ) ); ?>><?php echo esc_html( $value ); ?></option>
                                <?php
                            }
                        ?>
                        </select>
                    </div>
                    <?php
                }
            break;

            case 'date_before_after':

                $after_filter_name = $filter_name . '_after';
                $before_filter_name = $filter_name . '_before';

                $after_date = isset( $saved_filters[ $this->name . '_after' ] ) ? $saved_filters[ $this->name . '_after' ] : '';
                $before_date = isset( $saved_filters[ $this->name . '_before' ] ) ? $saved_filters[ $this->name . '_before' ] : '';

                ?>
                <div style="margin-left: 2.0em;">
                    <label for="<?php echo $after_filter_name; ?>" class="pwle-filter pwle-noselect">
                        From
                        <input type="text" name="<?php echo $after_filter_name; ?>" id="<?php echo $after_filter_name; ?>" value="<?php echo esc_attr( $after_date ); ?>" />
                    </label>
                    <label for="<?php echo $before_filter_name; ?>" class="pwle-filter pwle-noselect">
                        To
                        <input type="text" name="<?php echo $before_filter_name; ?>" id="<?php echo $before_filter_name; ?>" value="<?php echo esc_attr( $before_date ); ?>" />
                    </label>
                </div>
                <script>
                    jQuery(function() {
                        var dates = jQuery(this).find('#<?php echo $this->jquery_escape( $after_filter_name ); ?>, #<?php echo $this->jquery_escape( $before_filter_name ); ?>').datepicker({
                            defaultDate: '',
                            dateFormat: 'yy-mm-dd',
                            numberOfMonths: 1,
                            showButtonPanel: true,
                            onSelect: function(selectedDate) {
                                var option   = jQuery(this).is('#<?php echo $this->jquery_escape( $after_filter_name ); ?>') ? 'minDate' : 'maxDate';
                                var instance = jQuery(this).data('datepicker');
                                var date     = jQuery.datepicker.parseDate( instance.settings.dateFormat || jQuery.datepicker._defaults.dateFormat, selectedDate, instance.settings);
                                dates.not(this).datepicker('option', option, date);
                            }
                        });
                    });
                </script>
                <?php
            break;

            case 'categories':
                ?>
                <div style="position: relative;">
                    <div style="position: absolute; right: 0; bottom: 0;">
                        Select
                        <a href="#" id="pwle-filter-product-categories-select-all" class="pwle-link">All</a> |
                        <a href="#" id="pwle-filter-product-categories-select-none" class="pwle-link">None</a>
                    </div>
                </div>
                <select name="<?php echo $filter_name; ?>" id="pwle-filter-product-categories" class="wc-enhanced-select pwle-filter-product-categories" multiple="multiple">
                    <?php
                        $sorted = array();
                        $pw_lets_export->sort_terms_hierarchicaly( $this->options, $sorted );
                        $pw_lets_export->hierarchical_select( $sorted, $data );
                    ?>
                </select>
                <?php
            break;

            case 'numeric':
                $value = isset( $data[0] ) ? $data[0] : '';
                ?>
                <div style="margin-left: 2.0em;">
                    <label for="<?php echo $filter_name; ?>" class="pwle-filter pwle-noselect">
                       <input type="number" min="0" step="1" name="<?php echo $filter_name; ?>" id="<?php echo $filter_name; ?>" value="<?php echo esc_attr( $value ); ?>" class="pwle-input-number" />
                    </label>
                </div>
                <?php
            break;

            case 'currency':
                $value = isset( $data[0] ) ? $data[0] : '';
                ?>
                <div style="margin-left: 2.0em; position: relative;">
                    <label for="<?php echo $filter_name; ?>" class="pwle-filter pwle-noselect">
                       <span style="position: absolute; top: 6px; left: -12px;"><?php echo get_woocommerce_currency_symbol(); ?></span>
                       <input type="number" min="0" step="any" name="<?php echo $filter_name; ?>" id="<?php echo $filter_name; ?>" value="<?php echo esc_attr( $value ); ?>" class="pwle-input-number" />
                    </label>
                </div>
                <?php
            break;
        }
    }

    private function jquery_escape( $value ) {
        return str_replace( '|', '\\\\|', $value );
    }
}

endif;

?>