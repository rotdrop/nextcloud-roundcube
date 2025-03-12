/**
 * @copyright Copyright (c) 2022, 2023, 2024, 2025, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 *
 */

export function generateUrl<T extends string>(url: T, urlParams?: Record<string, string|number|boolean|null>, urlOptions?: Record<string, string>):string;
export function generateOcsUrl<T extends string>(url: T, urlParams?: Record<string, string|number|boolean|null>, urlOptions?: Record<stgring, string>):string;

export default generateUrl;
