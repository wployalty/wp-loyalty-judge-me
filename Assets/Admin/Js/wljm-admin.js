if (typeof (wljm_jquery) == 'undefined') {
    wljm_jquery = jQuery.noConflict();
}
wljm_jquery(document).on('click', '#wljm-settings #wljm-setting-submit-button', function () {
    let form_id = '#wljm-settings #wljm-settings-form';
    let data = wljm_jquery(form_id).serializeArray();
    wljm_jquery('#wljm-settings #wljm-setting-submit-button').attr('disabled', true);
    wljm_jquery('#wljm-settings .wljm-error').remove();
    wljm_jquery("#wljm-settings #wljm-setting-submit-button").html('<i class="wlr wlrf-save"></i>' + wljm_localize_data.saving_button_label);
    wljm_jquery.ajax({
        data: data,
        type: 'post',
        url: wljm_localize_data.ajax_url,
        error: function (request, error) {
        },
        success: function (json) {
            alertify.set('notifier', 'position', 'top-right');
            if (json.success == false) {
                wljm_jquery("#wljm-settings #wljm-setting-submit-button").html('<i class="wlr wlrf-save"></i>' + wljm_localize_data.saved_button_label);
                wljm_jquery('#wljm-settings #wljm-setting-submit-button').attr('disabled', true);
                if (json.message) {
                    alertify.error(json.message);
                }

                if (json.field_error) {
                    wljm_jquery.each(json.field_error, function (index, value) {
                        //alertify.error(value);
                        wljm_jquery(`#wljm-settings #wljm-settings-form .wljm_${index}_value_block`).after('<span class="wljm-error" style="color: red;">' + value + '</span>');
                    });
                }
            } else {
                alertify.success(json.message);
                setTimeout(function () {
                    /*wljm_jquery("#wljm-settings .wljm-button-block .spinner").removeClass("is-active");*/
                    location.reload();
                }, 800);
            }
        }
    });
});
wljm_jquery(document).on('click', '#wljm-main-page #wljm-webhook-delete', function () {
    let webhook_key = wljm_jquery(this).data('webhook-key');
    let button = wljm_jquery(this);
    wljm_jquery.ajax({
        data: {
            webhook_key: webhook_key,
            action: 'wljm_webhook_delete',
            wljm_nonce: wljm_localize_data.delete_nonce
        },
        type: 'post',
        url: wljm_localize_data.ajax_url,
        beforeSend: function () {
            let confirm_status = confirm(wljm_localize_data.confirm_label);
            if (confirm_status === true) {
                button.attr('disabled', true);
                button.html(wljm_localize_data.deleting_button_label);
            } else {
                button.attr('disabled', false);
                button.html(wljm_localize_data.delete_button_label);
                return false;
            }
        },
        error: function (request, error) {
        },
        success: function (json) {
            alertify.set('notifier', 'position', 'top-right');
            button.attr('disabled', false);
            button.html(wljm_localize_data.delete_button_label);
            if (json.success == true) {
                alertify.success(json.message);
                setTimeout(function () {
                    location.reload();
                }, 800);
            } else if (json.success == false) {
                alertify.error(json.message);
            }
        }
    });
});

wljm_jquery(document).on('click', '#wljm-main-page #wljm-webhook-create', function () {
    let webhook_key = wljm_jquery(this).data('webhook-key');
    let button = wljm_jquery(this);
    wljm_jquery(this).attr('disabled', true);
    wljm_jquery(this).html(wljm_localize_data.creating_button_label);
    wljm_jquery.ajax({
        data: {
            webhook_key: webhook_key,
            action: 'wljm_webhook_create',
            wljm_nonce: wljm_localize_data.create_nonce
        },
        type: 'post',
        url: wljm_localize_data.ajax_url,
        error: function (request, error) {
        },
        success: function (json) {
            alertify.set('notifier', 'position', 'top-right');
            button.attr('disabled', false);
            button.html(wljm_localize_data.create_button_label);
            if (json.success == true) {
                alertify.success(json.message);
                setTimeout(function () {
                    location.reload();
                }, 800);
            } else if (json.success == false) {
                alertify.error(json.message);
            }
        }
    });
});