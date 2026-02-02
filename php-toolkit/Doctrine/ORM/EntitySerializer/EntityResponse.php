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
 * A collection of entities without duplicates.
 */
#[TSAttributes\TemplateParameters('K extends keyof ' . ORM::class . '.EntityMetadata.EntityMap')]
class EntityResponse extends \OCA\RotDrop\Toolkit\DTO\AbstractResponseDTO
{
  /** {@inheritdoc} */
  public function __construct(
    /**
     *  @var array<string, array<string> >
     *
     * Still difficulties with those TS magic ... the current construct makes
     * sure that if K consists of multiple entity names then it is not clear
     * which fields are set, while if K only "contains" a single entity name
     * then this field must not be unset.
     */
    #[TSAttributes\LiteralTypeScriptType('{ [e in K]: K extends e ? string[] : undefined|string[] }')]
    public readonly array $entities,
    /** @var array<string, array<string, object> > */
    #[TSAttributes\LiteralTypeScriptType('{ [e in K]: { [id: string]: ' . ORM::class . '.EntityMetadata.EntityDto<e> } }')]
    public readonly array $repositories,
  ) {
  }
}
