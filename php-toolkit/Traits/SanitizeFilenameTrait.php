<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2025 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use Throwable;

use OCP\AppFramework\IAppContainer;
use OC\Files\FilenameValidator;

use OCA\RotDrop\Toolkit\Exceptions;

/**
 * Cloned from OCP\Files\Command\SanitzeFilenames and changed a bit.
 */
trait SanitizeFilenameTrait
{
  use FakeTranslationTrait;
  use LoggerTrait;

  protected IAppContainer $appContainer;

  /**
   * Remove "forbidden" characters as configured in order to achieve a
   * filename which is also valid on certain strange operating systems.
   *
   * @param string $name
   *
   * @param null|string $mimeType
   *
   * @return string
   *
   * @throws Exceptions\EnduserNotificationException
   */
  protected function sanitizeFilename(string $name, ?string $mimeType = null): string
  {
    if ($name === '') {
      return $name; // root folder
    }
    $oldName = $name;
    try {
      /** @var FilenameValidator $filenameValidator */
      $filenameValidator = $this->appContainer->get(FilenameValidator::class);

      $forbiddenCharacters = $filenameValidator->getForbiddenCharacters();
      $charReplacement = array_diff(['_', ' ', '-'], $forbiddenCharacters);
      $charReplacement = reset($charReplacement) ?: '';

      $forbiddenExtensions = $filenameValidator->getForbiddenExtensions();
      foreach ($forbiddenExtensions as $extension) {
        if (str_ends_with($name, $extension)) {
          $name = substr($name, 0, strlen($name) - strlen($extension));
          $extension = $this->fileExtensionFromMimeType($mimeType);
          if ($extension && !in_array($extension, $forbiddenExtensions)) {
            $name .= '.' . $extension;
          }
        }
      }

      $basename = substr($name, 0, strpos($name, '.', 1) ?: null);
      if (in_array($basename, $filenameValidator->getForbiddenBasenames())) {
        $name = str_replace($basename, $this->l->t('%1$s (renamed)', [$basename]), $name);
      }

      if ($name === '') {
        $name = $this->l->t('renamed file');
      }

      $forbiddenCharacter = $filenameValidator->getForbiddenCharacters();
      $name = str_replace($forbiddenCharacter, $charReplacement, $name);
    } catch (Throwable $t) {
      throw new Exceptions\EnduserNotificationException(
        self::t('Unable to sanitize filename "%s".', $oldName),
        previous: $t,
      );
    }
    return $name;
  }

  protected const FILE_EXTENSIONS_BY_MIME_TYPE = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/gif' => 'gif',
    'image/vnd.microsoft.icon' => 'ico',
    'image/svg+xml' => 'svg',
    'application/pdf' => 'pdf',
  ];

  /**
   * @param null|string $mimeType
   *
   * @return null|string
   */
  protected function fileExtensionFromMimeType(?string $mimeType):?string
  {
    if (!$mimeType) {
      return null;
    }
    if (!empty(self::FILE_EXTENSIONS_BY_MIME_TYPE[$mimeType])) {
      return self::FILE_EXTENSIONS_BY_MIME_TYPE[$mimeType];
    }
    // as a wild guess we return anything after the slash if it is at
    // most 4 characters.
    list(/* $first*/, $second) = explode('/', $mimeType);
    if (strlen($second) <= 4) {
      return $second;
    }
    return null;
  }
}
