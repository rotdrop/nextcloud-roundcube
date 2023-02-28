<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use SimpleXMLElement;

/** Get the possible enumeration values as array for backed enums. */
trait EnumValuesTrait
{
  /**
   * @return array The enum case values indexed by their case names.
   */
  public static function toArray():array
  {
    $result = [];
    foreach (self::cases() as $case) {
      $result[$case->name] = $case->value;
    }
    return $result;
  }

  /**
   * @return array The enum case values as flat consecutive array.
   */
  public static function values():array
  {
    return array_map(fn($case) => $case->value, self::cases());
  }

  /**
   * @return array The enum case names as flat consecutive array.
   */
  public static function keys():array
  {
    return array_map(fn($case) => $case->name, self::cases());
  }
}
