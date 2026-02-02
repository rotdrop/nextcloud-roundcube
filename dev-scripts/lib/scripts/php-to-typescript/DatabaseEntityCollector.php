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

use Spatie\TypeScriptTransformer\Collectors\DefaultCollector;
use Spatie\TypeScriptTransformer\Exceptions\InvalidTransformerGiven;
use Spatie\TypeScriptTransformer\Exceptions\TransformerNotFound;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\TypeReflectors\ClassTypeReflector;

use Doctrine\ORM\Mapping as ORM;

/**
 * Collect all database entities.
 */
class DatabaseEntityCollector extends DefaultCollector
{
  /** {@inheritdoc} */
  protected function shouldCollect(ClassTypeReflector $reflector): bool
  {
    $class = $reflector->getReflectionClass();

    $transformers = array_map('get_class', $this->config->getTransformers());

    $hasTransformer = count(
      array_filter($transformers, function (string $transformer) {
        if ($transformer === DtoTransformer::class) {
          return true;
        }

        return is_subclass_of($transformer, DtoTransformer::class);
      }),
    ) > 0;

    if (! $hasTransformer) {
      return false;
    }

    $entityAttribute = ORM\Entity::class;
    $scopedNamespaces = $this->config->getScopedNamespaces();
    if (!empty(array_filter($scopedNamespaces, fn(string $nameSpace) => str_starts_with($entityAttribute, $nameSpace)))) {
      $scopedNamespacePrefix = $this->config->getScopedNamespacePrefix();
      $entityAttribute = "{$scopedNamespacePrefix}\\{$entityAttribute}";
    }

    if (empty($class->getAttributes($entityAttribute))) {
      return false;
    }

    return true;
  }

  /** {@inheritdoc} */
  protected function resolveAlreadyTransformedType(ClassTypeReflector $reflector): TransformedType
  {
    $oldNullableOptional = $this->config->shouldConsiderNullAsOptional();
    $this->config->nullToOptional(false);
    $result = parent::resolveAlreadyTransformedType($reflector);
    $this->config->nullToOptional($oldNullableOptional);

    return $result;
  }

  /** {@inheritdoc} */
  protected function resolveTypeViaTransformer(ClassTypeReflector $reflector): null|TransformedType|TypesCollection
  {
    $transformerClass = DatabaseEntityTransformer::class;
    $reflectionClass = $reflector->getReflectionClass();

    if (!class_exists($transformerClass)) {
      throw InvalidTransformerGiven::classDoesNotExist(
        $reflectionClass,
        $transformerClass
      );
    }

    if (!is_subclass_of($transformerClass, Transformer::class)) {
      throw InvalidTransformerGiven::classIsNotATransformer(
        $reflectionClass,
        $transformerClass
      );
    }

    $transformer = $this->config->buildTransformer($transformerClass);

    return $transformer->transform(
      $reflectionClass,
      $reflector->getName()
    );
  }
}
