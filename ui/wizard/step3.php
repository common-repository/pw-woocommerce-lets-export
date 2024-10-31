<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

    global $pw_lets_export;

    pwleWizardTitle( 'Filters' );

    foreach ( $pw_lets_export->input_classes as $class_name => $input_class ) {
        $filters = $input_class->get_filters();

        ?>
        <div class="pwle-object-filters" style="background-color: <?php echo $input_class->color; ?>;" data-class="<?php echo $class_name; ?>">
            <div class="pwle-object-filters-header">
                <i class="fa fa-filter" aria-hidden="true"></i> <?php echo $input_class->title; ?> filters
            </div>
            <div class="pwle-object-filters-container">
                <?php
                    if ( !empty( $filters ) ) {
                        foreach ( $filters as $filter ) {
                            if ( isset( $pwle_export ) && isset( $pwle_export->classes[ $class_name ] ) && isset( $pwle_export->classes[ $class_name ]['filters'] ) ) {
                                $saved_filters = $pwle_export->classes[ $class_name ]['filters'];
                                $filter->html( $saved_filters );

                            } else {
                                $filter->default_html( $data );
                            }

                            // Add some separation between each group of filters.
                            echo '<div style="margin-bottom: 1.5em;"></div>';
                        }
                    } else {
                        echo 'There are no filters for this item.';
                    }
                ?>
            </div>
        </div>
        <?php
    }

    require( 'navigation_buttons.php' );
?>
<script>

    function pwleWizardLoadStep<?php echo $pwle_step; ?>() {
        jQuery('.pwle-object-filters').addClass('pwle-hidden');

        jQuery('.pwle-object-selected').each(function() {
            var className = jQuery(this).attr('data-class');
            jQuery('.pwle-object-filters[data-class="' + className + '"]').removeClass('pwle-hidden');
        });

        jQuery('.pwle-filter-checkbox').find(':checkbox').on('change', function() {
            jQuery(this).closest('.pwle-filter-checkbox').toggleClass('pwle-checkbox-checked', this.checked);
        });
    }

    function pwleWizardValidateStep<?php echo $pwle_step; ?>() {
        return true;
    }
</script>
