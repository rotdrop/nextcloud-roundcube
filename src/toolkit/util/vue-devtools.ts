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

// Unsane mixture between type and instance. How to cleanup?
declare global {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  var __VUE_DEVTOOLS_GLOBAL_HOOK__: any;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  var __VUE__: any;
}

// Enabe dev-tools also needs unsafe-eval on script-src in the CSP.
export const enableVueDevTools = () => {
  if (window.__VUE_DEVTOOLS_GLOBAL_HOOK__) {
    window.__VUE_DEVTOOLS_GLOBAL_HOOK__.enabled = true;
    window.__VUE__ = Vue;
  }
};

export const disableVueDevTools = () => {
  if (window.__VUE_DEVTOOLS_GLOBAL_HOOK__?.enabled) {
    window.__VUE_DEVTOOLS_GLOBAL_HOOK__.enabled = false;
  }
}
