<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use OCP\IPreview;
use OCP\Files\Node;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMovableMount;

/** Helper trait for file-system nodes. */
trait NodeTrait
{
  use UserRootFolderTrait;

  /** @var string $userId */
  protected string $userId;

  /** @var IPreview */
  protected IPreview $previewManager;

  /** @var IRootFolder */
  protected IRootFolder $rootFolder;

  /**
   * Stolen from the template-manager.
   *
   * @param Node $node
   *
   * @return array
   *
   * @throws NotFoundException
   * @throws \OCP\Files\InvalidPathException
   */
  protected function formatNode(Node $node):array
  {
    $mount = $node->getMountPoint();
    $mountType = $mount->getMountType();
    $extraPermissions = ($mount instanceof IMovableMount)
      ? (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_DELETE)
      : 0;
    $path = $node->getPath();
    $topLevelFolder = $this->getUserFolder();
    if (!str_starts_with($path, $topLevelFolder->getPath())) {
      $topLevelFolder = $this->getUserAppFolder();
      if (!str_starts_with($path, $topLevelFolder->getPath())) {
        $topLevelFolder = null;
      }
    }
    if (!empty($topLevelFolder)) {
      $relativePath = $topLevelFolder->getRelativePath($path);
      $topLevelFolder = $topLevelFolder->getPath();
    }
    return [
      'fileid' => $node->getId(),
      'path' => $path,
      'topLevelFolder' => $topLevelFolder,
      'relativePath' => $relativePath,
      'basename' => $node->getName(),
      'lastmod' => $node->getMTime(),
      'mime' => $node->getMimetype(),
      'size' => $node->getSize(),
      'type' => $node->getType(),
      'hasPreview' => $this->previewManager->isAvailable($node),
      'permissions' => $node->getPermissions() | $extraPermissions,
      'mount-type' => $mountType,
      'etag' => $node->getEtag(),
    ];
  }
}
