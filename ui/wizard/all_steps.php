<?php if ( !defined( 'ABSPATH' ) ) { exit; } ?>
<?php

global $pw_lets_export;
global $pwle_step;
global $pwle_last_step;

function pwleWizardTitle( $title, $show_steps = true ) {
    ?>
    <div class="pwle-heading">
        <?php
            if ( true === $show_steps ) {
                ?>
                <div class="pwle-heading-step">Step <?php echo $GLOBALS['pwle_step']; ?> of <?php echo ( $GLOBALS['pwle_last_step'] - 1 ); ?></div>
                <?php
            }
        ?>
        <?php echo $title; ?>
    </div>
    <?php
}

// Calculate the value for last_step.
$pwle_step = 1;
while ( file_exists( dirname( __FILE__ ) . '/step' . $pwle_step . '.php' ) ) {
    $pwle_last_step = $pwle_step;
    $pwle_step++;
}

// Load the steps.
for ( $pwle_step = 1; $pwle_step <= $pwle_last_step; $pwle_step++ ) {
    // Prevent flickering on initial load. Don't want to hide step 1 then show it after page loads. Instead, start out visible.
    $hidden = ( $pwle_step == 1 && ( isset( $pwle_export ) || count( $exports ) == 0 ) ) ? '' : 'pwle-hidden';

    ?>
    <div id="pwle-wizard-step-<?php echo $pwle_step; ?>" class="pwle-wizard-step pwle-bordered-container <?php echo $hidden; ?>">
        <?php
            require( 'step' . $pwle_step . '.php' );
        ?>
    </div>
    <script>
        jQuery('#pwle-wizard-step-<?php echo $pwle_step; ?>').bind('pwleIsLoading', pwleWizardLoadStep<?php echo $pwle_step; ?>);
    </script>
    <?php
}

?>
<div id="pwle-wizard-step-saving" class="pwle-wizard-step pwle-bordered-container pwle-hidden">
    <div style="text-align: center;">
        <div class="pwle-heading">Saving...</div>
        <img src="<?php echo $pw_lets_export->relative_url( '/assets/images/spinner-2x.gif' ); ?>" class="pwle-spinner">
    </div>
</div>
<script>
    var pwleLastStep = <?php echo $pwle_last_step; ?>;

    function pwleWizardValidateStep(step) {
        switch (step) {
            <?php
                for ( $pwle_step = 1; $pwle_step <= $pwle_last_step; $pwle_step++ ) {
                    ?>
                    case <?php echo $pwle_step; ?>:
                        if (!pwleWizardValidateStep<?php echo $pwle_step; ?>()) {
                            return false;
                        }
                    break;
                    <?php
                }
            ?>
        }
    }
</script>