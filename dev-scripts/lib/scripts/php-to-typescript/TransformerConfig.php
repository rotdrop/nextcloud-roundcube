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

use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

/**
 * Extend the default configuration class and add further options.
 */
class TransformerConfig extends TypeScriptTransformerConfig
{
  private bool $constantsAsConstants = true;

  private string $scopedNamespacePrefix = '';

  private array $scopedNamespaces = [];

  private string $appNamespace = 'RotDrop';

  /**
   * Set the app-namespace.
   *
   * @param string $appNamespace
   *
   * @return static
   */
  public function appNamespace(string $appNamespace): static
  {
    $appNamespace = trim($appNamespace, '\\');
    if (str_starts_with($appNamespace, 'OCA\\')) {
      $appNamespace = substr($appNamespace, strlen('OCA\\'));
    }
    $this->appNamespace = $appNamespace;

    return $this;
  }

  /**
   * The namespace prefix for classes wrapped by humbug/php-scoper.
   *
   * @param string $scopedNamespacePrefix
   *
   * @return static
   */
  public function scopedNamespacePrefix(string $scopedNamespacePrefix): static
  {
    $this->scopedNamespacePrefix = $scopedNamespacePrefix;

    return $this;
  }

  /**
   * A list of namespaces wrapped by humbug/php-scoper.
   *
   * @param array $scopedNamespaces
   *
   * @return static
   */
  public function scopedNamespaces(array $scopedNamespaces): static
  {
    $this->scopedNamespaces = $scopedNamespaces;

    return $this;
  }

  public function defaultInlineTypeReplacements(array $replacements): static
  {
    foreach ($replacements as $class => $replacement) {
      foreach ($this->scopedNamespaces as $namespace) {
        if (str_starts_with($class, $namespace)) {
          $replacements[$this->scopedNamespacePrefix . '\\' . $class] = $replacement;
          unset($replacements[$class]);
          break;
        }
      }
    }
    parent::defaultInlineTypeReplacements($replacements);
    return $this;
  }

  public function defaultTypeReplacements(array $replacements): static
  {
    foreach ($replacements as $class => $replacement) {
      foreach ($this->scopedNamespaces as $namespace) {
        if (str_starts_with($class, $namespace)) {
          $replacements[$this->scopedNamespacePrefix . '\\' . $class] = $replacement;
          unset($replacements[$class]);
          break;
        }
      }
    }
    parent::defaultTypeReplacements($replacements);
    return $this;
  }

  /**
   * Request to emit constants as literal type typed constants.
   *
   * @param bool $constantsAsConstants
   *
   * @return ConstantsTransformerConfig
   */
  public function constantsAsConstants(bool $constantsAsConstants = true): static
  {
    $this->constantsAsConstants = $constantsAsConstants;

    return $this;
  }

  /**
   * Request to emit constants as literal type typed interface properties.
   *
   * @param bool $constantsAsProperties
   *
   * @return ConstantsTransformerConfig
   */
  public function constantsAsProperties(bool $constantsAsProperties = true)
  {
    return $this->constantsAsConstants(!$constantsAsProperties);
  }

  /**
   * Return the scoped app namespace.
   *
   * @return string;
   */
  public function getAppNamespace(): string
  {
    return $this->appNamespace;
  }

  /**
   * Return the scoped namespace prefix.
   *
   * @return string;
   */
  public function getScopedNamespacePrefix(): string
  {
    return $this->scopedNamespacePrefix;
  }

  /**
   * Return the scoped namespaces.
   *
   * @return string;
   */
  public function getScopedNamespaces(): array
  {
    return $this->scopedNamespaces;
  }

  /**
   * @return bool Whether constants should be emitted as literal type typed constants variables.
   */
  public function emitConstantsAsConstants(): bool
  {
    return $this->constantsAsConstants;
  }

  /**
   * @return bool Whether constants should be emitted as literal type typed interface properties.
   */
  public function emitConstantsAsProperties(): bool
  {
    return !$this->emitConstantsAsConstants();
  }
}
