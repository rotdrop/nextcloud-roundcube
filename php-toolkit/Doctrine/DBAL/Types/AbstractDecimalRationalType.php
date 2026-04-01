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

use Throwable;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Exception\SerializationFailed;
use Doctrine\DBAL\Types\Type;

use OCA\RotDrop\Toolkit\Common\RationalNumber;
use OCA\RotDrop\Toolkit\Common\AbstractDecimalRational;

/**
 * Abstract base class for decimal types
 */
abstract class AbstractDecimalRationalType extends Type
{
  protected const NUMBER_CLASS = AbstractDecimalRational::class;
  public const NAME_BASE = 'decimal_rational';

  /**
   * {@inheritDoc}
   */
  public function getName()
  {
    return static::NAME_BASE . '_' . (static::NUMBER_CLASS)::PRECISION . '_' . (static::NUMBER_CLASS)::SCALE;
  }

  /**
   * {@inheritDoc}
   *
   * This overrides precision and scale with the class constants
   */
  public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
  {
    $column['precision'] = (static::NUMBER_CLASS)::PRECISION;
    $column['scale'] = (static::NUMBER_CLASS)::SCALE;
    return $platform->getDecimalTypeDeclarationSQL($column);
  }

  /**
   * {@inheritDoc}
   */
  public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AbstractDecimalRational
  {
    if ($value === null || $value === '') {
      return null;
    }
    if ($value instanceof (static::NUMBER_CLASS)) {
      return $value;
    } elseif ($value instanceof RationalNumber) {
      return (static::NUMBER_CLASS)::create($value);
    }

    return (static::NUMBER_CLASS)::fromDecimal($value);
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $value
   *
   * @param AbstractPlatform $platform
   *
   * @return mixed
   *
   * @throws SerializationFailed
   */
  public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
  {
    if ($value === null || $value === '') {
      return null;
    }
    $originalValue = $value;
    try {
      if (!($value instanceof (static::NUMBER_CLASS))) {
        $value = (static::NUMBER_CLASS)::create($value);
      }
      return $value->jsonSerialize();
    } catch (Throwable $t) {
      throw SerializationFailed::new($originalValue, 'decimal', $t->getMessage(), $t);
    }
  }
}
