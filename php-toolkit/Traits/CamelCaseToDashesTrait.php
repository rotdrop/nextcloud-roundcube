<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 * Helper trait for converting between camelcase and "dashed" variants.
 */
trait CamelCaseToDashesTrait
{
  /**
   * Take any dashed or "underscored" lower-case string and convert to
   * camel-case.
   *
   * @param string $string the string to convert.
   *
   * @param bool $capitalizeFirstCharacter self explaining.
   *
   * @param string $dashes Characters to replace, defaults to '-_',
   * i.e. dashes and underscores are both considered as separators.
   *
   * @return string
   */
  protected static function dashesToCamelCase(string $string, bool $capitalizeFirstCharacter = false, string $dashes = '_-'):string
  {
    if ($string === null) {
      return null;
    }
    $str = str_replace(str_split($dashes), '', ucwords($string, $dashes));

    if (!$capitalizeFirstCharacter) {
      $str[0] = strtolower($str[0]);
    }

    return $str;
  }

  /**
   * Take an camel-case string and convert to lower-case with dashes or
   * underscores between the words. First letter may or may not be upper
   * case. Sequences of numbers and punctutation characters (different from
   * what is passed in the $dashes argument) are considered as words,
   * e.g. 'Example99!' will be converted to 'Example_99_!'
   *
   * @param string $string String to work on.
   *
   * @param string $separator Separator to use, defaults to '-'.
   *
   * @return string
   */
  protected static function camelCaseToDashes(string $string, string $separator = '-'):string
  {
    return strtolower(preg_replace('/([A-Z]|[0-9]+|[[:punct:]]+)/', $separator . '$1', lcfirst($string)));
  }
}
