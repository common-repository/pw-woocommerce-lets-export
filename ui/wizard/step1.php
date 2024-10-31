<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

    global $pw_lets_export;

    pwleWizardTitle( 'What do you want to export?' );
?>
<div class="pwle-object-container">
    <?php
        foreach ( $pw_lets_export->input_classes as $class_name => $input_class ) {
            $is_selected = ( true === $input_class->is_default );

            if ( isset( $pwle_export ) ) {
                foreach ( $pwle_export->settings['selected_classes'] as $selected_class ) {
                    if ( $class_name == $selected_class ) {
                        $is_selected = true;
                        break;
                    }
                }
            }

            if ( $is_selected ) {
                $object_class = 'pwle-object-selected';
                $icon_class = '';
            } else {
                $object_class = '';
                $icon_class = 'pwle-hidden';
            }

            ?>
            <input type="hidden" name="selected_classes[]" class="pwle-selected-classes" value="<?php echo ( $is_selected ) ? $class_name : ''; ?>" data-class="<?php echo $class_name; ?>" data-title-plural="<?php echo $input_class->title_plural; ?>">

            <div class="pwle-object pwle-noselect <?php echo $object_class; ?>" style="background-color: <?php echo $input_class->color; ?>;" data-class="<?php echo $class_name; ?>">
                <div class="pwle-object-text">
                    <?php echo $input_class->title_plural; ?>

                    <div class="pwle-object-icon <?php echo $icon_class; ?>">
                        <i class="fa fa-check"></i>
                    </div>
                </div>
            </div>
            <?php
        }
    ?>
</div>
<?php
    require( 'navigation_buttons.php' );
?>
<script>
    jQuery(function() {
        jQuery('.pwle-object').on('click', function() {
            jQuery(this).toggleClass('pwle-object-selected');
            jQuery(this).find('.pwle-object-icon').toggleClass('pwle-hidden');

            var className = jQuery(this).attr('data-class');
            var selected = '';
            if (jQuery(this).hasClass('pwle-object-selected')) {
                selected = className;
            }
            jQuery('.pwle-selected-classes[data-class="' + className + '"]').val(selected);
        });
    });

    function pwleWizardLoadStep<?php echo $GLOBALS['pwle_step']; ?>() {

    }

    function pwleWizardValidateStep<?php echo $GLOBALS['pwle_step']; ?>() {
        var valid = true;

        if (jQuery('.pwle-object-selected').length <= 0) {
            jQuery('.pwle-wizard-error').text('Select at least one object.');
            valid = false;
        }

        return valid;
    }
</script>