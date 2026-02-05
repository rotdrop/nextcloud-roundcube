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

use InvalidArgumentException;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionProperty;

use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\Structures\TypesCollection;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

/** Transform class constants to TypeScript constants. */
class ClassConstantsTransformer implements Transformer
{
  /** {@inheritdoc} */
  public function __construct(
    protected TransformerConfig $config,
  ) {
  }

  /** {@inheritdoc} */
  public function transform(ReflectionClass $class, string $name):null|TransformedType|TypesCollection
  {
    if (!$this->canTransform($class)) {
      return null;
    }

    $namespaceName = $class->getNamespaceName();
    $namespacedClassName = $namespaceName . '\\' . $name;

    $constants = $this->resolveConstants($class);

    if ($this->config->emitConstantsAsConstants()) {
      $collection = new TypesCollection();

      /** @var ReflectionClassConstant $constant */
      foreach ($constants as $constant) {
        $name = $constant->getName();
        $value = $constant->getValue();
        $value = self::convertValueToTypeScript($value);
        $constantType = TransformedTypeOrConstant::create(
          $class,
          $constant->getName(),
          $value,
          keyword: 'const',
        );
        $constantType->namespaceName = $namespacedClassName;
        $collection[$namespacedClassName . '.' . $constant->getName()] = $constantType;
      }

      return $collection;
    } else {
      $properties = $this->transformConstantsToProperties($class);

      return TransformedType::create(
        $class,
        $name,
        "{" . PHP_EOL . $properties . "}",
        keyword: 'interface',
      );
    }
  }

  /**
   * Convert multiline PHP strings to string templates for JS.
   *
   * @param mixed $value
   *
   * @param int $level
   *
   * @return string
   */
  private static function convertValueToTypeScript(mixed $value, int $level = 1): string
  {
    if (is_array($value)) {
      if (array_is_list($value)) {
        $result = '[' . PHP_EOL;
        // array reduce does not give access to keys ...
        foreach ($value as $key => $member) {
          $member = self::convertValueToTypeScript($member, $level + 1);
          $result .= str_pad('', ($level + 1) * 2) . "{$member}," . PHP_EOL;
        }
        $result .= str_pad('', $level * 2) . '] as const';
      } else {
        $result = '{' . PHP_EOL;
        // array reduce does not give access to keys ...
        foreach ($value as $key => $member) {
          $member = self::convertValueToTypeScript($member, $level + 1);
          if (str_contains($key, ' ')) {
            $key = "'{$key}'";
          }
          $result .= str_pad('', ($level + 1) * 2) . "{$key}: {$member} as const," . PHP_EOL;
        }
        $result .= str_pad('', $level * 2) . '}';
      }
      return $result;
    }
    if (is_string($value)) {
      if (str_contains($value, "\n")) {
        return '`' . $value . '`';
      } else {
        return "'" . $value . "'";
      }
    } elseif ($value === null) {
      $value = 'null';
    } elseif ($value === true) {
      $value = 'true';
    } elseif ($value === false) {
      $value = 'false';
    }
    if (!is_scalar($value)) {
      throw new InvalidArgumentException('Value is not scalar: ' . print_r($value, true) . ' NULL ' . (int)($value === null));
    }
    return $value;
  }

  /**
   * Transform constants into literal type typed members.
   *
   * @param ReflectionClass $class
   *
   * @return string
   */
  protected function transformConstantsToProperties(ReflectionClass $class): string
  {
    return array_reduce(
      $this->resolveConstants($class),
      function (string $carry, ReflectionClassConstant $constant) {
        $value = $constant->getValue();
        // @todo: also support non-scalars
        if (!is_scalar($value)) {
          return $carry;
        }
        $value = self::convertValueToTypeScript($value);
        $name = $constant->getName();
        return "{$carry}    {$name}: $value," . PHP_EOL;
      },
      '',
    );
  }

  /** {@inheritdoc} */
  protected function canTransform(ReflectionClass $class): bool
  {
    // This is for const-only classes.
    return true && count($this->resolveProperties($class)) == 0;
  }

  /** {@inheritdoc} */
  protected function resolveConstants(ReflectionClass $class): array
  {
    return $class->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC);
  }

  /** {@inheritdoc} */
  protected function resolveProperties(ReflectionClass $class): array
  {
    $properties = array_filter(
      $class->getProperties(ReflectionProperty::IS_PUBLIC),
            fn (ReflectionProperty $property) => ! $property->isStatic()
    );

    return array_values($properties);
  }
}
