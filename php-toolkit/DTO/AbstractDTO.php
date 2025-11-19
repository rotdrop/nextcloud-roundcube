<?php
/**
 * Orchestra member, musician and project management application.
 *
 * CAFEVDB -- Camerata Academica Freiburg e.V. DataBase.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022-2025 Claus-Justus Heine
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

namespace OCA\RotDrop\Toolkit\DTO;

use DateTimeInterface;
use ReflectionClass;
use ReflectionProperty;

/**
 * Base class for DTOs.
 */
abstract class AbstractDTO implements \JsonSerializable
{
  protected static ?array $keys = [];

  /**
   * Fetch the names of all public properties.
   *
   * @return void
   */
  protected static function initKeys(): void
  {
    if (empty(static::$keys[static::class])) {
      static::$keys[static::class] = array_map(
        fn(ReflectionProperty $p) => $p->getName(),
        (new ReflectionClass(static::class))->getProperties(ReflectionProperty::IS_PUBLIC),
      );
    }
  }

  /** {@inheritdoc} */
  public function jsonSerialize(): mixed
  {
    static::initKeys();
    $result = [];
    foreach (static::$keys[static::class] as $key) {
      $value = $this->{$key};
      if ($value instanceof DateTimeInterface) {
        $value = $value->format(DateTime::W3C);
      }
      $result[$key] = $value;
    }
    return $result;
  }

  /**
   * Return the name of all public properties.
   *
   * @return array
   */
  public static function getKeys(): array
  {
    static::initKeys();
    return static::$keys[static::class];
  }

  /** @return array */
  public function toArray(): array
  {
    return $this->jsonSerialize();
  }
}
