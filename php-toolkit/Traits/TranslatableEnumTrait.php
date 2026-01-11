<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Traits;

use InvalidArgumentException;
use Throwable;
use ValueError;

use OCP\IL10N;

/**
 * Used primarily for L10N injection, a dev script can check for
 * self::getL10NValues() vie reflection and inject the enum values into the
 * translation templates.
 */

/**
 * Some convenience stuff for PHP enums.
 */
trait TranslatableEnumTrait
{
  use BackedEnumTrait;
  use CamelCaseToDashesTrait;

  public const L10N_TAG = 'ENUM_VALUE';

  /**
   * @param IL10N $l
   *
   * @return array translated value array.
   */
  public static function getL10NValues(IL10N $l): array
  {
    $values = self::values();
    return array_combine(
      $values,
      array_map(
        function(string $value) use ($l) {
          $prefix = self::l10nTag();
          $l10nValue = $l->t($prefix . $value);
          return ($l10nValue === $value || $l10nValue === $prefix . $value) ? $l->t($value) : $l10nValue;
        },
        $values,
      ),
    );
  }

  /** @return string */
  public static function l10nTag(): string
  {
    $classBaseName = substr(__CLASS__, strrpos(__CLASS__, '\\') + 1);
    if (str_starts_with($classBaseName, 'Enum')) {
      $classBaseName = substr($classBaseName, strlen('Enum'));
    }
    $classBaseName = strtoupper(self::camelCaseToDashes($classBaseName, separator: '_'));
    return self::L10N_TAG . '_' . $classBaseName . ': ';
  }
}
