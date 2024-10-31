<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<div class="pwle-wizard-button-container">
    <div class="pwle-wizard-error"></div>
    <?php
        if ( $pwle_step == $pwle_last_step ) {
            ?>
            <div style="float: left;">
                <a href="<?php echo admin_url( 'admin.php?page=pw-lets-export' ); ?>" class="button button-secondary">Do not save</a>
            </div>
            <div onClick="pwleWizardFinish();" class="pwle-wizard-next-previous-button pwle-wizard-finish-button pwle-noselect">Save</div>
            <?php
        } else if ( $pwle_step < ( $pwle_last_step - 1 ) ) {
            ?>
            <div onClick="pwleWizardLoadStep(<?php echo ( $pwle_step + 1 ); ?>, true);" class="pwle-wizard-next-previous-button pwle-wizard-next-button pwle-noselect">Next</div>
            <?php
        }

        if ( $pwle_step > 1 ) {
            ?>
            <div onClick="history.back();" class="pwle-wizard-next-previous-button pwle-wizard-previous-button pwle-noselect">Back</div>
            <?php
        }
    ?>
</div>
