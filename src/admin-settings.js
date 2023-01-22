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
import { failData as ajaxFailData } from './toolkit/util/ajax.js';
import generateUrl from './toolkit/util/generate-url.js';

require('style/settings.scss');

const jQuery = require('jquery');
const $ = jQuery;

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
  $.post(generateUrl('settings/admin/set'), post)
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
  const formId = webPrefix + 'settings';
  const inputs = {
    externalLocation: 'blur',
    userIdEmail: 'change',
    emailDefaultDomain: 'blur',
    userPreferencesEmail: 'change',
    userChosenEmail: 'change',
    forceSSO: 'change',
    showTopLine: 'change',
    enableSSLVerify: 'change',
    personalEncryption: 'change',
  };
  inputs[formId] = 'submit';

  for (const input in inputs) {
    const id = '#' + input;
    const event = inputs[input];

    console.info(id, event);

    $(id).on(event, function(event) {
      event.preventDefault();
      storeSettings(event, id);
      return false;
    });
  }

  $('input[name="emailAddressChoice"]').on('change', function(event) {
    if ($('#userIdEmail').is(':checked')) {
      $('.emailDefaultDomain').removeClass('disabled').prop('disabled', false);
    } else {
      $('.emailDefaultDomain').addClass('disabled').prop('disabled', true);
    }
    return false;
  });
});
