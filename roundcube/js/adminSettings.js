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
        $('#adminmail_success_message').hide();
        $('#adminmail_error_message').hide();
        $('#adminmail_update_message').show();
        // Ajax foobar
        $.post(OC.filePath('roundcube', 'ajax', 'adminSettings.php'), postData, function(data) {
            $('#adminmail_update_message').hide();
            if (data.status == 'success') {
                $('#maildir').val(data.config.maildir);
                $('#adminmail_success_message').text(data.message).show();
                window.setTimeout(function() {
                    $('#adminmail_success_message').hide();
                }, 1000);
            } else {
                $('#adminmail_error_message').text(data.message).show();
            }
        }, 'json');
        return false;
    });
}

$(document).ready(function() {
    Roundcube.adminSettingsUI();
});
