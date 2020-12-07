/**
 * nextCloud - RoundCube mail plugin
 *
 * @author Claus-Justus Heine
 * @copyright 2020 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
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
 *
 */

var RoundCube = RoundCube || {};
if (!RoundCube.appName) {
  const state = OCP.InitialState.loadState('roundcube', 'initial');
  RoundCube = $.extend({}, state);
  RoundCube.refreshTimer = false;
  console.debug("RoundCube", RoundCube);
}

RoundCube.Settings = RoundCube.Settings || {};

(function(window, $, RoundCube) {

  /**
   * Fetch data from an error response.
   *
   * @param xhr jqXHR, see fail() method of jQuery ajax.
   *
   * @param status from jQuery, see fail() method of jQuery ajax.
   *
   * @param errorThrown, see fail() method of jQuery ajax.
   */
  RoundCube.ajaxFailData = function(xhr, status, errorThrown) {
    const ct = xhr.getResponseHeader("content-type") || "";
    var data = {
      'error': errorThrown,
      'status': status,
      'message': t(RoundCube.appName, 'Unknown JSON error response to AJAX call: {status} / {error}')
    };
    if (ct.indexOf('html') > -1) {
      console.debug('html response', xhr, status, errorThrown);
      console.debug(xhr.status);
      data.message = t(RoundCube.appName, 'HTTP error response to AJAX call: {code} / {error}',
                       {'code': xhr.status, 'error': errorThrown});
    } else if (ct.indexOf('json') > -1) {
      const response = JSON.parse(xhr.responseText);
      //console.info('XHR response text', xhr.responseText);
      //console.log('JSON response', response);
      data = {...data, ...response };
    } else {
      console.log('unknown response');
    }
    //console.info(data);
    return data;
  };

  RoundCube.Settings.storeSettings = function(event, id) {
    const webPrefix = RoundCube.webPrefix;
    const msg = $('#'+webPrefix+'settings .msg');
    const msgStatus = $('#'+webPrefix+'settings .msg.status');
    const msgSuccess = $('#'+webPrefix+'settings .msg.success');
    const msgError = $('#'+webPrefix+'settings .msg.error');
    msg.hide();
    msgStatus.show();
    const input = $(id);
    var post = input.serialize();
    const cbSelector = 'input:checkbox:not(:checked)';
    input.find(cbSelector).addBack(cbSelector).each(function(index) {
      console.info('unchecked?', index, $(this));
      if (post !== '') {
        post += '&';
      }
      post += $(this).attr('name') + '=' + 'off';
    });
    console.info(post);
    $.post(OC.generateUrl('/apps/'+RoundCube.appName+'/settings/personal/set'), post)
      .done(function(data) {
        msgStatus.hide();
        console.info("Got response data", data);
	if (data.message) {
	  msgSuccess.html(data.message).show();
	}
      })
      .fail(function(xhr, status, errorThrown) {
        msgStatus.hide();
        const response = RoundCube.ajaxFailData(xhr, status, errorThrown);
        console.error(response);
        if (response.message) {
	  msgError.html(response.message).show();
        }
      });
    return false;
  };

})(window, jQuery, RoundCube);

$(function(){

  var id;

  id = '#emailAddress';
  $(id).on('blur', function(event) {
    return RoundCube.Settings.storeSettings(event, id);
  });

  id = '#emailPassword';
  const password = $(id);
  password.on('blur', function(event) {
    return RoundCube.Settings.storeSettings(event, id);
  });

  const tmp = password.val();
  var passwordShown;
  $(id).showPassword(function(args) {
    passwordShown = args.clone;
  });
  password.val(tmp);

  $(passwordShown).on('blur', function(event) {
    password.trigger('blur');
    return false;
  });

  id = '#roundcubesettings';
  $(id).off('submit').on('submit', function(event) {
    passwordShown.hide();
    password.show();
    $('#emailPasswordShow').prop('checked', false);
    return RoundCube.Settings.storeSettings(event, id);
  });

});
