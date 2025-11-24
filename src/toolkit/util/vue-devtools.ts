/**
 * @author Claus-Justus Heine
 * @copyright 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 */

import Vue from 'vue';

import Console from './console.ts';

const logger = new Console('VUE-DEVTOOLS');

// Unsane mixture between type and instance. How to cleanup?
declare global {
  // eslint-disable-next-line
  var __VUE_DEVTOOLS_GLOBAL_HOOK__: any;
  // eslint-disable-next-line
  var __VUE__: any;
}

// Enabe dev-tools also needs unsafe-eval on script-src in the CSP.
export const enableVueDevTools = () => {
  if (globalThis.__VUE_DEVTOOLS_GLOBAL_HOOK__) {
    logger.info('**************** ENABLING VUE DEV-TOOLS ******************', {
      globalThis,
    });
    globalThis.__VUE_DEVTOOLS_GLOBAL_HOOK__.enabled = true;
    globalThis.__VUE__ = Vue;
  } else {
    logger.error('VUE-DEVTOOLS DOES NOT SEEM TO BE AVAILABLE', {
      globalThis,
    });
  }
};

export const disableVueDevTools = () => {
  if (globalThis.__VUE_DEVTOOLS_GLOBAL_HOOK__?.enabled) {
    globalThis.__VUE_DEVTOOLS_GLOBAL_HOOK__.enabled = false;
  }
};

export const vueDevTools = ({ enabled }: { enabled: boolean } = { enabled: true }) =>
  enabled ? enableVueDevTools() : disableVueDevTools();
