/**
 * Orchestra member, musicion and project management application.
 *
 * CAFEVDB -- Camerata Academica Freiburg e.V. DataBase.
 *
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

import { AxiosError, isAxiosError } from 'axios';
import type { AxiosResponse } from 'axios';

export { isAxiosError } from 'axios';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export interface AxiosErrorResponse<T = unknown, D = any> extends Omit<AxiosError<T, D>, 'response'> {
  response: AxiosResponse<T, D>,
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const isAxiosErrorResponse = <T = unknown, D = any>(error: unknown): error is AxiosErrorResponse<T, D> =>
  isAxiosError<T, D>(error) && !!error.response;

type Messages = { messages: string[] };

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export const isAxiosMessagesErrorResponse = <T extends Messages, D = any>(error: unknown): error is AxiosErrorResponse<T, D> =>
  isAxiosErrorResponse<T, D>(error) && ('messages' in error.response.data);
