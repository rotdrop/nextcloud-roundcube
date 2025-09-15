<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Traits;

use ReflectionMethod;

use OCP\AppFramework\Utility\IControllerMethodReflector;

/**
 * For consumption by middleware, allow an attribute or an annotation which is
 * the "basename" of the attribute class.
 */
trait HasAnnotationOrAttributeTrait
{
  use LoggerTrait;

  protected IControllerMethodReflector $reflector;

  /**
   * @param ReflectionMethod $reflectionMethod
   *
   * @param class-string $attributeClass
   *
   * @return boolean
   */
  protected function hasAnnotationOrAttribute(ReflectionMethod $reflectionMethod, string $attributeClass): bool
  {
    if (!empty($reflectionMethod->getAttributes($attributeClass))) {
      return true;
    }

    $annotationName = substr(strrchr($attributeClass, '\\'), 1);

    if ($annotationName && $this->reflector->hasAnnotation($annotationName)) {
      $this->logError(
        $reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName()
        . ' uses the @' . $annotationName . ' annotation but should use the #[' . $attributeClass . '] attribute instead',
      );
      return true;
    }

    return false;
  }
}
