/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * Nextcloud RoundCube App is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * Nextcloud RoundCube App is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with Nextcloud RoundCube App. If not, see
 * <http://www.gnu.org/licenses/>.
 */

const appInfo = require('../appinfo/info.xml');
const appName = appInfo.info.id[0];
const webPrefix = appName;
const webRoot = OC.appswebroots[appName] + '/';
const cloudUser = OC.currentUser;

let state = OCP.InitialState.loadState(appName, 'initial');
state = $.extend({}, state);
state.refreshTimer = false;

if (appName !== state.appName) {
  throw new Error('appName / state.appName are different: ' + appName + ' / ' + state.appName);
}

export {
  state,
  appInfo,
  appName,
  webRoot,
  webPrefix,
  cloudUser,
};

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
