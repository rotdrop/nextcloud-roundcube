/**
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

// jQuery stuff

import { appName } from '../../config.js';
import { getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth';
const jQuery = require('jquery');

if (window.jQuery && window.jQuery !== jQuery) {
  console.info(appName + ': JQUERY VERSIONS W / A', window.jQuery.fn.jquery, jQuery.fn.jquery);
  // if (window.jQuery.fn.jquery === jQuery.fn.jquery) {
  //   console.info(appName + ': using matching window.jQuery version');
  //   jQuery = window.jQuery;
  // }
}

let requestToken = getRequestToken() || '';

jQuery.ajaxSetup({
  beforeSend(xhr) {
    xhr.setRequestHeader('requesttoken', requestToken);
  },
});

onRequestTokenUpdate(token => { requestToken = token; });

export default jQuery;
