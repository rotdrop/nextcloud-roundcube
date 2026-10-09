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

namespace OCA\RotDrop\Toolkit\Service\ArchiveService;

use OCA\RotDrop\Toolkit\DTO\AbstractDTO;

/** DTO class in order to communicate the archive info to the JS frontend. */
class ArchiveInfo extends AbstractDTO
{
  /** {@inheritdoc} */
  public function __construct(
    /**
     * @var string
     *
     * Internal format of the underlying archive backend.
     */
    public readonly string $format,
    /**
     * @var string
     *
     * Mime-type of the archive file.
     */
    public readonly string $mimeType,
    /**
     * @var int
     *
     * The size of the archive file (not neccessarily the sum of the size of the
     * archive members).
     */
    public readonly int $size,
    /**
     * @var int
     *
     * The sum of the compressed size of the archive members.
     */
    public readonly int $compressedSize,
    /**
     * @var int
     *
     * The sum of the uncompressed size of the archive members.
     */
    public readonly int $originalSize,
    /**
     * @var int
     *
     * The number of archive members (files) in the archive.
     */
    public readonly int $numberOfFiles,
    /**
     * @var ?string
     *
     * Some archive formats support optional creator supplied comments.
     */
    public readonly ?string $comment,
    /**
     * @var string
     *
     * Propose a mount point name based on the archive name.
     */
    public readonly string $defaultMountPoint,
    /**
     * @var string
     *
     * Compute the common path prefix of the archive members.
     */
    public readonly string $commonPathPrefix,
    /**
     * @var string
     *
     * The basename of the backend driver class.
     */
    public readonly string $backendDriver,
  ) {
  }

  /**
   * Initialize from the given array.
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
      format: $format,
      mimeType: $mimeType,
      size: $size,
      compressedSize: $compressedSize,
      originalSize: $originalSize,
      numberOfFiles: $numberOfFiles,
      comment: $comment,
      defaultMountPoint: $defaultMountPoint,
      commonPathPrefix: $commonPathPrefix,
      backendDriver: $backendDriver,
    );
  }
}
