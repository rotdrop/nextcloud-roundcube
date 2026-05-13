/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025, 2026 Claus-Justus Heine
 * @license AGPL-3.0-or-later
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
import { appName } from '../config.ts';
import { createWebHistory, createRouter } from 'vue-router';
import type { RouterOptions } from 'vue-router';
import { generateUrl } from '@nextcloud/router';

const base = generateUrl('/apps/' + appName);

const options: RouterOptions = {
  history: createWebHistory(base),
  linkActiveClass: 'active',
  routes: [
    {
      path: '/',
      component: () => import('../RoundCubeWrapperRouteReactivity.vue'),
      name: 'home',
    },
  ],
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) {
      return { behavior: 'smooth', ...savedPosition };
    } else if (to.hash) {
      return {
        selector: to.hash,
        behavior: 'smooth',
      };
    }
  },
};

const router = createRouter(options);

export default router;
