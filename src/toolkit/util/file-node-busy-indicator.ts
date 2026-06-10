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

import type { INode } from '@nextcloud/files';

import { emit } from '@nextcloud/event-bus';
import { NodeStatus } from '@nextcloud/files';

const busyNodes: INode[] = [];

export const setFileNodeBusy = (node?: INode, state: boolean = true) => {
  if (node && state) {
    node.status = NodeStatus.LOADING;
    emit('files:node:updated', node);
    busyNodes.push(node);
  } else {
    for (const node of busyNodes) {
      node.status = undefined;
      emit('files:node:updated', node);
    }
    busyNodes.splice(0, busyNodes.length);
  }
};

export const clearFileNodeBusy = () => setFileNodeBusy(undefined, false);
