<?php
/**
 * Some PHP utility functions for Nextcloud apps.
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

namespace OCA\RotDrop\Toolkit\Service;

use RuntimeException;

use Psr\Log\LoggerInterface;
use OCP\IL10N;
use OCP\Constants;
use OCP\Files\IRootFolder;

use OCA\GroupFolders\Folder\FolderManager;
use OCA\GroupFolders\Mount\MountProvider;

/**
 * Mis-use the internal services of the groupfolders app in order to
 * automatically generate group-shared folder structures.
 */
class GroupFoldersService
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  const GROUP_FOLDERS_APP = 'groupfolders';

  const SEARCH_TOPIC_MOUNT = 'mount';
  const SEARCH_TOPIC_GROUP = 'group';

  const PERMISSION_WRITE = Constants::PERMISSION_READ|Constants::PERMISSION_UPDATE|Constants::PERMISSION_CREATE;
  const PERMISSION_READ = Constants::PERMISSION_READ;
  const PERMISSION_DELETE = Constants::PERMISSION_READ|Constants::PERMISSION_DELETE;
  const PERMISSION_SHARE = Constants::PERMISSION_READ|Constants::PERMISSION_SHARE;
  const PERMISSION_ALL = Constants::PERMISSION_ALL;
  const DEFAULT_PERMISSIONS = self::PERMISSION_ALL;

  const MANAGER_TYPE_GROUP = 'group';
  const MANAGER_TYPE_USER = 'user';

  /** @var IL10N */
  private $l;

  /** @var IRootFolder */
  private $rootFolder;

  /** @var FolderManager */
  private $folderManager;

  /** @var MountProvider */
  private $mountProvider;

  /**
   * @var array
   *
   * All shared folders.
   * ```
   * [
   *   'id' => ID,
   *   'mount_point' => MOUNT_POINT,
   *   'groups' => [
   *     GROUP_ID => [
   *       'displayName' => GROUP_ID
   *       'permissions' => PERMISSIONS,
   *       'type' => 'group',
   *       ...
   *     ],
   *   ],
   *   'quota' => -3,
   *   'size' => SIZE,
   *   'acl' => true/false,
   *   'manage' => [
   *      [ 'type' => 'group'/'user', 'id' => MANAGER_ID, 'displayname' => DISPLAY_NAME ]
   *   ]
   * ]
   * ```
   */
  private $sharedFolders = null;

  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    LoggerInterface $logger,
    IRootFolder $rootFolder,
    FolderManager $folderManager,
    MountProvider $mountProvider,
    ?IL10N $l10n = null,
  ) {
    $this->logger = $logger;
    $this->rootFolder = $rootFolder;
    $this->folderManager = $folderManager;
    $this->mountProvider = $mountProvider;
    $this->l = $l10n;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
   * Set the localization to use.
   *
   * @param IL10N $l10n
   *
   * @return GroupFOldersService $this.
   */
  public function setL10N(IL10N $l10n):GroupFoldersService
  {
    $this->l = $l10n;

    return $this;
  }

  /**
   * Fetch and cache all shared folders from the groupfolders app.
   *
   * @return void
   */
  private function fetchFolders():void
  {
    $folders = $this->folderManager->getAllFoldersWithSize($this->getRootFolderStorageId());
    $this->sharedFolders = [];
    foreach ($folders as $folderInfo) {
      $this->sharedFolders[$folderInfo['mount_point']] = $folderInfo;
    }
    $this->logDebug('FOLDERS ' . print_r($this->sharedFolders, true));
  }

  /**
   * @param bool $reload
   *
   * @return void
   */
  private function ensureFolders(bool $reload = false):void
  {
    if ($reload || $this->sharedFolders === null) {
      $this->fetchFolders();
    }
  }

  /**
   * Obtain the shared folder data for the given mount-point.
   *
   * @param string $mountPoint
   *
   * @param bool $reload
   *
   * @return null|array Return the data requested from the groupfolders app or
   * null if the folder is not found.
   */
  public function getFolder(string $mountPoint, bool $reload = false):?array
  {
    $this->ensureFolders($reload);
    $this->logDebug('SHARED FOLDERS: ' . print_r($this->sharedFolders, true));
    return $this->sharedFolders[$mountPoint] ?? null;
  }

  /**
   * Return all shared folders matching the given regexp.
   *
   * @param string $regexp
   *
   * @param string $topic
   *
   * @return array
   */
  public function searchFolders(string $regexp, string $topic = self::SEARCH_TOPIC_MOUNT):array
  {
    if ($this->sharedFolders === null) {
      $this->fetchFolders();
    }
    if ($regexp[0] != $regexp[-1]) {
      $regexp = '|^' . $regexp . '$|';
    }
    $this->logDebug('REGEXP ' . $regexp . ' / ' . $topic);
    switch ($topic) {
      case self::SEARCH_TOPIC_MOUNT:
        return array_filter($this->sharedFolders, function($folderInfo) use ($regexp) {
          return preg_match($regexp, $folderInfo['mount_point']);
        });
      case self::SEARCH_TOPIC_GROUP:
        return array_filter($this->sharedFolders, function($folderInfo) use ($regexp) {
          foreach (array_keys($folderInfo['groups']) as $group) {
            if (preg_match($regexp, $group)) {
              return true;
            }
            return false;
          }
        });
      default:
        throw new RuntimeException($this->l->t('Unknown search topic "%1$s"', $topic));
    }
  }

  /**
   * @param string $mountRegexp
   *
   * @return void
   */
  public function deleteFolders(string $mountRegexp):void
  {
    foreach ($this->searchFolders($mountRegexp) as $folderInfo) {
      $folder = $this->mountProvider->getFolder($folderInfo['id']);
      $folder->delete();
      $this->folderManager->removeFolder($folderInfo['id']);
      unset($this->sharedFolders[$folderInfo['mount_point']]);
    }
  }

  /**
   * Get the shared folder by its id. The folder must exist
   *
   * @param int $id
   *
   * @return array
   */
  public function getFolderById(int $id):array
  {
    $folderInfo = $this->folderManager->getFolder($id, $this->getRootFolderStorageId());
    if (empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder with id "%1$s" does not exist.', [ $id ]));
    }
    foreach ($this->sharedFolders as $mountPoint => $cachedInfo) {
      if ($cachedInfo['id'] == $folderInfo['id']) {
        unset($this->sharedFolders[$mountPoint]);
      }
    }
    $this->sharedFolders[$folderInfo['mount_point']] = $folderInfo;
    $this->logDebug('BY ID INFO ' . print_r($folderInfo, true));
    return $folderInfo;
  }

  /**
   * Create a shared folder and set appropriate permissions
   *
   * @param string $mountPoint Note the a nested mount-point will not create
   * the parent folders.
   *
   * @param array $groups Array $groupId => $groupPermissions
   * ```
   * [ GROUP1_ID => PERMS1, GROUP2_ID => PERMS2 ]
   * ```.
   *
   * @param array $manager ID => TYPE
   * ```
   * [ USER_ID => 'user', GROUP_ID => 'group' ]
   * ```.
   *
   * @return void
   */
  public function createFolder(string $mountPoint, array $groups, array $manager = []):void
  {
    $folderInfo = $this->getFolder($mountPoint);
    if (!empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder for mount-point "%1$s" already exists, cannot create it.', [ $mountPoint ]));
    }
    $id = $this->folderManager->createFolder($mountPoint);
    $folderInfo = $this->getFolderById($id);

    foreach ($groups as $groupId => $permissions) {
      $this->addGroupToFolder($mountPoint, $groupId, $permissions);
    }

    foreach ($manager as $managerId => $managerType) {
      $this->addManagerToFolder($mountPoint, $managerId, $managerType);
    }
  }

  /**
   * Add a single group to the given shared folder. This is a no-op if the
   * group including matching permissions is already there.
   *
   * @param string $mountPoint
   *
   * @param string $groupId
   *
   * @param int $permissions
   *
   * @param bool $canManage
   *
   * @return void
   */
  public function addGroupToFolder(
    string $mountPoint,
    string $groupId,
    int $permissions = self::DEFAULT_PERMISSIONS,
    bool $canManage = false,
  ):void {
    // POST BASEURL/groupfolders/folders/4/groups
    // DATA group: GROUP_ID
    $folderInfo = $this->getFolder($mountPoint);
    if (empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder for mount-point "%1$s" does not exist, cannot add group "%2$s".', [ $mountPoint, $groupId ]));
    }
    if (!isset($folderInfo['groups'][$groupId])) {
      $this->folderManager->addApplicableGroup($folderInfo['id'], $groupId);
      $folderInfo['groups'][$groupId] = [
        'displayName' => $groupId,
        'permissions' => self::DEFAULT_PERMISSIONS,
        'type' => 'group',
      ];
    }
    if (($folderInfo['groups'][$groupId]['permissions'] ?? 0) != $permissions) {
      $this->setGroupPermissions($mountPoint, $groupId, $permissions);
    }
    $this->changeFolderManager($mountPoint, $groupId, self::MANAGER_TYPE_GROUP, $canManage);
  }

  /**
   * Remove a group from the given shared folder.
   *
   * @param string $mountPoint
   *
   * @param string $groupId
   *
   * @return void
   */
  public function removeGroupFromFolder(string $mountPoint, string $groupId):void
  {
    // REMOVE GROUP
    // DELETE BASE_URL/groupfolders/folders/4/groups/GROUP_ID
    $folderInfo = $this->getFolder($mountPoint);
    if (empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder for mount-point "%1$s" does not exist, cannot remove group "%2$s".', [ $mountPoint, $groupId ]));
    }
    $this->folderManager->removeApplicableGroup($folderInfo['id'], $groupId);
    unset($folderInfo[$mountPoint]['groups'][$groupId]);
  }

  /**
   * @param string $mountPoint
   *
   * @param string $managerId
   *
   * @param string $type
   *
   * @param bool $canManage
   *
   * @return void
   */
  private function changeFolderManager(string $mountPoint, string $managerId, string $type, bool $canManage):void
  {
    // POST BASEURL/groupfolders/folders/8/acl
    // [ acl => 0 / 1 ]
    //
    // POST BASEURL/groupfolders/folders/4/manageACL
    // [ mappingType => MANAGER_TYPE, mappingId => MANAGER_ID, manageAcl=> 1 ]
    //
    $folderInfo = $this->getFolder($mountPoint);
    if (empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder for mount-point "%1$s" does not exist, cannot modify manager "%2$s".', [ $mountPoint, $managerId ]));
    }

    $this->logDebug('FOLDER INFO ' . print_r($folderInfo, true));

    // first check if anything needs to be done
    $aclEnabled = !!($folderInfo['acl'] ?? false);
    $isManager = 0 < count(array_filter($folderInfo['manage'] ?? [], function($manager) use ($managerId, $type) {
      return $manager['type'] == $type && $manager['id'] == $managerId;
    }));

    if ($isManager == $canManage && ($canManage || $isManager == $canManage)) {
      return;
    }

    $folderId = $folderInfo['id'];
    if (!$aclEnabled) {
      $this->folderManager->setFolderACL($folderId, true);
    }

    if ($isManager != $canManage) {
      $this->folderManager->setManageACL($folderId, $type, $managerId, $canManage);
    }
    if (!$aclEnabled || $isManager != $canManage) {
      $this->getFolderById($folderId);
    }
  }

  /**
   * @param string $mountPoint
   *
   * @param string $managerId
   *
   * @param string $managerType
   *
   * @return void
   */
  public function addManagerToFolder(string $mountPoint, string $managerId, string $managerType):void
  {
    $this->changeFolderManager($mountPoint, $managerId, $managerType, canManage: true);
  }

  /**
   * @param string $mountPoint
   *
   * @param string $managerId
   *
   * @param string $managerType
   *
   * @return void
   */
  public function removeManagerFromFolder(string $mountPoint, string $managerId, string $managerType):void
  {
    $this->changeFolderManager($mountPoint, $managerId, $managerType, canManage: false);
  }

  /**
   * @param string $mountPoint
   *
   * @param string $groupId
   *
   * @param int $permissions
   *
   * @return void
   */
  public function setGroupPermissions(string $mountPoint, string $groupId, int $permissions):void
  {
    // SET PERMISSIONS
    // POST BASE_URL/groupfolders/folders/4/groups/GROUP_ID
    // DATA permissions: PERM-BITFIELD
    $folderInfo = $this->getFolder($mountPoint);
    if (empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder for mount-point "%1$s" does not exist, cannot set permissions for group "%2$s".', [ $mountPoint, $groupId ]));
    }
    if (($folderInfo['groups'][$groupId]['permissions'] ?? 0) != $permissions) {
      $this->folderManager->setGroupPermissions($folderInfo['id'], $groupId, $permissions);
      $this->sharedFolders[$mountPoint]['groups'][$groupId]['permissions'] = $permissions;
    }
  }

  /**
   * Change the given group-shared folder -- given by its old mount-point --
   * to a new mount-point.
   *
   * @param string $mountPoint The old mount-point which determines the
   * group-shared folder to act upon.
   *
   * @param string $targetMountPoint The destination mount-point. If the
   * parent-directories of the destination mount-point do not exist then the
   * shared folder will be hidden.
   *
   * @param bool $moveChildren If true and the old mount-point appears as prefix
   * in other group-shared folders then change their respective mount-points
   * as well.
   *
   * @return void
   */
  public function changeMountPoint(string $mountPoint, string $targetMountPoint, bool $moveChildren = true):void
  {
    if ($mountPoint == $targetMountPoint) {
      return;
    }
    $folderInfo = $this->getFolder($mountPoint);
    if (empty($folderInfo)) {
      throw new RuntimeException($this->l->t('Shared folder for mount-point "%1$s" does not exist, cannot change its mount-point to "%2$s".', [ $mountPoint,  ]));
    }
    // POST BASE_URL/groupfolders/folders/FOLDER_ID/mountpoint
    // DATA [ mountpoint => NEW_MOUNT_POINT ]

    $this->folderManager->renameFolder($folderInfo['id'], $targetMountPoint);
    $this->sharedFolders[$targetMountPoint] = $this->sharedFolders[$mountPoint];
    unset($this->sharedFolders[$mountPoint]);
    $this->sharedFolders[$targetMountPoint]['mount_point'] = $targetMountPoint;

    if ($moveChildren) {
      $children = $this->searchFolders('|^' . $mountPoint . '/.*$|');
      foreach ($children as $childInfo) {
        $oldChildMount = $childInfo['mount_point'];
        $newChildMount = $targetMountPoint . substr($oldChildMount, strlen($mountPoint));
        try {
          $this->changeMountPoint($oldChildMount, $newChildMount, moveChildren: false);
        } catch (\Throwable $t) {
          $this->logException($t, 'Failed to move child mount from ' . $oldChildMount . ' to ' . $newChildMount);
        }
      }
    }
  }

  /** @return null|int The storage id of the root folder. */
  private function getRootFolderStorageId():?int
  {
    return $this->rootFolder->getMountPoint()->getNumericStorageId();
  }
}
