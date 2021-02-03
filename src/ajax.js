/**
 * Embed a DokuWiki instance as app into ownCloud, intentionally with
 * single-sign-on.
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

import { appName } from './config.js';

/**
 * Fetch data from an error response.
 *
 * @param {Object} xhr jqXHR, see fail() method of jQuery ajax.
 *
 * @param {Object} status from jQuery, see fail() method of jQuery ajax.
 *
 * @param {Object} errorThrown, see fail() method of jQuery ajax.
 *
 * @returns {Array}
 */
const ajaxFailData = function(xhr, status, errorThrown) {
  const ct = xhr.getResponseHeader('content-type') || '';
  let data = {
    error: errorThrown,
    status,
    message: t(appName, 'Unknown JSON error response to AJAX call: {status} / {error}'),
  };
  if (ct.indexOf('html') > -1) {
    console.debug('html response', xhr, status, errorThrown);
    console.debug(xhr.status);
    data.message = t(appName, 'HTTP error response to AJAX call: {code} / {error}', {
      code: xhr.status, error: errorThrown,
    });
  } else if (ct.indexOf('json') > -1) {
    const response = JSON.parse(xhr.responseText);
    // console.info('XHR response text', xhr.responseText);
    // console.log('JSON response', response);
    data = {...data, ...response };
  } else {
    console.log('unknown response');
  }
  // console.info(data);
  return data;
};

export default ajaxFailData;

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
