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
 * decimal number string with the given scale and maximum precision.
 */
#[TSAttributes\TypeScript]
#[TSAttributes\LiteralTypeScriptType('string')]
#[TSAttributes\TypeScriptTransformer(DtoTransformer::class)]
class DecimalRationalP4S4 extends AbstractDecimalRational
{
  public const PRECISION = 4;
  public const SCALE = 4;
}
