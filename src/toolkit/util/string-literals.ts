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

export type JoinLiterals<T extends string[], S extends string> =
  T extends []
     ? ''
     : T extends [string]
       ? `${T[0]}`
       : T extends [string, ...infer U extends string[]]
         ? `${T[0]}${S}${JoinLiterals<U, S>}`
         : string;

function joinLiterals(): <T extends string[]>(...strings: T) => JoinLiterals<T, ''>;
function joinLiterals<Separator extends string>(separator: Separator): <T extends string[]>(...strings: T) => JoinLiterals<T, Separator>;

/**
 * Return a function which joins literals with the given separator.
 *
 * @param separator Separator.
 */
function joinLiterals(separator = '') {
  return function<T extends string[]>(...strings: T): JoinLiterals<T, typeof separator> {
    return strings.join(separator) as JoinLiterals<T, typeof separator>;
  };
}

export {
  joinLiterals,
};
