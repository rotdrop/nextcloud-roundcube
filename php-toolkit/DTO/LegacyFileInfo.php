<?php
/**
 * Orchestra member, musician and project management application.
 *
 * CAFEVDB -- Camerata Academica Freiburg e.V. DataBase.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022-2026 Claus-Justus Heine
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

namespace OCA\RotDrop\Toolkit\DTO;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;
use OCA\RotDrop\DevScripts\PhpToTypeScript;

use OCP\Files\FileInfo;

/**
 * Borrowed and enhanced from OC\Files\Template\TemplateManager.php from one
 * ancient Nextcloud version.
 */
#[TSAttributes\TypeScriptTransformer(PhpToTypeScript\DatabaseEntityTransformer::class)]
#[TSAttributes\TemplateParameters(
  "FileType extends '" . FileInfo::TYPE_FILE . "'|'" . FileInfo::TYPE_FOLDER . "'"
    . " = "
    . "'" . FileInfo::TYPE_FILE . "'|'" . FileInfo::TYPE_FOLDER . "'"
)]
class LegacyFileInfo extends AbstractDTO
{
  public const TYPE_FILE = FileInfo::TYPE_FILE;
  public const TYPE_FOLDER = FileInfo::TYPE_FOLDER;

  /** {@inheritdoc} */
  public function __construct(
    public readonly string $fileid,
    public readonly string $path,
    public readonly ?string $topLevelFolder,
    public readonly ?string $relativePath,
    public readonly string $basename,
    public readonly int $lastmod,
    public readonly string $mime,
    public readonly int $size,
    #[TSAttributes\LiteralTypeScriptType('FileType')]
    public readonly string $type,
    public readonly bool $hasPreview,
    public readonly int $permissions,
    #[PhpToTypeScript\TypeScriptPropertyName(propertyName: "'mount-type'")]
    public readonly string $mountType,
    public readonly string $etag,
  ) {
  }

  /** {@inheritdoc} */
  public function jsonSerialize(): mixed
  {
    $result = parent::jsonSerialize();
    $result['mount-type'] = $result['mountType'];
    unset($result['mountType']);
    return $result;
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
      fileid: $fileid,
      path: $path,
      topLevelFolder: $topLevelFolder ?? null,
      relativePath: $relativePath ?? null,
      basename: $basename,
      lastmod: $lastmod,
      mime: $mime,
      size: $size,
      type: $type,
      hasPreview: $hasPreview,
      permissions: $permissions,
      mountType: $mountType,
      etag: $etag,
    );
  }
}
