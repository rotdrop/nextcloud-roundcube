/**
 * @copyright Copyright (c) 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 *
 */

import type { Event } from '@nextcloud/event-bus';

/**
 * Define the type used by the notifications app as Event. These are
 * just the properties for Notification.vue from the notifications
 * app.
 */
export interface Notification {
  notificationId: number;
  datetime: string;
  app: string;
  icon: string;
  link: string;
  externalLink: string;
  user: string;
  message: string;
  messageRich: string;
  messageRichParameters: any;
  subject: string;
  subjectRich: string;
  subjectRichParameters: any;
  objectType: string;
  objectId: string;
  shouldNotify: boolean;
  actions: Array<any>;
  index: number;
}

export interface NotificationEvent extends Event {
  notification: Notification;
}
