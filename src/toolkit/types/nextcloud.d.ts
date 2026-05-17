/**
 * @author Claus-Justus Heine
 * @copyright 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

// The core window.d.ts seems to be ignored (why?) so we duplicate the defs here
import type Tab from '../../../../files/src/models/Tab.js';
import type Settings from '../../../../files/src/services/Settings.js';
import type Sidebar from '../../../../files/src/services/Sidebar.js';

import '@nextcloud/typings';

type SidebarAPI = Sidebar & {
  open: (path: string) => Promise<void>;
  close: () => void;
  setFullScreenMode: (fullScreen: boolean) => void;
  setShowTagsDefault: (showTagsDefault: boolean) => void;
  Tab: typeof Tab;
};

declare global {
  const OC: Nextcloud.v31.OC;
  // Private Files namespace
  const OCA: {
    Files: {
      Settings: Settings;
      Sidebar: SidebarAPI;
    };
  } & Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any
  // const OCA: Window.OCA;
  // {
  //    Files: {
  //      // eslint-disable-next-line @typescript-eslint/no-explicit-any
  //      [key: string]: any;
  //    };
  //  };
  const OCP: Nextcloud.v31.OCP;
}

export {};
