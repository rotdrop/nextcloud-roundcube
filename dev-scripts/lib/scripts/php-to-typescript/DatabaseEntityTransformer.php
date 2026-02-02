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
use ReflectionProperty;

use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

/**
 * Transform database entities, including and in particular their private and
 * protected properties.
 */
class DatabaseEntityTransformer extends DtoTransformer
{
  private ClassConstantsTransformer $classConstantsTransformer;

  /** {@inheritdoc} */
  public function __construct(
    TransformerConfig $config,
  ) {
    parent::__construct($config);
    $this->classConstantsTransformer = new ClassConstantsTransformer($config);
  }

  /** {@inheritdoc} */
  protected function resolveProperties(ReflectionClass $class): array
  {
    $visibility = ReflectionProperty::IS_PUBLIC
      |ReflectionProperty::IS_PROTECTED
      |ReflectionProperty::IS_PRIVATE;
    $properties = array_filter(
      $class->getProperties($visibility),
      fn (ReflectionProperty $property) => ! $property->isStatic()
    );

    return array_values($properties);
  }

  /** {@inheritdoc} */
  protected function transformExtra(
    ReflectionClass $class,
    MissingSymbolsCollection $missingSymbols
  ): string {
    $result = '';
    $attributes = $class->getAttributes(LiteralTypeScriptProperty::class);
    foreach ($attributes as $reflectionAttribute) {
      /** @var LiteralTypeScriptProperty $attribute */
      $attribute = $reflectionAttribute->newInstance();
      $propertyName = $attribute->getPropertyName();
      $propertyType = $attribute->getType();
      $isOptional = $attribute->getOptional();
      $transformedType = $this->typeToTypeScript(
        $propertyType,
        $missingSymbols,
        false,
        $class->getName(),
      );
      $result .= $isOptional
        ? "    {$propertyName}?: {$transformedType};" . PHP_EOL
        : "    {$propertyName}: {$transformedType};" . PHP_EOL;
    }

    return $result;
  }

  /** {@inheritdoc} */
  public function transform(ReflectionClass $class, string $name): null|TransformedType|TypesCollection
  {
    $oldNullableOptional = $this->config->shouldConsiderNullAsOptional();
    $this->config->nullToOptional(false); // the properties are guaranteed to be there, so ...
    $dtoType = parent::transform($class, $name);
    $this->config->nullToOptional($oldNullableOptional);

    $collection = $this->classConstantsTransformer->transform($class, 'Constants\\' . $name);
    if ($collection) {
      $collection[$class->getName()] = $dtoType;
      return $collection;
    }

    return $dtoType;
  }
}
