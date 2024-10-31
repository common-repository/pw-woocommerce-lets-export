<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

    global $pw_lets_export;

    pwleWizardTitle( 'Fields' );

    foreach ( $pw_lets_export->input_classes as $class_name => $input_class ) {
        $fields = $input_class->get_fields();

        ?>
        <div class="pwle-object-fields" style="background-color: <?php echo $input_class->color; ?>;" data-class="<?php echo $class_name; ?>" data-title="<?php echo esc_attr( $input_class->title ); ?>">
            <div class="pwle-object-fields-header">
                <i class="fa fa-database" aria-hidden="true"></i> <?php echo $input_class->title; ?> fields
            </div>
            <div class="pwle-object-fields-container">
                <?php
                    if ( !empty( $fields ) ) {
                        ?>
                        <span class="pwle-object-fields-select-container">
                            Select
                            <a href="#" class="pwle-object-fields-select-all pwle-link">All</a> |
                            <a href="#" class="pwle-object-fields-select-none pwle-link">None</a>
                        </span>
                        <?php

                        $additional_fields = 0;
                        foreach( $fields as $field_id => $field ) {
                            if ( !$field->is_default ) { $additional_fields++; continue; }

                            if ( isset( $pwle_export ) && isset( $pwle_export->classes[ $class_name ] ) && isset( $pwle_export->classes[ $class_name ]['fields'] ) ) {
                                $saved_fields = $pwle_export->classes[ $class_name ]['fields'];
                                $is_selected = ( isset( $saved_fields[ $field_id ] ) && $saved_fields[ $field_id ] == 'on' );
                            } else {
                                $is_selected = $field->is_default;
                            }

                            $checked = checked( $is_selected, true, false );

                            $field_name = "fields|||{$class_name}|||{$field_id}";

                            $search_field = preg_replace( '/[^a-z0-9_]+/', '', strtolower( $field->name ) );

                            ?>
                            <label for="<?php echo $field_name; ?>" class="pwle-object-field-label pwle-noselect <?php echo ( $is_selected ) ? 'pwle-checkbox-checked' : ''; ?>" data-search="<?php echo $search_field; ?>" style="display: block;">
                                <input type="checkbox" name="<?php echo $field_name; ?>" id="<?php echo $field_name; ?>" class="pwle-object-field" <?php echo $checked; ?>>
                                <?php echo $field->name; ?>
                            </label>
                            <?php
                        }

                        ?>
                        <div class="pwle-show-all-fields-container">
                            <span class="pwle-hidden-field-count"><?php echo $additional_fields; ?></span> additional fields found. Upgrade to our <a href="<?php echo $pw_lets_export->pro_url; ?>" target="_blank">Pro Version</a> to export<br>additional fields. <?php echo $input_class->pro_message; ?>
                        </div>
                        <?php
                    } else {
                        echo 'There are no fields for this item.';
                    }
                ?>
            </div>
        </div>
        <?php
    }

    require( 'navigation_buttons.php' );
?>
<script>

    jQuery(function() {
        jQuery('.pwle-object-fields-select-all').on('click', function(e) {
            var fieldContainer = jQuery(this).closest('.pwle-object-fields-container');
            fieldContainer.find('.pwle-object-field').prop('checked', true).closest('.pwle-object-field-label').addClass('pwle-checkbox-checked');
            e.preventDefault();
            return false;
        });

        jQuery('.pwle-object-fields-select-none').on('click', function(e) {
            var fieldContainer = jQuery(this).closest('.pwle-object-fields-container');
            fieldContainer.find('.pwle-object-field').prop('checked', false).closest('.pwle-object-field-label').removeClass('pwle-checkbox-checked');
            e.preventDefault();
            return false;
        });

        jQuery('.pwle-object-field').on('change', function() {
            jQuery(this).closest('.pwle-object-field-label').toggleClass('pwle-checkbox-checked', this.checked);
        });
    });

    function pwleWizardLoadStep<?php echo $pwle_step; ?>() {
        jQuery('.pwle-object-fields').addClass('pwle-hidden');

        jQuery('.pwle-object-selected').each(function() {
            var className = jQuery(this).attr('data-class');
            jQuery('.pwle-object-fields[data-class="' + className + '"]').removeClass('pwle-hidden');
        });
    }

    function pwleWizardValidateStep<?php echo $pwle_step; ?>() {
        var valid = true;

        jQuery('.pwle-object-fields').each(function () {
            var fieldContainer = jQuery(this);

            if (!fieldContainer.hasClass('pwle-hidden') && fieldContainer.find('.pwle-object-field:checked').length == 0) {
                var title = fieldContainer.attr('data-title');
                jQuery('.pwle-wizard-error').text('Select at least one ' + title + ' field to export.');
                valid = false;
            }
        });

        return valid;
    }
</script>
