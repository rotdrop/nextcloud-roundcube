// declare namespace

var Roundcube = Roundcube || {};

/**
 * Set client side refresh job
 */
Roundcube.routes = function() {
    if (OC.currentUser) {
        if (Roundcube.refreshInterval) {
            var url = OC.generateUrl('/apps/roundcube/refresh');
            Roundcube.refresh = setInterval(function() {
                if (OC.currentUser) {
                    $.post(url);
                } else {
                    // if user is null end up refresh (logged out)
                    clearTimeout(Roundcube.refresh);
                }
            }, Roundcube.refreshInterval * 1000);
            return true;
        }
    } else {
        // if user is null end up refresh (logged out)
        clearTimeout(Roundcube.refresh);
        return false;
    }
}

$(document).ready(function() {
    Roundcube.routes();
});
