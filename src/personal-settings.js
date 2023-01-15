/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { webPrefix } from './config.js';
import ajaxFailData from './ajax.js';
import generateUrl from './generate-url.js';
import '../style/settings.css';

const jQuery = require('jquery');
const $ = jQuery;
require('./nextcloud/jquery/showpassword.js');

const storeSettings = function(event, id) {
  const msg = $('#' + webPrefix + 'settings .msg');
  const msgStatus = $('#' + webPrefix + 'settings .msg.status');
  const msgSuccess = $('#' + webPrefix + 'settings .msg.success');
  const msgError = $('#' + webPrefix + 'settings .msg.error');
  msg.hide();
  msgStatus.show();
  const input = $(id);
  let post = input.serialize();
  const cbSelector = 'input:checkbox:not(:checked)';
  input.find(cbSelector).addBack(cbSelector).each(function(index) {
    console.info('unchecked?', index, $(this));
    if (post !== '') {
      post += '&';
    }
    post += $(this).attr('name') + '=' + 'off';
  });
  console.info(post);
  $.post(generateUrl('settings/personal/set'), post)
    .done(function(data) {
      msgStatus.hide();
      console.info('Got response data', data);
      if (data.message) {
        msgSuccess.html(data.message).show();
      }
    })
    .fail(function(xhr, status, errorThrown) {
      msgStatus.hide();
      const response = ajaxFailData(xhr, status, errorThrown);
      console.error(response);
      if (response.message) {
        msgError.html(response.message).show();
      }
    });
  return false;
};

$(function() {

  let id;

  id = '#emailAddress';
  $(id).on('blur', function(event) {
    return storeSettings(event, id);
  });

  id = '#emailPassword';
  const password = $(id);
  password.on('blur', function(event) {
    return storeSettings(event, id);
  });

  const tmp = password.val();
  let passwordShown;
  $(id).showPassword(function(args) {
    passwordShown = args.clone;
  });
  password.val(tmp);

  $(passwordShown).on('blur', function(event) {
    password.trigger('blur');
    return false;
  });

  id = '#' + webPrefix + 'settings';
  $(id).off('submit').on('submit', function(event) {
    passwordShown.hide();
    password.show();
    $('#emailPasswordShow').prop('checked', false);
    return storeSettings(event, id);
  });

});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
