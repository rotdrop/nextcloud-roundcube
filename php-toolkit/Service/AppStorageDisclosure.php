<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2024, 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCP\IConfig as CloudConfig;
use OCP\Files\IAppData;
use OCP\Files\Mount\IMountManager;
use OCP\Files\IRootFolder;
use OCP\Files\Folder;
use OCP\Files\NotFoundException as FileNotFoundException;

use OCA\RotDrop\Toolkit\Traits\Constants;

/**
 * Disclose the app-storage folder as ordinary file-system Folder instance
 * instead of only as \OCP\Files\SimpleFS\ISimpleRoot
 */
class AppStorageDisclosure
{
  use \OCA\RotDrop\Toolkit\Traits\LoggerTrait;

  public const PATH_SEP = Constants::PATH_SEPARATOR;

  private const APP_DATA_PREFIX = 'appdata_';

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private string $appName,
    private IAppData $appData,
    private IRootFolder $rootFolder,
    private IMountManager $mountManager,
    private CloudConfig $cloudConfig,
    protected LoggerInterface $logger,
    protected IL10N $l,
  ) {
  }
  // phpcs:enable

  /**
   * @return string Determine the name of the app-data folder.
   *
   * @todo This does not use internal APIs, but knowledge about internal
   * details of Nextcloud which might change in the future.
   */
  private function getAppDataFolderName(): string
  {
    $instanceId = $this->cloudConfig->getSystemValue('instanceid', null);
    if ($instanceId === null) {
      // can this be at this point?
      throw new RuntimeException($this->l->t('Cloud installation problem: instance id is missing.'));
    }
    return self::APP_DATA_PREFIX . $instanceId;
  }

  /**
   * @return Folder The app root folder.
   */
  private function getAppRootFolder():Folder
  {
    $path = $this->getAppDataFolderName();
    $mount = $this->mountManager->find($path);
    $storage = $mount->getStorage();
    $internalPath = $mount->getInternalPath($path);
    if ($storage->file_exists($internalPath)) {
      $folder = $this->rootFolder->get($path);
    } else {
      throw new RuntimeException($this->l->t('App-data root-folder does not exist.'));
    }
    return $folder;
  }

  /**
   * Obtain an app-data folder as ordinary Filesystem Folder instance instead
   * of \OCP\Files\SimpleFS\ISimpleFolder. The folder is created if it does
   * not exist.
   *
   * @param string $path Path relative to the app-data directory for this app.
   *
   * @return Folder Filesystem folder instance pointing to $path.
   */
  public function getFilesystemFolder(string $path = ''):Folder
  {
    $rootFolder = $this->getAppRootFolder();
    /** @var Folder $appFolder */
    try {
      $appFolder = $rootFolder->get($this->appName);
    } catch (FileNotFoundException) {
      $appFolder = $rootFolder->newFolder($this->appName);
    }
    if ($path == '') {
      return $appFolder;
    }
    try {
      $folder = $appFolder->get($path);
    } catch (FileNotFoundException $e) {
      $this->logInfo('NOT FOUND EXCEPTION');
      // fallthrough
    } catch (Throwable $t) {
      $this->logInfo('OTHER ERROR ' . get_class($t));
    }
    if (empty($folder)) {
      $this->logInfo('TRY CREATE ' . $path);
      $folder = $appFolder->newFolder($path);
      if (empty($folder)) {
        throw new RuntimeException(
          $this->l->t(
            'App-storage sub-folder "%s" does not exist and cannot be created.',
            $path,
          ),
        );
      }
    }
    return $folder;
  }
}
