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

use Throwable;

/** Base class for database exceptions providing an entity-class. */
class DatabaseEntityException extends DatabaseException
{
  /**
   * @param string $message
   *
   * @param int $code
   *
   * @param ?Throwable $previous
   *
   * @param ?string $entityClassName
   *
   * {@inheritdoc}
   */
  public function __construct(
    string $message,
    int $code = 0,
    ?Throwable $previous = null,
    public readonly ?string $entityClassName = null,
  ) {
    parent::__construct($message, $code, $previous);
  }
}
