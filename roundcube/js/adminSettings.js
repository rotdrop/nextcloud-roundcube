// declare namespace
var Roundcube = Roundcube || {};

/**
 * Init admin settings view.
 */
Roundcube.adminSettingsUI = function() {
    if ($('#roundcube').length <= 0) {
        return;
    }
    $('#rcAddDomainPath').click(function() {
        document.getElementById('rcTableDomainPath').tBodies[0].appendChild(
            document.importNode(
                document.getElementById('rcDomainPath').content,
                true
            )
        );
    });
    $('#rcTableDomainPath').on('click', '.action.delete', function() {
        $(this).closest('tr').remove();
    });
    $('#rcAdminSubmit').click(function(event) {
        event.preventDefault();

        // Prevent CSRF with OC request token
        $('#requesttoken').val(oc_requesttoken);
        var data = $('#rcAdminSettings').serialize();
        $('#rc_save_success, #rc_save_error').hide();
        $('#rc_save_status').show();
        // Ajax
        $.post(OC.filePath('roundcube', 'ajax', 'adminSettings.php'), data, function(res) {
            $('#rc_save_status').hide();
            if (res.status == 'success') {
                $('#defaultRCPath').val(res.config.defaultRCPath);
                $('#rc_save_success').text(res.message).show().delay(2000).fadeOut(2000);
            } else if (res.status == 'error') {
                $('#rc_save_error').text(res.message).show();
            }
        }, 'json');
        return false;
    });
}

$(document).ready(function() {
    Roundcube.adminSettingsUI();
});
