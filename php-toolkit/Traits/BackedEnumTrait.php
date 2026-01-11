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
declare(strict_types=1);

namespace OCA\RotDrop\Toolkit\Traits;

use InvalidArgumentException;
use ReflectionClass;
use Throwable;
use TypeError;
use ValueError;

/**
 * Some convenience stuff for PHP enums.
 */
trait BackedEnumTrait
{
  /**
   * @return array<string> The names of the enum cases as flat array.
   */
  public static function names(): array
  {
    return array_column(self::cases(), 'name');
  }

  /**
   * @return array<string> The values of the enum cases as flat array.
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
  public static function toArray(): array
  {
    return array_combine(self::names(), self::values());
  }

    /**
   * Try to convert a string which is either the value or the case name into
   * the enum instance. If an enum instance is passed, it is just returned.
   *
   * @param self|int|string $instanceOrCaseOrValue
   *
   * @return self
   *
   * @throws InvalidArgumentException
   * @throws TypeError
   */
  public static function get(self|int|string $instanceOrCaseOrValue):self
  {
    if ($instanceOrCaseOrValue instanceof self) {
      return $instanceOrCaseOrValue;
    }
    try {
      $instance = self::from($instanceOrCaseOrValue);
    } catch (TypeError|ValueError $e) {
      try {
        $instance = self::{$instanceOrCaseOrValue};
      } catch (Throwable $t) {
        $class = new ReflectionClass($t);
        $ctor = $class->getConstructor();
        $ctor->invoke($t, $t->getMessage(), $t->getCode(), $e);
        $invalid = $instanceOrCaseOrValue ?? 'NULL';
        throw new InvalidArgumentException(
          "{$invalid} is neither a value nor a key of {self::class}: " . print_r(self::toArray(), true),
          previous: $t,
        );
      }
    }
    return $instance;
  }
}
