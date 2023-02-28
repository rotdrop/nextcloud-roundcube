<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

/**
 * Pattern matching with include and exclude patterns and include/exclude
 * precedence for conflict resolution.
 */
trait IncludeExcludeTrait
{
  /**
   * Determine if $subject should be included. Empty patterns match any
   * string.
   *
   * @param string $subject
   *
   * @param null|string $includePattern
   *
   * @param null|string $excludePattern
   *
   * @param bool $includeHasPrecedence Conflict resolution. If \true then a
   * matching include pattern will override any also matching exclude
   * patterns. If \false a matching exclude pattern will override any matching
   * include patterns. Some special cases:
   * - $includeHasPrecedence == \true, $includePattern is empty. All files will be included.
   * - $includeHasPrecedence == \false, $excludePattern is empty. No file will be included.
   *
   * @return bool
   */
  protected function isIncluded(
    string $subject,
    ?string $includePattern,
    ?string $excludePattern,
    bool $includeHasPrecedence,
  ):bool {
    $includeMatches = empty($includePattern) || preg_match($includePattern, $subject);
    $excludeMatches = empty($excludePattern) || preg_match($excludePattern, $subject);

    if ($includeHasPrecedence) {
      return $includeMatches || !$excludeMatches;
    } else {
      return $includeMatches && !$excludeMatches;
    }
  }
}
