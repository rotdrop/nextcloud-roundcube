// declare namespace
var Roundcube = Roundcube || {};

/**
 * init admin settings view
 */
Roundcube.adminSettingsUI = function() {
    if ($('#roundcube').length <= 0) {
        return;
    }
    $('#rcAdminSubmit').click(function(event) {
        event.preventDefault();

        $('#requesttoken').val(oc_requesttoken);
        var postData = $('#rcMailAdminPrefs').serialize();
        $('#rc_save_success').hide();
        $('#rc_save_error').hide();
        $('#rc_save_status').show();
        // Ajax foobar
        $.post(OC.filePath('roundcube', 'ajax', 'adminSettings.php'), postData, function(data) {
            $('#rc_save_status').hide();
            if (data.status == 'success') {
                $('#maildir').val(data.config.maildir);
                $('#rc_save_success').text(data.message).show().delay(2000).fadeOut(2000);
                $('#rc_save_error').text(data.message).show();
            }
        }, 'json');
        return false;
    });
}

$(document).ready(function() {
    Roundcube.adminSettingsUI();
});
