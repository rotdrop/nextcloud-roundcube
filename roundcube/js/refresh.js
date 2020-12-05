/**
 * nextCloud - RoundCube mail plugin
*
 * @author Claus-Justus Heine
 * @copyright 2013-2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

var RoundCube = RoundCube || {};
if (!RoundCube.appName) {
    const state = OCP.InitialState.loadState('roundcube', 'initial');
    RoundCube = $.extend({}, state);
    RoundCube.refreshTimer = false;
    console.debug("RoundCube", RoundCube);
}

(function(window, $, RoundCube) {

    RoundCube.refresh = function() {
        const self = this;
        if (!(RoundCube.refreshInterval >= 30)) {
            console.error("Refresh interval too short", RoundCube.refreshInterval);
            RoundCube.refreshInterval = 30;
        }
        if (OC.currentUser) {
            const url = OC.generateUrl('apps/'+this.appName+'/authentication/refresh');
            this.refresh = function(){
                if (OC.currentUser) {
                    $.post(url, {}).always(function () {
                        console.info('RoundCube refresh scheduled', self.refreshInterval * 1000);
                        self.refreshTimer = setTimeout(self.refresh, self.refreshInterval*1000);
                    });
                } else if (self.refreshTimer !== false) {
                    clearTimeout(self.refreshTimer);
                    self.refreshTimer = false;
                }
            };
            console.info('RoundCube refresh scheduled', this.refreshInterval * 1000);
            this.refreshTimer = setTimeout(this.refresh, this.refreshInterval*1000);
        } else if (this.refreshTimer !== false) {
            console.info('OC.currentUser appears unset');
            clearTimeout(this.refreshTimer);
            self.refreshTimer = false;
        }
    };

})(window, jQuery, RoundCube);

$(function() {
    console.info('Starting RoundCube refresh');
    RoundCube.refresh();
});
