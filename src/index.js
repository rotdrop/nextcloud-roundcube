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

import { webPrefix } from './config.js';
import { loadHandler, resizeHandler, } from './roundcube.js';
import '../style/base.css';

const jQuery = require('jquery');
const $ = jQuery;

const test = function() {
  const $frame = $('#' + webPrefix + 'Frame');
  if ($frame.length > 0) {
    const rcfContents = $frame.contents();
    if (rcfContents.find('#mainscreen').length > 0) {
      console.info('ROUNDCUBE LOADED????');
      trigger($frame, 'load');
    } else {
      console.info('NO CONTENT');
    }
  } else {
    console.info('NO FRAME');
  }
};

$(function() {
  const $frame = $('#' + webPrefix + 'Frame');
  let gotLoadEvent = false;

  if ($frame.length > 0) {
    $frame.on('load', function() {
      if (!gotLoadEvent) {
        gotLoadEvent = true
        loadHandler($frame);
      } else {
        console.warn('Duplicate load event');
      }
    });

    $(window).resize(function() {
      resizeHandler($frame);
    });

    const loadTimeout = 100;
    let timerCount = 0;
    const loadTimerHandler = function() {
      if (gotLoadEvent) {
        return;
      }
      ++timerCount;
      const rcfContents = $frame.contents();
      if (rcfContents.find('#mainscreen').length > 0) {
        console.warn('LOAD EVENT FROM TIMER AFTER ' + (loadTimeout * timerCount) + ' ms');
        $frame.trigger('load');
      } else {
        setTimeout(loadTimerHandler, loadTimeout);
      }
    }
    setTimeout(loadTimerHandler, loadTimeout);
  }

});

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
