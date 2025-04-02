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

import { v4 as uuidv4 } from 'uuid'

const idRegExp = /id="([^"]+)"/g;
const urlRegExp = /url\(#([^)]+)\)/g;

/**
 * Sanitize the given data which is supposedly a string represention
 * markup for an SVG element.
 *
 * - Make the ids globally unique in order to allow inlining the same
 *   svg multiple times on the same page.
 *
 * @param data
 */
const sanitize = (data: string) => {
  const uuid = uuidv4();

  const sanitizedData = data
    .replace(idRegExp, 'id="$1-' + uuid + '"')
    .replace(urlRegExp, 'url(#$1-' + uuid + ')');

  return sanitizedData;
}

export default sanitize;
