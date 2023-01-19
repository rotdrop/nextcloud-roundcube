/**
 * @copyright Copyright (c) 2022 Claus-Justus Heine <himself@claus-justus-heine.de>
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
  200: t(appName, 'OK'),
  201: t(appName, 'Created'),
  202: t(appName, 'Accepted'),
  203: t(appName, 'Non-Authoritative Information'),
  204: t(appName, 'No Content'),
  205: t(appName, 'Reset Content'),
  206: t(appName, 'Partial Content'),
  207: t(appName, 'Multi-Status (WebDAV)'),
  208: t(appName, 'Already Reported (WebDAV)'),
  226: t(appName, 'IM Used'),
  300: t(appName, 'Multiple Choices'),
  301: t(appName, 'Moved Permanently'),
  302: t(appName, 'Found'),
  303: t(appName, 'See Other'),
  304: t(appName, 'Not Modified'),
  305: t(appName, 'Use Proxy'),
  306: t(appName, '(Unused)'),
  307: t(appName, 'Temporary Redirect'),
  308: t(appName, 'Permanent Redirect (experimental)'),
  400: t(appName, 'Bad Request'),
  401: t(appName, 'Unauthorized'),
  402: t(appName, 'Payment Required'),
  403: t(appName, 'Forbidden'),
  404: t(appName, 'Not Found'),
  405: t(appName, 'Method Not Allowed'),
  406: t(appName, 'Not Acceptable'),
  407: t(appName, 'Proxy Authentication Required'),
  408: t(appName, 'Request Timeout'),
  409: t(appName, 'Conflict'),
  410: t(appName, 'Gone'),
  411: t(appName, 'Length Required'),
  412: t(appName, 'Precondition Failed'),
  413: t(appName, 'Request Entity Too Large'),
  414: t(appName, 'Request-URI Too Long'),
  415: t(appName, 'Unsupported Media Type'),
  416: t(appName, 'Requested Range Not Satisfiable'),
  417: t(appName, 'Expectation Failed'),
  418: t(appName, 'I\'m a teapot (RFC 2324)'),
  420: t(appName, 'Enhance Your Calm (Twitter)'),
  422: t(appName, 'Unprocessable Entity (WebDAV)'),
  423: t(appName, 'Locked (WebDAV)'),
  424: t(appName, 'Failed Dependency (WebDAV)'),
  425: t(appName, 'Reserved for WebDAV'),
  426: t(appName, 'Upgrade Required'),
  428: t(appName, 'Precondition Required'),
  429: t(appName, 'Too Many Requests'),
  431: t(appName, 'Request Header Fields Too Large'),
  444: t(appName, 'No Response (Nginx)'),
  449: t(appName, 'Retry With (Microsoft)'),
  450: t(appName, 'Blocked by Windows Parental Controls (Microsoft)'),
  451: t(appName, 'Unavailable For Legal Reasons'),
  499: t(appName, 'Client Closed Request (Nginx)'),
  500: t(appName, 'Internal Server Error'),
  501: t(appName, 'Not Implemented'),
  502: t(appName, 'Bad Gateway'),
  503: t(appName, 'Service Unavailable'),
  504: t(appName, 'Gateway Timeout'),
  505: t(appName, 'HTTP Version Not Supported'),
  506: t(appName, 'Variant Also Negotiates (Experimental)'),
  507: t(appName, 'Insufficient Storage (WebDAV)'),
  508: t(appName, 'Loop Detected (WebDAV)'),
  509: t(appName, 'Bandwidth Limit Exceeded (Apache)'),
  510: t(appName, 'Not Extended'),
  511: t(appName, 'Network Authentication Required'),
  598: t(appName, 'Network read timeout error'),
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

// Local Variables: ***
// js-indent-level: 2 ***
// End: ***
