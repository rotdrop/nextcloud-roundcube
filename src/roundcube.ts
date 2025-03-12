/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021, 2022, 2023, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

import { getInitialState } from './toolkit/services/InitialStateService.js';

const initialState = getInitialState();

type RoundCubeWindow = Window & {
  rcmail?: {
    env: {
      skin: string,
    },
  },
}

/**
 * @param {object} rcf RoundCubeFrame.
 */
const hideTopLine = function(rcf: HTMLIFrameElement) {
  const frameWindow: RoundCubeWindow = rcf.contentWindow!;
  const frameDocument = frameWindow.document!;

  const skin = frameWindow.rcmail!.env.skin;
  if (skin.includes('classic')) {
    // just remove the logout button
    frameDocument.querySelectorAll('.button-logout').forEach(el => el.remove());
  } else if (skin.includes('elastic')) {
    // just remove the logout button
    frameDocument.querySelectorAll(':scope .special-buttons .logout').forEach(el => el.remove());
  } else if (skin.includes('larry')) {
    // User shouldn't be able to logout from rc, but from outer app:
    // 1. #topline has a logout button which we don't want, so remove it and
    // adjust the top attribute of #mainscreen. Reduce height if no toolbar.
    // 2. Also remove button to show/hide the #topline and adjust the #taskbar.
    // 3. Remove other logout buttons.
    const mainScreenElement: HTMLElement = frameDocument.querySelector('#mainscreen')!;
    const toplineHeight = frameDocument.querySelector('#topline')!.getBoundingClientRect().height;
    const mainscreenTop = parseInt(mainScreenElement.style.top);
    const toolbarHeight = 40;
    let newMainscreenTop = mainscreenTop - toplineHeight;
    frameDocument.querySelector('#topline')!.remove(); // [1]
    if (!mainScreenElement.querySelector('.toolbar')) {
      newMainscreenTop -= toolbarHeight;
    }
    mainScreenElement.style.top = newMainscreenTop + 'px'; // [1]
    frameDocument.querySelector(':scope #taskbar .minmodetoggle')!.remove(); // [2]
    (frameDocument.querySelector('#taskbar')! as HTMLElement).style['padding-right'] = 0; // [2]
    frameDocument.querySelector('.button-logout')!.remove(); // [3]
  }
};

/**
 * Fills height of window (more precise than height: 100%;)
 *
 * @param frame The frame to be  resized.
 */
const fillHeight = function(frame: HTMLIFrameElement) {
  const height = window.innerHeight - frame.getBoundingClientRect().top;
  frame.style.height = height + 'px';
  const outerDelta = frame.getBoundingClientRect().height - frame.clientHeight;
  if (outerDelta) {
    frame.style.height = (height - outerDelta) + 'px';
  }
};

/**
 * Fills width of window (more precise than width: 100%;)
 *
 * @param frame The frame to be resized.
 */
const fillWidth = function(frame: HTMLIFrameElement) {
  const width = window.innerWidth - frame.getBoundingClientRect().left;
  frame.style.width = width + 'px';
  const outerDelta = frame.getBoundingClientRect().width - frame.clientWidth;
  if (outerDelta > 0) {
    frame.style.width = (width - outerDelta) + 'px';
  }
};

/**
 * Fills height and width of RC window.
 * More precise than height/width: 100%.
 *
 * @param frame TBD.
 */
const resizeIframe = function(frame: HTMLIFrameElement) {
  if (!frame) {
    return;
  }
  fillHeight(frame);
  fillWidth(frame);
};

/**
 * @param frame TBD.
 */
const loadHandler = function(frame: HTMLIFrameElement) {
  if (!initialState.showTopline) {
    hideTopLine(frame);
  }
  resizeIframe(frame);
};

export { loadHandler, resizeIframe as resizeHandler };
