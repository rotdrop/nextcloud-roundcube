/**
 * Orchestra member, musicion and project management application.
 *
 * CAFEVDB -- Camerata Academica Freiburg e.V. DataBase.
 *
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

import type { LegacyFileInfo } from '@nextcloud/files';

declare module '@nextcloud/files' {

  /**
   * Incomplete interface for OC.Files.FileInfo which is actually a
     constructor for this thing. See core/src/files/fileinfo.js
   */
  export interface LegacyFileInfo {
    id: number,
    mimetype: string,
    path: string,
    name: string,
    type: 'dir'|'file',
    permissions: number,
    mountType: string,
    isDirectory: () => boolean,
    canEdit: () => boolean,
    get: (key: string) => any,
    // ... and a couple of other properties
  }
}

export {}
