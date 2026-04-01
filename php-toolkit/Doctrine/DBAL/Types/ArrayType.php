<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

use function is_resource;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function stream_get_contents;
use function unserialize;

/**
 * Type that maps a PHP array to a clob SQL type.
 */
class ArrayType extends Type
{
  public const NAME = 'array';

  /**
   * {@inheritDoc}
   */
  public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
  {
    return $platform->getClobTypeDeclarationSQL($column);
  }

  /**
   * {@inheritDoc}
   */
  public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
  {
    return serialize($value);
  }

  /**
   * {@inheritDoc}
   */
  public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
  {
    if ($value === null) {
      return null;
    }

    $value = is_resource($value) ? stream_get_contents($value) : $value;

    set_error_handler(function (int $code, string $message): bool {
      if ($code === E_DEPRECATED || $code === E_USER_DEPRECATED) {
        return false;
      }

      throw ConversionException::conversionFailedUnserialization($this->getName(), $message);
    });

    try {
      return unserialize($value);
    } finally {
      restore_error_handler();
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getName()
  {
    return self::NAME;
  }

  /**
   * {@inheritDoc}
   */
  public function requiresSQLCommentHint(AbstractPlatform $platform)
  {
    return true;
  }
}
