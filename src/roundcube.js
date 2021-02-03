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

import { appName, webPrefix, state } from './config.js';

const jQuery = require('jquery');
const $ = jQuery;

/**
 * This is only for larry/melanie2_larry_mobile skins.
 * For other skins we don't do anything.
 *
 * @param {jQuery} rcf RoundCubeFrame.
 */
const hideTopLine = function(rcf) {
  if (rcf[0].contentWindow.rcmail.env.skin.includes('classic')) {
    // just remove the logout button
    const rcfContents = rcf.contents();
    rcfContents.find('.button-logout').remove();
    return;
  }
  if (rcf[0].contentWindow.rcmail.env.skin.includes('elastic')) {
    // just remove the logout button
    const rcfContents = rcf.contents();
    rcfContents.find('.special-buttons .logout').remove();
    return;
  }
  if (!rcf[0].contentWindow.rcmail.env.skin.includes('larry')) {
    return;
  }
  const rcfContents = rcf.contents();
  // User shouldn't be able to logout from rc, but from outer app:
  // 1. #topline has a logout button which we don't want, so remove it and
  // adjust the top attribute of #mainscreen. Reduce height if no toolbar.
  // 2. Also remove button to show/hide the #topline and adjust the #taskbar.
  // 3. Remove other logout buttons.
  const toplineHeight = rcfContents.find('#topline').outerHeight();
  const mainscreenTop = parseInt(rcfContents.find('#mainscreen').css('top'));
  const toolbarHeight = 40;
  let newMainscreenTop = mainscreenTop - toplineHeight;
  rcfContents.find('#topline').remove(); // [1]
  if (rcfContents.find('#mainscreen .toolbar').length === 0) {
    newMainscreenTop -= toolbarHeight;
  }
  rcfContents.find('#mainscreen').css('top', newMainscreenTop); // [1]
  rcfContents.find('#taskbar .minmodetoggle').remove(); // [2]
  rcfContents.find('#taskbar').css('padding-right', 0); // [2]
  rcfContents.find('.button-logout').remove(); // [3]
};

/**
 * Fills height of window (more precise than height: 100%;)
 *
 * @param {jQuery} frame The frame to be  resized.
 */
const fillHeight = function(frame) {
  const height = $(window).height() - frame.offset().top;
  frame.css('height', height);
  if (frame.outerHeight() > frame.height()) {
    frame.css('height', height - (frame.outerHeight() - frame.height()));
  }
};

/**
 * Fills width of window (more precise than width: 100%;)
 *
 * @param {jQuery} frame The frame to be resized.
 */
const fillWidth = function(frame) {
  const width = $(window).width() - frame.offset().left;
  frame.css('width', width);
  if (frame.outerWidth() > frame.width()) {
    frame.css('width', width - (frame.outerWidth() - frame.width()));
  }
};

/**
 * Fills height and width of RC window.
 * More precise than height/width: 100%.
 *
 * @param {jQuery} frame TBD.
 */
const resizeIframe = function(frame) {
  if (frame.length === 0) {
    return;
  }
  fillHeight(frame);
  fillWidth(frame);
};

const loadHandler = function(frame) {

  if (!state.showTopline) {
    hideTopLine(frame);
  }
  resizeIframe(frame);

  // Fade in roundcube nice to let iframe load
  $('#' + webPrefix + 'LoaderContainer').fadeOut('slow');
};

export { loadHandler, resizeIframe as resizeHandler, };

// Local Variables: ***
// js-indent-level: 2 ***
// indent-tabs-mode: nil ***
// End: ***
