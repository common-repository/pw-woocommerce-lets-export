<?php
    if ( !defined( 'ABSPATH' ) ) { exit; }

    global $pw_lets_export;

    pwleWizardTitle( 'Let\'s Export!' );
?>

<div class="pwle-export-processing">
    <div id="pwle-export-processing-icon">
        <i class="fa fa-circle-o-notch fa-4x fa-spin" aria-hidden="true"></i>
    </div>
    <div id="pwle-export-processing-message" class="pwle-export-processing-message"></div>
    <div id="pwle-export-cancel-export-button" class="button button-secondary pwle-export-cancel-export-button pwle-hidden">Cancel</div>
</div>

<div class="pwle-export-container">
    <div class="pwle-export-buttons">
        <?php
            foreach ( $pw_lets_export->output_classes as $output_class ) {
                ?>
                <div class="pwle-export-button pwle-export-button-large" data-export_type="<?php echo $output_class->name; ?>">
                    <div style="background-color: <?php echo $output_class->color; ?>;">
                        <i class="fa <?php echo $output_class->icon; ?> fa-4x" aria-hidden="true"></i>
                    </div>
                    <div><?php echo $output_class->title; ?></div>
                </div>
                <?php
            }
        ?>
    </div>

    <div class="pwle-save-config-container">
        <p>Save the configuration if you would like to run this export again later.</p>
        <div class="button button-primary" onClick="pwleWizardLoadStep(<?php echo ( $pwle_step + 1 ); ?>, true);">Save Configuration</div>
    </div>
</div>

<?php
    require( 'navigation_buttons.php' );
?>
<script>

    var pwleExportType;
    var pwleOutputFilename;
    var pwleQuickExportId;

    jQuery('.pwle-export-button-large').on('click', function() {
        pwleExportType = jQuery(this).attr('data-export_type');
        pwleOutputFilename = '';

        pwleBeginExporting();
    });

    jQuery('#pwle-export-cancel-export-button').on('click', function() {
        pwleCancelExporting();
    });

    function pwleLoadRecords() {
        pwleBeginProcessing('Looking for records...');

        var settings = jQuery('#pwle-form').serialize();

        jQuery.post(ajaxurl, {'action': 'pw-lets-export-prepare', 'settings': settings, 'export_id': pwleQuickExportId}, function(result) {
            pwleDisplayTotalRecordCount(result.record_count);

            if (pwleQuickExportId) {
                pwleBeginExporting();
            } else {
                pwleEndProcessing();
            }

        }).fail(function(xhr, textStatus, errorThrown) {
            if (errorThrown) {
                pwleEndProcessing('Error: ' + errorThrown);
            }
        });
    }

    function pwleBeginExporting() {
        pwleBeginProcessing('Starting the export...');

        var settings = jQuery('#pwle-form').serialize();

        jQuery('#pwle-export-cancel-export-button').removeClass('pwle-hidden');
        jQuery('#pwle-export-processing-icon>i').removeClass('fa-circle-o-notch').addClass('fa-cog');

        jQuery.post(ajaxurl, {'action': 'pw-lets-export-process', 'export_type': pwleExportType, 'settings': settings, 'export_id': pwleQuickExportId}, function(result) {
            pwleOutputFilename = result.output_filename;
            pwleUpdateStatus();

        }).fail(function(xhr, textStatus, errorThrown) {
            if (errorThrown) {
                alert('Error: ' + errorThrown + '\n\n pw-lets-export-process');
            }
            pwleEndProcessing('Error: ' + errorThrown);
        });
    }

    function pwleFinishExporting(recordCount) {
        var url = '<?php echo get_site_url(); ?>?action=pwle_export&export_type=' + pwleExportType + '&filename=' + pwleOutputFilename;

        if (pwleExportType == 'Screen') {
            jQuery('#pwle-export-processing-message').text('Loading table...');
            jQuery('#pwle-screen-export-table-container').load(url, function() {
                pwleFinishedExportingMessage(recordCount);

                jQuery('html, body').animate({
                    scrollTop: jQuery("#pwle-screen-export-table-container").offset().top
                }, 500);
            });
        } else {
            window.open( url, '_self');
            pwleFinishedExportingMessage(recordCount);
        }
    }

    function pwleFinishedExportingMessage(recordCount) {
        if (parseInt(recordCount) == 1) {
            pwleEndProcessing('Exported 1 record to ' + pwleExportType);
        } else {
            pwleEndProcessing('Exported ' + parseInt(recordCount).toLocaleString() + ' records to ' + pwleExportType);
        }
    }

    function pwleUpdateStatus() {
        jQuery.post(ajaxurl, {'action': 'pw-lets-export-status'}, function(result) {
            if (result.canceled > 0) {
                pwleEndProcessing('Canceled');
                pwleDisplayTotalRecordCount(result.total);

            } else if (result.pending > 0) {
                var percentage = ( ( result.total - result.pending ) / result.total ) * 100.0;

                if (!jQuery('#pwle-export-processing-icon').hasClass('pwle-hidden')) {
                    jQuery('#pwle-export-processing-message').text(percentage.toFixed(2) + '%');

                    setTimeout(function() {
                        pwleUpdateStatus();
                    }, 1000);
                }
            } else {
                pwleFinishExporting(result.total);
            }

        }).fail(function(xhr, textStatus, errorThrown) {
            if (errorThrown) {
                alert('Error: ' + errorThrown + '\n\n pw-lets-export-process');
            }
            pwleEndProcessing('Error: ' + errorThrown);
        });
    }

    function pwleCancelExporting() {
        var cancelButton = jQuery('#pwle-export-cancel-export-button');
        if (cancelButton.text() != 'Stopping export...') {
            jQuery.post(ajaxurl, {'action': 'pw-lets-export-cancel'});

            cancelButton.text('Stopping export...');
        }
    }

    function pwleDisplayTotalRecordCount(count) {
        if (parseInt(count) == 1) {
            jQuery('#pwle-export-processing-message').text('1 record found');
        } else {
            jQuery('#pwle-export-processing-message').text(parseInt(count).toLocaleString() + ' records found');
        }
    }

    function pwleBeginProcessing(message) {
        jQuery('#pwle-screen-export-table-container').html('');
        jQuery('#pwle-export-processing-message').text(message);
        jQuery('#pwle-export-processing-icon').removeClass('pwle-hidden');
        jQuery('.pwle-save-config-container').addClass('pwle-hidden');
        jQuery('.pwle-export-container').css('visibility', 'hidden');
        jQuery('.pwle-wizard-button-container').addClass('pwle-hidden');
    }

    function pwleEndProcessing(message) {
        if (pwleQuickExportId) {
            if (message.startsWith('Error')) {
                alert(message);
            }
            pwleWizardClose();
            pwleQuickExportId = 0;
            return;
        }

        if (message) {
            jQuery('#pwle-export-processing-message').text(message);
        }
        jQuery('#pwle-export-processing-icon').addClass('pwle-hidden');
        jQuery('.pwle-export-container').css('visibility', 'visible');
        jQuery('.pwle-save-config-container').removeClass('pwle-hidden');
        jQuery('.pwle-wizard-button-container').removeClass('pwle-hidden');
        jQuery('#pwle-export-processing-icon>i').removeClass('fa-cog').addClass('fa-circle-o-notch');
        jQuery('#pwle-export-cancel-export-button').text('Cancel').addClass('pwle-hidden');
    }

    function pwleWizardLoadStep<?php echo $pwle_step; ?>() {
        if (pwleQuickExportId) {
            jQuery('.pwle-heading-step').addClass('pwle-hidden');
        } else {
            jQuery('.pwle-heading-step').removeClass('pwle-hidden');
        }

        pwleLoadRecords();
    }

    function pwleWizardValidateStep<?php echo $pwle_step; ?>() {
        return true;
    }

</script>
