<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Traits;

/**
 * Some convenience stuff for PHP enums.
 */
trait BackedEnumTrait
{
  /**
   * @return array<int, string> The names of the enum cases as flat array.
   */
  public static function names(): array
  {
    return array_column(self::cases(), 'name');
  }

  /**
   * @return array<int, string> The values of the enum cases as flat array.
   */
  public static function values(): array
  {
    return array_column(self::cases(), 'value');
  }

  /**
   * @return array<string, string|int> The cases of the enum as array
   * ```
   * [ NAME => VALUE, ... ]
   * ```
   */
  public static function array(): array
  {
    return array_combine(self::values(), self::names());
  }
}
