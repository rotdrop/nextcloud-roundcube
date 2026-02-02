<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025, 2026 Claus-Justus Heine
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
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;
use Spatie\TypeScriptTransformer\TypeReflectors\ClassTypeReflector;

use OCA\RotDrop\Toolkit\DTO\AbstractDTO;

/**
 * Collect DTOs.
 */
class DTOCollector extends DefaultCollector
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

    $appNamespace = $this->config->getAppNamespace();
    $dtoClass = str_replace('RotDrop', $appNamespace, AbstractDTO::class);

    if (!$class->isSubclassOf($dtoClass)) {
      return false;
    }

    return true;
  }
}
