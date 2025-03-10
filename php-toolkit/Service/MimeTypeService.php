<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2024 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use Throwable;

use OC\Files\Type\Detection as MimeTypeDetector;
use Psr\Log\LoggerInterface;
use OCP\Files\IMimeTypeDetector;

use OCA\FilesArchive\Toolkit\Backend\ArchiveFormats;
use OCA\FilesArchive\Toolkit\Traits\Constants;

/** Tweak the Nextcloud server to support all MIME-types needed by this app. */
class MimeTypeService
{
  use \OCA\FilesArchive\Toolkit\Traits\LoggerTrait;

  const MIME_TYPE_MAPPING_DATA_FILE = 'config/nextcloud/mimetypemapping.json';
  const MIME_TYPE_ALIASES_DATA_FILE = 'config/nextcloud/mimetypealiases.json';

  /** @var array */
  private ?array $supportedMimeTypes = null;

  /** @var array<string, string> */
  private ?array $appMimeTypeMappings = null;

  /** @var array<string, string> */
  private ?array $allMimeTypeMappings = null;

  /** @var string */
  private $appPath;

  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    private IMimeTypeDetector $mimeTypeDetector,
    protected LoggerInterface $logger,
  ) {
  }
  // phpcs:enable

  /**
   * Configure the base directory containing the data files below
   * `$appPath/config/nextcloud/`. So this is actually just the configuration
   * prefix directory, typically the base-dir of the consuming app.
   *
   * @param string $appPath
   *
   * @return MimeTypeService $this for chaining.
   */
  public function setAppPath(string $appPath):MimeTypeService
  {
    $this->appPath = $appPath;
    if (!str_ends_with($this->appPath, Constants::PATH_SEPARATOR)) {
      $this->appPath .= Constants::PATH_SEPARATOR;
    }
    return $this;
  }

  /**
   * Register the needed extension to MIME-type mappings with the Nextcloud server.
   *
   * @return void
   *
   * @bug This function uses internal APIs.
   */
  public function registerMimeTypeMappings():void
  {
    if (!($this->mimeTypeDetector instanceof MimeTypeDetector)) {
      return;
    }

    $missingMimeMappings = $this->getMissingMimeTypeMappings();
    foreach ($missingMimeMappings as $extension => $mimeTypes) {
      $this->mimeTypeDetector->registerType($extension, $mimeTypes[0]);
    }
    $this->allMimeTypeMappings = null;
  }

  /**
   * Return an array of required mime type mappings missing from this Nextcloud instance.
   *
   * @return array
   */
  public function getMissingMimeTypeMappings():array
  {
    if (!($this->mimeTypeDetector instanceof MimeTypeDetector)) {
      return [];
    }

    $cloudMimeTypeMapping = $this->mimeTypeDetector->getAllMappings();
    $mimeTypeMapping = $this->getAppMimeTypeMappings();

    $missingMimeMappings = array_diff_key($mimeTypeMapping, $cloudMimeTypeMapping);
    // $this->logInfo('ADDING MISSING MIME-TYPE MAPPINGS ' . print_r($missingMimeMappings, true));

    return $missingMimeMappings;
  }

  /**
   * @return array<string, string> An array EXT => MIME of the supported
   * archive MIME-types. That is, the cloud must know the mime type and the
   * archive backend must support it in order to have an extension and
   * mime-type added to the list.
   */
  public function getSupportedArchiveMimeTypes():array
  {
    if ($this->supportedMimeTypes !== null) {
      return $this->supportedMimeTypes;
    }
    $this->registerMimeTypeMappings();
    $mimeTypeMapping = $this->getAllMimeTypeMappings();
    $supportedFormats = ArchiveFormats::getSupportedDriverFormats();
    $this->supportedMimeTypes = [];
    $unsupportedMimeTypes = [];
    foreach ($mimeTypeMapping as $extension => $mimeTypes) {
      if (count($mimeTypes) == 0) {
        $this->logError('Buggy config file, no mime-types for extension ' . $extension);
      } elseif (count($mimeTypes) > 1) {
        $this->logDebug('More than one mime-type for extension "' . $extension . '": ' . print_r($mimeTypes, true));
      }
      $mimeType = $mimeTypes[0];
      if ($mimeType == 'application/x-gtar') {
        $this->logInfo('MIME TYPE ' . $mimeType);
      }
      $format = ArchiveFormats::detectArchiveFormat('FOO.' . $extension, contentCheck: false);
      if (!empty($supportedFormats[$format])) {
        $this->supportedMimeTypes[$extension] = $mimeType;
      } else {
        $unsupportedMimeTypes[$extension] = $mimeType;
      }
    }
    // $this->logInfo('SUPPORTED MIME TYPES ' . print_r($this->supportedMimeTypes, true));
    // $this->logInfo('UNSUPPORTED MIME TYPES ' . print_r($unsupportedMimeTypes, true));

    return $this->supportedMimeTypes;
  }

  /** @return array Return all registered mime-type without comments. */
  private function getAllMimeTypeMappings():array
  {
    if ($this->allMimeTypeMappings !== null) {
      return $this->allMimeTypeMappings;
    }

    $arrayData = $this->mimeTypeDetector->getAllMappings();

    $this->allMimeTypeMappings = array_filter($arrayData, fn(string $key) => !str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);
    // $this->logInfo('MIME MAPPINGS ' . print_r($this->allMimeTypeMappings, true));

    return $this->allMimeTypeMappings;

  }

  /** @return array Slurp in and cache the extension to mime-type mapping. */
  private function getAppMimeTypeMappings():array
  {
    if ($this->appMimeTypeMappings !== null) {
      return $this->appMimeTypeMappings;
    }

    $baseDirs = [ $this->appPath, __DIR__ . '/../../' ];
    foreach ($baseDirs as $prefixDir) {
      $dataFile = $prefixDir . self::MIME_TYPE_MAPPING_DATA_FILE;
      $jsonData = file_get_contents($dataFile);
      if (empty($jsonData)) {
        $this->logInfo('Unable to read "' . $dataFile . '".');
        continue;
      }
      try {
        $arrayData = json_decode($jsonData, true);
        break;
      } catch (Throwable $t) {
        $this->logException($t, 'Unable to decode mime-type mapping. "' . self::MIME_TYPE_MAPPING_DATA_FILE . '".');
        $arrayData = null;
        continue;
      }
    }

    if (empty($arrayData)) {
      $this->appMimeTypeMappings = [];
      return $this->appMimeTypeMappings;
    }

    $this->appMimeTypeMappings = array_filter($arrayData, fn(string $key) => !str_starts_with($key, '_'), ARRAY_FILTER_USE_KEY);
    // $this->logInfo('MIME MAPPINGS ' . print_r($this->appMimeTypeMappings, true));

    return $this->appMimeTypeMappings;
  }
}
