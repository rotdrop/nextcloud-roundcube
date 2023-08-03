<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use OCP\Files\IRootFolder;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException as FileNotFoundException;

/**
 * The actual user folder is USER_ROOT/files/. This trait provides some
 * convenience methods for the access of USER_ROOT. The consuming class must
 * initialize the protected member $rootFooder with an instance of IRootFolder
 * and the protected member $userId with the user id of the target user.
 */
trait UserRootFolderTrait
{
  /** @var string */
  protected $appName;

  /** @var string */
  protected $userId;

  /** @var IRootFolder */
  protected $rootFolder;

  /** @var Folder */
  protected $userRootFolder;

  /** @var Folder */
  protected $userFolder;

  /** @return Folder The user-folder*/
  public function getUserFolder():Folder
  {
    if (empty($this->userFolder)) {
      $this->userFolder = $this->rootFolder->getUserFolder($this->userId);
    }
    return $this->userFolder;
  }

  /** @return Folder The parent of the user-folder. */
  public function getUserRootFolder():Folder
  {
    if (empty($this->userRootFolder)) {
      $this->userRootFolder = $this->getUserFolder()->getParent();
    }
    return $this->userRootFolder;
  }

  /** @return Folder The app folder in the user's root-storage */
  public function getUserAppFolder():Folder
  {
    return $this->getUserTopLevelFolder($this->appName);
  }

  /**
   * Return the name top-level folder as Folder instance. The folder is
   * created if it does not exist.
   *
   * @param string $name
   *
   * @return Folder
   */
  public function getUserTopLevelFolder(string $name):Folder
  {
    $userRootFolder = $this->getUserRootFolder();
    try {
      $folder = $userRootFolder->get($name);
    } catch (FileNotFoundException $e) {
      $folder = $userRootFolder->newFolder($name);
    }
    return $folder;
  }

  /**
   * @param null|string $userId The user-id. If null then $this->userId is used.
   *
   * @return string Just the name of the user folder without recursing to the
   * file system. Thus the returned folder-name does not neccessarily point to
   * an existing folder. The returned path is relative to the root-folder
   * without a leading slash, e.g. "jane.doe/files".
   */
  public function getUserFolderPath(?string $userId = null):string
  {
    return ($userId ?? $this->userId) . Constants::PATH_SEPARATOR . Constants::USER_FOLDER_PREFIX;
  }

  /**
   * Walk the given $pathOrFolder and apply the callable to each found node.
   *
   * @param string|Folder $pathOrFolder Folder-path or Folder instance. If a
   * path then it must be relative to the user folder.
   *
   * @param null|callable $callback The callback receives two arguments, the
   * current file system node and the recursion depth. If the current node is
   * a folder then the callback is invoked before traversing its directory
   * entries.
   *
   * @param int $depth Internal recursion depth parameters. The $callback
   * receives it as second argument.
   *
   * @return int The number of files found during the walk.
   */
  public function folderWalk(mixed $pathOrFolder, ?callable $callback = null, int $depth = 0):int
  {
    /** @var \OCP\Files\File $node */
    if (!($pathOrFolder instanceof Folder)) {
      $folder = $this->getUserFolder()->get($pathOrFolder);
    } else {
      $folder = $pathOrFolder;
    }

    if (empty($folder)) {
      return 0;
    }

    if (!empty($callback)) {
      if ($callback($folder, $depth) === false) {
        return 0;
      }
    }
    ++$depth;

    $numberOfFiles = 0;
    $folderContents = $folder->getDirectoryListing();
    /** @var Node $node */
    foreach ($folderContents as $node) {
      if ($node->getType() == FileInfo::TYPE_FILE) {
        if (!empty($callback)) {
          $callback($node, $depth);
        }
        ++$numberOfFiles;
      } else {
        $numberOfFiles += $this->folderWalk($node, $callback, $depth);
      }
    }
    return $numberOfFiles;
  }
}
