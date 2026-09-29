/**
 * @copyright Copyright (c) 2024, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
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

// import type { Event } from '@nextcloud/event-bus';

import type { IFolder, INode, IView } from '@nextcloud/files';
import type { components as NotificationComponents } from '../../../build/ts-types/notification-api.d.ts';

/**
 * Define the type used by the notifications app as Event. These are
 * just the properties for Notification.vue from the notifications
 * app.
 */
export type Notification = NotificationComponents['schemas']['Notification'];

export interface NotificationEvent /* extends Event */ {
  notification: Notification;
}

/** See NavigationManager */
export interface INavigationEntry {
  /** Navigation id */
  id: string
  /** If this is the currently active app */
  active: boolean
  /** Order where this entry should be shown */
  order: number
  /** Target of the navigation entry */
  href: string
  /** The icon used for the naviation entry */
  icon: string
  /** CSS color of the action indicator, only used by entries of type 'action' */
  color?: string
  /** Type of the navigation entry ('link' vs 'settings' vs 'action') */
  type: 'link' | 'settings' | 'action'
  /** Localized name of the navigation entry */
  name: string
  /** Whether this is the default app */
  default?: boolean
  /** App that registered this navigation entry (not necessarly the same as the id) */
  app?: string
  /** If this app has unread notification */
  unread: number
  /** True when the link should be opened in a new tab */
  target?: boolean
}

declare module '@nextcloud/event-bus' {
  interface NextcloudEvents {
    'core:navigation:action': INavigationEntry;
    'files:list:updated': {
      folder: IFolder;
      contents: INode[];
      view: IView;
    };
    'files:node:created': INode;
    'files:node:deleted': INode;
    'files:node:renamed': INode;
    'toggle-navigation': { open: boolean };
    'files:sidebar:opened': INode;
    'files:sidebar:closed': undefined;
    'notifications:notification:received': NotificationEvent;
  }
}

export {};
