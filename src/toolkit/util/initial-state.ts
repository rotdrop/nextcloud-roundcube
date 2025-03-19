/**
 * @copyright Copyright (c) 2022, 2023, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 *
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

import { appName } from '../../config.ts';

import { loadState } from '@nextcloud/initial-state';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface GetInitialStateArgs<D = Record<string, any> > {
  section: string,
  defaults?: D,
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const getInitialState = <D = Record<string, any> >({ section, defaults }: GetInitialStateArgs<D> = { section: 'config' }) => {
  try {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const result = loadState(appName, section) as D;
    return result;
  } catch (err) {
    if (defaults) {
      return defaults;
    }
    console.error('error in loadState("' + section + '"): ', err);
    return null;
  }
};

export default getInitialState;
