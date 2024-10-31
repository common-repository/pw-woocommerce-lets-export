jQuery(function() {

    jQuery('#pwle-filter-product-categories-select-all').on('click', function(e) {
        jQuery('#pwle-filter-product-categories option').prop('selected', true);
        jQuery('#pwle-filter-product-categories').focus();
        e.preventDefault();
        return false;
    });

    jQuery('#pwle-filter-product-categories-select-none').on('click', function(e) {
        jQuery('#pwle-filter-product-categories').val([]);
        e.preventDefault();
        return false;
    });

    jQuery('.pwle-quick-export-button').on('click', function(e) {
        var exportId = jQuery(this).closest('tr').attr('data-export-id');
        pwleQuickExportId = exportId;

        pwleExportType = jQuery(this).attr('data-export_type');
        pwleOutputFilename = '';

        pwleWizardLoadStep(4);

        e.preventDefault();
        return false;
    });

    jQuery('.pwle-delete-link').on('click', function(e) {
        var exportId = jQuery(this).closest('tr').attr('data-export-id');
        pwleDelete(exportId);
        e.preventDefault();
        return false;
    });

    window.addEventListener('popstate', function(event) {
        if (event.state) {
            var step = JSON.stringify(event.state);
            pwleWizardLoadStep(step, false, true);
        } else {
            pwleWizardClose();
        }
    });
});

function pwleActivate() {
    jQuery('.pwle-activation-error').text('');
    jQuery('#pwle-activate-license').prop('disabled', true).val('Activating, please wait...');

    jQuery.post(ajaxurl, {'action': 'pw-lets-export-activation', 'license-key': jQuery('#pwle-license-key').val() }, function( result ) {
        if (result.active == true) {
            location.reload();
        } else {
            jQuery('.pwle-activation-error').text(result.error);
            jQuery('#pwle-activate-license').prop('disabled', false).val('Activate');
        }
    }).fail(function(xhr, textStatus, errorThrown) {
        if (errorThrown) {
            alert('Error: ' + errorThrown + '\n\n pw-lets-export-activation');
        }
    });
}

function pwleWizardLoadStep(step, validate, skipHistory) {
    // This is located in all_steps.php since it's dynamic.
    if (validate == true && pwleWizardValidateStep(step - 1) == false) {
        return;
    }

    jQuery('.pwle-wizard-error').text('');

    if (!skipHistory) {
        history.pushState(step, null, pwleAdminUrl + '&step=' + step);
    }

    jQuery('#pwle-screen-export-table-container').html('');
    jQuery('#pwle-main-content').addClass('pwle-hidden');
    jQuery('.pwle-wizard-step').addClass('pwle-hidden');
    jQuery('#pwle-wizard-step-saving').addClass('pwle-hidden');
    jQuery('#pwle-wizard-step-' + step).removeClass('pwle-hidden');
    jQuery('#pwle-wizard-step-' + step).trigger('pwleIsLoading');
}

function pwleWizardFinish() {
    // This is located in all_steps.php since it's dynamic.
    if (pwleWizardValidateStep(pwleLastStep) == false) {
        return;
    }

    jQuery('.pwle-wizard-step').addClass('pwle-hidden');
    jQuery('#pwle-wizard-step-saving').removeClass('pwle-hidden');

    var settings = jQuery('#pwle-form').serialize();

    jQuery.post(ajaxurl, {'action': 'pw-lets-export-save-settings', 'settings': settings }, function( result ) {
        if (result.complete != true) {
            alert(result.message);
        }
        window.location = pwleAdminUrl;

    }).fail(function(xhr, textStatus, errorThrown) {
        if (errorThrown) {
            alert('Error: ' + errorThrown + '\n\n pw-lets-export-save-settings');
        }
        window.location = pwleAdminUrl;
    });
}

function pwleWizardClose() {
    jQuery('.pwle-wizard-step').addClass('pwle-hidden');
    jQuery('#pwle-main-content').removeClass('pwle-hidden');
}

function pwleDelete(exportId) {
    if (confirm('Are you sure you want to delete this saved configuration? This cannot be undone.')) {
        jQuery.post(ajaxurl, {'action': 'pw-lets-export-delete', 'export_id': exportId}, function() {
            location.reload();
        }).fail(function(xhr, textStatus, errorThrown) {
            if (errorThrown) {
                alert('Error: ' + errorThrown + '\n\n pw-lets-export-delete');
            }
            location.reload();
        });
    }
}

// Source: https://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function pwleGetParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

