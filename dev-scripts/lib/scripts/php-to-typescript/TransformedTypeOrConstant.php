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

use ReflectionClass;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

/** {@inheritdoc} */
class TransformedTypeOrConstant extends TransformedType
{
  public ?string $namespaceName = null;

  /** {@inheritdoc} */
  public function getNamespaceSegments(): array
  {
    if ($this->isInline === true) {
      return [];
    }

    $namespace = $this->namespaceName
      ?? ($this->keyword == 'const'
          ? $this->reflection->getName()
          : $this->reflection->getNamespaceName());

    if (empty($namespace)) {
            return [];
    }

    return explode('\\', $namespace);
  }

/** {@inheritdoc} */
  public function toString(): string
  {
    if ($this->keyword == 'const') {
      return "const {$this->name} = {$this->transformed}"
        . ($this->trailingSemicolon ? ';' : '');
    }
    return parent::toString();
  }

  /** {@inheritdoc} */
  public static function create(
    ReflectionClass $class,
    string $name,
    string $transformed,
    ?MissingSymbolsCollection $missingSymbols = null,
    bool $inline = false,
    string $keyword = 'type',
    bool $trailingSemicolon = true,
    ?array $templateTypes = null,
  ): self {
    $instance = new self($class, $name, $transformed, $missingSymbols ?? new MissingSymbolsCollection(), $inline, $keyword, $trailingSemicolon);
    return $instance;
  }
}
