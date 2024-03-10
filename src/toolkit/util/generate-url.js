/**
 * @copyright Copyright (c) 2022, 2023, 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { appName } from '../../config.js';
import { generateUrl as nextcloudGenerateUrl } from '@nextcloud/router';

/**
 * Generate an absolute URL for this app.
 *
 * @param {string} url The locate URL without app-prefix.
 *
 * @param {object} urlParams Object holding url-parameters if url
 * contains parameters. "Excess" parameters will be appended as query
 * parameters to the URL.
 *
 * @param {object} [urlOptions] Object with query parameters
 * ```
 * {
 *   escape: BOOL,
 *   noRewrite: BOOL,
 * }
 * ```
 *
 * @return {string}
 */
const generateUrl = function(url, urlParams, urlOptions) {
  // const str = '/image/{joinTable}/{ownerId}';
  let generated = nextcloudGenerateUrl('/apps/' + appName + '/' + url, urlParams, urlOptions);
  const queryParams = { ...urlParams };
  for (const urlParam of url.matchAll(/{([^{}]*)}/g)) {
    delete queryParams[urlParam[1]];
  }
  const queryArray = [];
  for (const [key, value] of Object.entries(queryParams)) {
    try {
      queryArray.push(key + '=' + encodeURIComponent(value.toString()));
    } catch (e) {
      console.debug('STRING CONVERSION ERROR', e);
    }
  }
  if (queryArray.length > 0) {
    generated += '?' + queryArray.join('&');
  }
  return generated;
};

export default generateUrl;
