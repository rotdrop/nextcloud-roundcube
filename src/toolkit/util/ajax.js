/**
 * @copyright Copyright (c) 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
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

import $ from './jquery.js';
import { appName } from '../../config.js';
// eslint-disable-next-line camelcase
import print_r from './print-r.js';
import * as Dialogs from './dialogs.js';
import { isPlainObject } from 'is-plain-object';
import { getRootUrl as getCloudRootUrl } from '@nextcloud/router';
import { getInitialState } from '../services/InitialStateService.js';

const cloudWebRoot = getCloudRootUrl() || '/';

const globalState = getInitialState();

const ajaxHttpStatus = {
  // TRANSLATORS: Textual description of HTTP status code 200, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  200: t(appName, 'OK'),
  // TRANSLATORS: Textual description of HTTP status code 201, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  201: t(appName, 'Created'),
  // TRANSLATORS: Textual description of HTTP status code 202, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  202: t(appName, 'Accepted'),
  // TRANSLATORS: Textual description of HTTP status code 203, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  203: t(appName, 'Non-Authoritative Information'),
  // TRANSLATORS: Textual description of HTTP status code 204, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  204: t(appName, 'No Content'),
  // TRANSLATORS: Textual description of HTTP status code 205, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  205: t(appName, 'Reset Content'),
  // TRANSLATORS: Textual description of HTTP status code 206, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  206: t(appName, 'Partial Content'),
  // TRANSLATORS: Textual description of HTTP status code 207, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  207: t(appName, 'Multi-Status (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 208, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  208: t(appName, 'Already Reported (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 226, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  226: t(appName, 'IM Used'),
  // TRANSLATORS: Textual description of HTTP status code 300, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  300: t(appName, 'Multiple Choices'),
  // TRANSLATORS: Textual description of HTTP status code 301, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  301: t(appName, 'Moved Permanently'),
  // TRANSLATORS: Textual description of HTTP status code 302, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  302: t(appName, 'Found'),
  // TRANSLATORS: Textual description of HTTP status code 303, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  303: t(appName, 'See Other'),
  // TRANSLATORS: Textual description of HTTP status code 304, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  304: t(appName, 'Not Modified'),
  // TRANSLATORS: Textual description of HTTP status code 305, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  305: t(appName, 'Use Proxy'),
  // TRANSLATORS: Textual description of HTTP status code 306, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  306: t(appName, '(Unused)'),
  // TRANSLATORS: Textual description of HTTP status code 307, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  307: t(appName, 'Temporary Redirect'),
  // TRANSLATORS: Textual description of HTTP status code 308, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  308: t(appName, 'Permanent Redirect (experimental)'),
  // TRANSLATORS: Textual description of HTTP status code 400, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  400: t(appName, 'Bad Request'),
  // TRANSLATORS: Textual description of HTTP status code 401, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  401: t(appName, 'Unauthorized'),
  // TRANSLATORS: Textual description of HTTP status code 402, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  402: t(appName, 'Payment Required'),
  // TRANSLATORS: Textual description of HTTP status code 403, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  403: t(appName, 'Forbidden'),
  // TRANSLATORS: Textual description of HTTP status code 404, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  404: t(appName, 'Not Found'),
  // TRANSLATORS: Textual description of HTTP status code 405, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  405: t(appName, 'Method Not Allowed'),
  // TRANSLATORS: Textual description of HTTP status code 406, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  406: t(appName, 'Not Acceptable'),
  // TRANSLATORS: Textual description of HTTP status code 407, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  407: t(appName, 'Proxy Authentication Required'),
  // TRANSLATORS: Textual description of HTTP status code 408, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  408: t(appName, 'Request Timeout'),
  // TRANSLATORS: Textual description of HTTP status code 409, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  409: t(appName, 'Conflict'),
  // TRANSLATORS: Textual description of HTTP status code 410, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  410: t(appName, 'Gone'),
  // TRANSLATORS: Textual description of HTTP status code 411, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  411: t(appName, 'Length Required'),
  // TRANSLATORS: Textual description of HTTP status code 412, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  412: t(appName, 'Precondition Failed'),
  // TRANSLATORS: Textual description of HTTP status code 413, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  413: t(appName, 'Request Entity Too Large'),
  // TRANSLATORS: Textual description of HTTP status code 414, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  414: t(appName, 'Request-URI Too Long'),
  // TRANSLATORS: Textual description of HTTP status code 415, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  415: t(appName, 'Unsupported Media Type'),
  // TRANSLATORS: Textual description of HTTP status code 416, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  416: t(appName, 'Requested Range Not Satisfiable'),
  // TRANSLATORS: Textual description of HTTP status code 417, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  417: t(appName, 'Expectation Failed'),
  // TRANSLATORS: Textual description of HTTP status code 418, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  418: t(appName, 'I\'m a teapot (RFC 2324)'),
  // TRANSLATORS: Textual description of HTTP status code 420, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  420: t(appName, 'Enhance Your Calm (Twitter)'),
  // TRANSLATORS: Textual description of HTTP status code 422, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  422: t(appName, 'Unprocessable Entity (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 423, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  423: t(appName, 'Locked (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 424, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  424: t(appName, 'Failed Dependency (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 425, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  425: t(appName, 'Reserved for WebDAV'),
  // TRANSLATORS: Textual description of HTTP status code 426, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  426: t(appName, 'Upgrade Required'),
  // TRANSLATORS: Textual description of HTTP status code 428, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  428: t(appName, 'Precondition Required'),
  // TRANSLATORS: Textual description of HTTP status code 429, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  429: t(appName, 'Too Many Requests'),
  // TRANSLATORS: Textual description of HTTP status code 431, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  431: t(appName, 'Request Header Fields Too Large'),
  // TRANSLATORS: Textual description of HTTP status code 444, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  444: t(appName, 'No Response (Nginx)'),
  // TRANSLATORS: Textual description of HTTP status code 449, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  449: t(appName, 'Retry With (Microsoft)'),
  // TRANSLATORS: Textual description of HTTP status code 450, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  450: t(appName, 'Blocked by Windows Parental Controls (Microsoft)'),
  // TRANSLATORS: Textual description of HTTP status code 451, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  451: t(appName, 'Unavailable For Legal Reasons'),
  // TRANSLATORS: Textual description of HTTP status code 599, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  499: t(appName, 'Client Closed Request (Nginx)'),
  // TRANSLATORS: Textual description of HTTP status code 500, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  500: t(appName, 'Internal Server Error'),
  // TRANSLATORS: Textual description of HTTP status code 501, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  501: t(appName, 'Not Implemented'),
  // TRANSLATORS: Textual description of HTTP status code 502, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  502: t(appName, 'Bad Gateway'),
  // TRANSLATORS: Textual description of HTTP status code 503, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  503: t(appName, 'Service Unavailable'),
  // TRANSLATORS: Textual description of HTTP status code 504, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  504: t(appName, 'Gateway Timeout'),
  // TRANSLATORS: Textual description of HTTP status code 505, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  505: t(appName, 'HTTP Version Not Supported'),
  // TRANSLATORS: Textual description of HTTP status code 506, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  506: t(appName, 'Variant Also Negotiates (Experimental)'),
  // TRANSLATORS: Textual description of HTTP status code 507, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  507: t(appName, 'Insufficient Storage (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 508, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  508: t(appName, 'Loop Detected (WebDAV)'),
  // TRANSLATORS: Textual description of HTTP status code 509, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  509: t(appName, 'Bandwidth Limit Exceeded (Apache)'),
  // TRANSLATORS: Textual description of HTTP status code 510, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  510: t(appName, 'Not Extended'),
  // TRANSLATORS: Textual description of HTTP status code 511, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  511: t(appName, 'Network Authentication Required'),
  // TRANSLATORS: Textual description of HTTP status code 598, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  598: t(appName, 'Network read timeout error'),
  // TRANSLATORS: Textual description of HTTP status code 599, see e.g. https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
  599: t(appName, 'Network connect timeout error'),

  // Seemingly Nextcloud always ever only returns one of these:
  OK: 200,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  NOT_FOUND: 404,
  CONFLICT: 409,
  PRECONDITION_FAILED: 412,
  INTERNAL_SERVER_ERROR: 500,
};

/**
 * Generate some diagnostic output, mostly needed during application
 * development. This is intended to be called from the fail()
 * callback.
 *
 * @param {object} xhr TBD.
 *
 * @param {string} textStatus TBD.
 *
 * @param {number} errorThrown TBD.
 *
 * @param {object} callbacks An object with hook-functions:
 *
 * ```
 * {
 *   cleanup: function(data) { ... },
 *   preProcess: function(data) { ... }
 * ```
 *
 * The callbacks as well as the callback object itself is optional and
 * defaults to "do nothing". The argument is the data possibly
 * submitted by the server, as computed by ajaFailData().
 *
 * @return {object} TBD.
 */
const ajaxHandleError = function(xhr, textStatus, errorThrown, callbacks) {

  const defaultCallbacks = {
    cleanup(data) {},
    preProcess(data) {},
  };
  callbacks = callbacks || {};
  if (callbacks instanceof Function) {
    callbacks = {
      cleanup: callbacks,
    };
  }
  callbacks = $.extend({}, defaultCallbacks, callbacks);

  const failData = ajaxFailData(xhr, textStatus, errorThrown);
  callbacks.preProcess(failData);

  let decodedStatus;
  switch (textStatus) {
  case 'cancelled':
    decodedStatus = t(appName, 'Operation cancelled by user.');
    break;
  case 'abort':
    decodedStatus = t(appName, 'Aborted');
    break;
  case 'notmodified':
  case 'nocontent':
  case 'error':
  case 'timeout':
  case 'parsererror':
  case 'success': // this should not happen here
  default:
    decodedStatus = ajaxHttpStatus[xhr.status];
    break;
  }

  const caption = t(appName, 'Error');
  let info = '<span class="bold toastify http-status error">' + decodedStatus + '</span>';
  // console.info(xhr.status, info, errorThrown, textStatus);

  let autoReport = '<a href="mailto:'
      + encodeURIComponent(globalState.adminContact)
      + '?subject=' + '[Error] Error Feedback'
      + '&body='
      + encodeURIComponent(
        'JavaScript User Agent:'
          + '\n'
          + navigator.userAgent
          + '\n'
          + '\n'
          + 'PHP User Agent:'
          + '\n'
          + (globalState.phpUserAgent || 'unknown')
          + '\n'
          + '\n'
          + 'Error Code: ' + decodedStatus
          + '\n'
          + '\n'
          + 'Error Data: ' + print_r(failData, true)
          + '\n')
      + '">'
      + t(appName, 'System Administrator')
      + '</a>';

  switch (xhr.status) {
  case ajaxHttpStatus.OK:
  case ajaxHttpStatus.BAD_REQUEST:
  case ajaxHttpStatus.NOT_FOUND:
  case ajaxHttpStatus.CONFLICT:
  case ajaxHttpStatus.PRECONDITION_FAILED:
  case ajaxHttpStatus.INTERNAL_SERVER_ERROR: {
    if (failData.error && ajaxHttpStatus[xhr.status] !== t(appName, failData.error)) {
      info += ': '
        + '<span class="bold error toastify name">'
        + t(appName, failData.error)
        + '</span>';
    }
    const messages = [];
    if (failData.message) {
      if (!Array.isArray(failData.message)) {
        messages.push(failData.message);
      } else {
        messages.splice(messages.length, 0, ...failData.message);
      }
    }
    if (failData.messages) {
      messages.splice(messages.length, 0, ...failData.messages);
    }
    if (failData.errorMessages) {
      messages.splice(messages.length, 0, ...failData.errorMessages);
    }

    for (const msg of messages) {
      info += '<div class="' + appName + ' error toastify">' + msg + '</div>';
    }
    info += '<div class="error toastify feedback-link">'
      + t(appName, 'Feedback email: {AutoReport}', { AutoReport: autoReport }, -1, { escape: false })
      + '</div>';
    autoReport = '';
    let exceptionData = failData;
    if (exceptionData.exception !== undefined) {
      info += '<div class="exception error name"><pre>' + exceptionData.exception + '</pre></div>'
        + '<div class="exception error trace"><pre>' + exceptionData.trace + '</pre></div>';
      while ((exceptionData = exceptionData.previous) != null) {
        info += '<div class="bold error toastify">' + exceptionData.message + '</div>';
        info += '<div class="exception error name"><pre>' + exceptionData.exception + '</pre></div>'
          + '<div class="exception error trace"><pre>' + exceptionData.trace + '</pre></div>';
      }
    }
    if (failData.info) {
      info += '<div class="' + appName + ' error-page">' + failData.info + '</div>';
    }
    break;
  }
  case ajaxHttpStatus.UNAUTHORIZED: {
    // no point in continuing, direct the user to the login page
    callbacks.cleanup = function() {
      window.location.replace(cloudWebRoot);
    };

    let generalHint = t(appName, 'Something went wrong.');
    generalHint += '<br/>'
      + t(appName, 'If it should be the case that you are already '
          + 'logged in for a long time without interacting '
          + 'with the app, then the reason for this '
          + 'error is probably a simple timeout.');
    generalHint += '<br/>'
      + t(appName, 'In any case it may help to logoff and logon again, as a '
          + 'temporary workaround. You will be redirected to the '
          + 'login page when you close this window.');
    info += '<div class="error general">' + generalHint + '</div>';
    // info += '<div class="error toastify feedback-link">'
    //       + t(appName, 'Feedback email: {AutoReport}', { AutoReport: autoReport }, -1, { escape: false })
    //       + '</div>';
    break;
  }
  }

  // console.info(info);
  Dialogs.attachDialogHandlers();

  Dialogs.alert(info, caption, function() { callbacks.cleanup(failData); }, true, true);
  return failData;
};

/**
 * Generate some diagnostic output, mostly needed during
 * application development. This is intended to be called from the
 * done() callback after a successful AJAX call.
 *
 * @param {object} data The data passed to the callback to $.post()
 *
 * @param {Array} required List of required fields in data.data.
 *
 * @param {Function} errorCB TBD.
 *
 * @return {boolean} TBD.
 */
const ajaxValidateResponse = function(data, required, errorCB) {
  if (data.data && data.data.status !== undefined) {
    console.error('********** Success handler called as error handler ************');
    if (data.data.status !== 'success') {
      ajaxHandleError(null, data, null);
      return false;
    } else {
      data = data.data;
    }
  }
  if (typeof errorCB === 'undefined') {
    errorCB = function(data) {};
  }
  const dialogCallback = function() {
    errorCB(data);
  };
  // error handling
  if (typeof data === 'undefined' || !data) {
    Dialogs.alert(
      t(appName, 'Unrecoverable unknown internal error, no details available'),
      t(appName, 'Internal Error'), dialogCallback, true);
    return false;
  }
  let missing = '';
  for (let idx = 0; idx < required.length; ++idx) {
    if (typeof data[required[idx]] === 'undefined') {
      missing += t(
        appName, 'Field {RequiredField} not present in AJAX response.',
        { RequiredField: required[idx] }) + '<br>';
    }
  }
  if (missing.length > 0) {
    let info = '';
    if (typeof data.message !== 'undefined') {
      info += data.message + ' ';
    }
    info += t(appName, 'Missing data');
    // Add missing fields only if no exception or setup-error was
    // caught as in this case no regular data-fields have been
    // constructed
    info += '<div class="missing error">' + missing + '</div>';

    if (!isPlainObject(data)) {
      let caption = 'Error';
      switch (typeof data) {
      case 'string':
        info += t(
          appName,
          'The submitted data "{stringValue}" seems to be a plain string, '
            + 'but we need an object where the data is provided through above listed properties.',
          { stringValue: data.substring(0, 32) + '...' });
        caption = t(appName, 'Error: plain string received');
        break;
      default:
        info += t(
          appName,
          'The submitted data is not a plain object, '
            + 'and does not provide the properties listed above.',
          { stringValue: data.substring(0, 32) + '...' });
        caption = t(appName, 'Error: not a plain object');
        break;
      }
      data = { caption, data };
    }

    // Display additional debug info if any
    Dialogs.debugPopup(data);

    let caption = data.caption;
    if (typeof caption === 'undefined' || caption === '') {
      caption = t(appName, 'Error');
      data.caption = caption;
    }
    Dialogs.alert(info, caption, dialogCallback, true, true);
    return false;
  }
  return true;
};

/**
 * Fetch data from an error response.
 *
 * @param {object} xhr jqXHR, see fail() method of jQuery ajax.
 *
 * @param {string} textStatus from jQuery, see fail() method of jQuery ajax.
 *
 * @param {string} errorThrown see fail() method of jQuery ajax.
 *
 * @return {object} TBD.
 */
const ajaxFailData = function(xhr, textStatus, errorThrown) {
  if (xhr.parsed !== undefined && xhr.error !== undefined && xhr.status !== undefined && xhr.message !== undefined) {
    return xhr;
  }
  const ct = xhr.getResponseHeader('content-type') || '';
  let data = {
    error: errorThrown,
    status: textStatus,
    messages: [
      t(appName, 'Unknown JSON error response to AJAX call: {status} / {error}', { status: textStatus, error: errorThrown }),
    ],
    parsed: false,
  };
  if (ct.indexOf('html') > -1) {
    console.debug('html response', xhr, textStatus, errorThrown);
    console.debug(xhr.status);
    data.messages = [
      t(appName, 'HTTP error response to AJAX call: {code} / {error}',
        { code: xhr.status, error: errorThrown }),
    ];
    data.info = $(xhr.responseText).find('main').html();
    data.parsed = true;
  } else if (ct.indexOf('json') > -1) {
    const response = JSON.parse(xhr.responseText);
    // console.info('XHR response text', xhr.responseText);
    // console.log('JSON response', response);
    data = { ...data, ...response };
    data.parsed = true;
  } else {
    console.log('unknown response');
  }
  // console.info(data);
  return data;
};

/**
 * Generate some diagnostic output, mostly needed during application
 * development.
 *
 * @param {object} xhr jqXHR, see fail() method of jQuery ajax.
 *
 * @param {string} textStatus from jQuery, see fail() method of jQuery ajax.
 *
 * @param {string} errorThrown see fail() method of jQuery ajax.
 *
 * @return {object} TBD.
 */
const ajaxFailMessage = function(xhr, textStatus, errorThrown) {
  return ajaxFailData(xhr, textStatus, errorThrown).message;
};

$(function() {
  Dialogs.attachDialogHandlers();
});

export {
  ajaxHttpStatus as httpStatus,
  ajaxHandleError as handleError,
  ajaxValidateResponse as validateResponse,
  ajaxFailData as failData,
  ajaxFailMessage as failMessage,
};
