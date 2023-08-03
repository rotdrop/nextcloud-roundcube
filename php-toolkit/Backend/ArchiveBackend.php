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

namespace OCA\RotDrop\Toolkit\Backend;

use InvalidArgumentException;
use wapmorgan\UnifiedArchive;
use wapmorgan\UnifiedArchive\Abilities;

use OCA\RotDrop\Toolkit\Backend\ArchiveFormats as Formats;

/**
 * Overload UnifiedArchive\UnifiedArchive with the goal to tweak the driver
 * selection.
 */
class ArchiveBackend extends UnifiedArchive\UnifiedArchive
{
  /**
   * @var array
   *
   * Define a potentially configurable driver ranking. The one with the
   * highest ranking will be picked first.
   */
  protected static $driverRanking = [
    'zip' => [
      UnifiedArchive\Drivers\AlchemyZippy::class => 0,
      UnifiedArchive\Drivers\NelexaZip::class => 10,
      UnifiedArchive\Drivers\Zip::class => 20,
      UnifiedArchive\Drivers\SevenZip::class => 30,
    ],
  ];

  /**
   * {@inheritdoc}
   *
   * @param bool $contentCheck Whether to also look at the contents of a file
   * to determine its MIME-type if it could not be determined by its file
   * extension.
   */
  public static function open($fileName, $abilities = [], $password = null, bool $contentCheck = true)
  {
    if (!file_exists($fileName) || !is_readable($fileName)) {
      throw new InvalidArgumentException('Could not open file: ' . $fileName.' is not readable');
    }

    $format = Formats::detectArchiveFormat($fileName, contentCheck: $contentCheck);
    if ($format === false) {
      return null;
    }

    if (!empty($abilities) && is_string($abilities)) {
      $password = $abilities;
      $abilities = [];
    }

    if (empty($abilities)) {
      $abilities = [Abilities::OPEN];
      if (!empty($password)) {
        $abilities[] = Abilities::OPEN_ENCRYPTED;
      }
    }

    $formatDrivers = Formats::getFormatDrivers(format: $format, abilities: $abilities);
    if (empty($formatDrivers)) {
      return null;
    }
    $ranking = static::$driverRanking[$format] ?? [];
    usort($formatDrivers, fn(string $driverA, string $driverB) => -(($ranking[$driverA] ?? 0) <=> ($ranking[$driverB] ?? 0)));

    $driver = $formatDrivers[0];

    return new static(fileName: $fileName, format: $format, driver: $driver, password: $password);
  }
}
