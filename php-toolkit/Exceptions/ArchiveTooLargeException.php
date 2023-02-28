<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022 Claus-Justus Heine <himself@claus-justus-heine.de>
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
 * Transparent archive extraction exception.
 */
class ArchiveTooLargeException extends ArchiveException
{
  private int $limit;

  private int $actualSize;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(string $message, int $limit, int $actualSize, ?\Throwable $previous = null)
  {
    parent::__construct($message, 0, $previous);
  }

  /**
   * Return the configured limit.
   *
   * @return int
   */
  public function getLimit():int
  {
    return $this->limit;
  }

  /**
   * Return the actual uncompressed size of the archive.
   *
   * @return int
   */
  public function getActualSize():int
  {
    return $this->actualSize;
  }
}
