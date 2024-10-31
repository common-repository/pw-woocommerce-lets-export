<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

    global $pw_lets_export;

    pwleWizardTitle( 'Save configuration', false );
?>

<div class="pwle-export-title-container">
    <div class="pwle-input-label">Title</div>
    <input type="text" id="pwle-title" name="title" class="pwle-input pwle-export-title" value="<?php echo isset( $pwle_export ) ? esc_html( $pwle_export->post_title ) : ''; ?>" required="required">
</div>

<?php
    require( 'navigation_buttons.php' );
?>
<script>

    function pwleWizardLoadStep<?php echo $pwle_step; ?>() {
        // Focus the input box with the cursor at the end.
        var titleInput = jQuery('#pwle-title');
        var title = titleInput.val();

        <?php
            if ( !isset( $pwle_export ) ) {
                ?>
                var selectedClasses = [];
                jQuery('.pwle-selected-classes[value!=""]').each(function() {
                    var classTitle = jQuery(this).attr('data-title-plural');
                    if (classTitle) {
                        selectedClasses.push(classTitle);
                    }
                });
                if (selectedClasses.length == 2) {
                    title = selectedClasses.join(' and ');
                } else {
                    title = selectedClasses.join(', ');
                }
                <?php
            }
        ?>

        titleInput.focus();
        titleInput.val('');
        titleInput.val(title);
    }

    function pwleWizardValidateStep<?php echo $pwle_step; ?>() {
        return true;

    }
</script>
