<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
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

namespace OCA\RotDrop\Toolkit\Traits;

use SimpleXMLElement;

/**
 * Trait which extracts the app-name from the info.xml file for cases where it
 * cannot be supplied by the cloud.
 */
trait AppNameTrait
{
  /**
   * @param string $classDir The value of __DIR__ of the consuming class.
   *
   * @return null|string The app-name from the info.xml file or null if that
   * cannot be found.
   */
  protected function getAppInfoAppName(string $classDir):?string
  {
    // Extract the directory nesting level from the class-name, so this counts
    // the part after OCA\APP_NAME_SPACE. Thus OCA\APP\AppInfo\Application.php
    // yields a nesting-level of 2 and yes, the info file is
    // lib/AppInfo/../../appinfo/info.xml
    $nestingLevel = count(explode('\\', __CLASS__)) - 2;

    $pathPrefix = str_repeat(Constants::PATH_SEPARATOR . '..', $nestingLevel);
    $infoFile = Constants::PATH_SEPARATOR . Constants::INFO_FILE;

    // we do not try-catch here as this file MUST be there and parseable.
    $infoXml = new SimpleXMLElement(file_get_contents($classDir . $pathPrefix . $infoFile));

    return !empty($infoXml->id) ? (string)$infoXml->id : null;
  }
}
