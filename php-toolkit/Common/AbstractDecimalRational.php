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

namespace OCA\RotDrop\Toolkit\Common;

use OutOfBoundsException;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;

use OCA\RotDrop\Toolkit\Constants;

/**
 * Just like RationalNumber, but the jsonSerialize() implementation yields a
 * decimal number string with configured precision and scale.
 */
#[TSAttributes\TypeScript]
#[TSAttributes\LiteralTypeScriptType('string')]
#[TSAttributes\TypeScriptTransformer(DtoTransformer::class)]
abstract class AbstractDecimalRational extends RationalNumber
{
  public const PRECISION = Constants::MONETARY_PRECISION;
  public const SCALE = Constants::MONETARY_SCALE;

  /**
   * {@inheritdoc}
   *
   * Override the parent  method with the given scale and precision.
   */
  public function toDecimal(int $scale = -1, int $precision = 0): string
  {
    if ($scale == -1) {
      $scale = static::SCALE;
    }
    if ($precision == 0) {
      $precision = static::PRECISION;
    }
    return parent::toDecimal($scale, $precision);
  }

  /**
   * Potentially lossy conversion to a decimal number string.
   *
   * @return mixed
   *
   * @throws OutOfBoundsException
   */
  public function jsonSerialize(): mixed
  {
    return $this->toDecimal();
  }
}
