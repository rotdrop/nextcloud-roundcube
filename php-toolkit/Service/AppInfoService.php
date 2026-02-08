<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use SimpleXMLElement;

use OCP\App\IAppManager;

use OCA\RotDrop\Toolkit\Traits\Constants;

/**
 * Access info from the appinfo/info.xml.
 */
class AppInfoService
{
  private static string $appInfoPath;

  private static string $appFolderPath;

  private static array $appInfo;

  /**
   * Determine the path to the app's info.xml file assuming that the app PHP
   * files start at lib/ from the app directory.
   *
   * @return string
   */
  public static function getAppInfoPath(): string
  {
    if (self::$appInfoPath ?? null) {
      return self::$appInfoPath;
    }

    self::$appInfoPath = self::getAppFolderPath() . Constants::PATH_SEP . Constants::INFO_FILE;

    return self::$appInfoPath;
  }

  /**
   * Determine the path of the application folder.
   *
   * @return string
   */
  public static function getAppFolderPath(): string
  {
    if (self::$appFolderPath ?? null) {
      return self::$appFolderPath;
    }

    // Extract the directory nesting level from the class-name, so this counts
    // the part after OCA\APP_NAME_SPACE. Thus OCA\APP_NAME_SPACE\AppInfo\Application.php
    // yields a nesting-level of 2 and yes, the info file is
    // lib/AppInfo/../../appinfo/info.xml
    $nestingLevel = count(explode('\\', __CLASS__)) - 2;

    $pathPrefix = str_repeat(Constants::PATH_SEP . '..', $nestingLevel);

    self::$appFolderPath = __DIR__ . $pathPrefix;

    return self::$appFolderPath;
  }

  /**
   * @return null|string The app-name from the info.xml file or null if that
   * cannot be found.
   */
  public static function getAppInfoAppName(): ?string
  {
    // we do not try-catch here as this file MUST be there and parseable.
    $infoXml = new SimpleXMLElement(file_get_contents(self::getAppInfoPath()));

    return !empty($infoXml->id) ? (string)$infoXml->id : null;
  }

  /**
   * Ask IAppManager about the full app-info file.
   *
   * @return array<string, mixed>
   */
  public static function getAppInfo(): array
  {
    if (self::$appInfo ?? null) {
      return self::$appInfo;
    }
    /** @var IAppManager $appManager */
    $appManager = \OCP\Server::get(IAppManager::class);
    self::$appInfo = $appManager->getAppInfoByPath(self::getAppInfoPath());
    return self::$appInfo;
  }
}
