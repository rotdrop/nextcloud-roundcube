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

namespace OCA\RotDrop\DevScripts\PhpToTypeScript;

use Attribute;

use Spatie\TypeScriptTransformer\Types\StructType;
use Spatie\TypeScriptTransformer\Types\TypeScriptType;
use phpDocumentor\Reflection\Type;

/**
 * Define additional properties which should be present in the TS output.
 */
#[Attribute(Attribute::IS_REPEATABLE|Attribute::TARGET_CLASS)]
class LiteralTypeScriptProperty
{
  /** {@inheritdoc} */
  public function __construct(
    private string $propertyName,
    private string | array $typeScript,
    private bool $optional = false,
  ) {
  }

  /** @return string */
  public function getPropertyName(): string
  {
    return $this->propertyName;
  }

  /** @return bool */
  public function getOptional(): bool
  {
    return $this->optional;
  }

  /** @return Type */
  public function getType(): Type
  {
    if (is_string($this->typeScript)) {
      return new TypeScriptType($this->typeScript);
    }

    $types = array_map(
      fn (string $type) => new TypeScriptType($type),
      $this->typeScript
    );

    return new StructType($types);
  }
}
