/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2022, 2023, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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
import { loadHandler, resizeHandler } from './roundcube.js';
import '../style/base.css';

const jQuery = require('jquery');
const $ = jQuery;

const rcFrameId = '#' + webPrefix + 'Frame';

let gotLoadEvent = false;
const loadTimeout = 100;
let timerCount = 0;

const loadTimerHandler = function($frame) {

  if (gotLoadEvent) {
    return;
  }

  ++timerCount;
  const rcfContents = $frame.contents();

  if (rcfContents.find('#layout').length > 0) {
    console.info('LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms');
    $frame.trigger('load', 'synthesized');
  } else {
    setTimeout(() => loadTimerHandler($frame), loadTimeout);
  }
};

const loadHandlerWrapper = function($frame, event, origin) {
  gotLoadEvent = true;
  loadHandler($frame);
};

$(function() {
  const $frame = $(rcFrameId);

  $frame.on('load', (event, origin) => loadHandlerWrapper($frame, event, origin));

  if ($frame.length > 0) {

    $(window).resize(function() {
      resizeHandler($frame);
    });

    setTimeout(() => loadTimerHandler($frame), loadTimeout);
  } else {
    console.info('ROUNDCUBE IFRAME NOT FOUND');
  }
});
