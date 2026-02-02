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

namespace OCA\RotDrop\Toolkit\Exceptions;

/**
 * Exception thrown by  OCA\RotDrop\Toolkit\Doctrine\ORM\EntitySerializer\EntitySerializer.
 */
class EntitySerializationException extends DatabaseException
{
  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $message,
    protected mixed $entity,
    int $code = 0,
    $previous = null,
  ) {
    parent::__construct($message, $code, $previous);
  }
  // phpcs:enable

  /** @return mixed */
  public function getEntity():mixed
  {
    return $this->entity;
  }
}
