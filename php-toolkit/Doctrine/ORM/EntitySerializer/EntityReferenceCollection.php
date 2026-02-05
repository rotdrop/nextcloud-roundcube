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

namespace OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;

/**
 * The metadata generator must generate the metadata in "our" namespace.
 */
use OCA\RotDrop\Toolkit\Doctrine\ORM;

/**
 * Simple entity reference with optional class name and flattened identifier.
 */
#[TSAttributes\TemplateParameters('K extends keyof ' . ORM::class . '.EntityMetadata.EntityMap')]
class EntityReferenceCollection extends \OCA\RotDrop\Toolkit\DTO\AbstractDTO
{
  /** {@inheritdoc} */
  public function __construct(
    #[TSAttributes\LiteralTypeScriptType('K')]
    public readonly string $entityClassName,
    /**
     * @var Collection<string, EntityReference>
     *
     * The array key is whatever has been specified by "indexBy".
     */
    #[TSAttributes\LiteralTypeScriptType('{ [index: string|number]: ' . EntityReference::class . '<keyof ' . ORM::class . '.EntityMetadata.EntityMap> }')]
    public readonly array $entities,
  ) {
  }

  /**
   * Create an instance from a data array.
   *
   * @param array $data
   *
   * @return self
   *
   * @SuppressWarnings(PHPMD.UndefinedVariable)
   * @SuppressWarnings(PHPMD.UnusedLocalVariable)
   */
  public static function fromArray(array $data): self
  {
    static::initKeys();
    extract(array_intersect_key($data, array_flip(static::$keys[__CLASS__])));
    return new self(
      entityClassName: $entityClassName,
      entities: $entities,
    );
  }
}
