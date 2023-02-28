<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author    Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022 Claus-Justus Heine
 * @license   AGPL-3.0-or-later
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

/** A couple of constants in order to avoid string literals. */
class Constants
{
  /**
   * @var string
   *
   * File-path separator.
   */
  public const PATH_SEPARATOR = '/';
  public const DEFAULT_ADMIN_ARCHIVE_SIZE_LIMIT = (1 << 32);
  public const USER_FOLDER_PREFIX = 'files';
  public const JS = 'js';
  public const CSS = 'css';
  public const ASSET = 'asset';
  public const HASH = 'hash';
  public const WEB_ASSET_META = self::JS . self::PATH_SEPARATOR . 'asset-meta.json';
  public const INFO_FILE = 'appinfo' . self::PATH_SEPARATOR . 'info.xml';
}
