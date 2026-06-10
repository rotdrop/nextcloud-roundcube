<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023, 2025, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use \OCP\IL10N;

/**
 * Supply a dummy t() function in order to inject strings into the translation
 * machinery.
 *
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
trait FakeTranslationTrait
{
  /**
   * @param string $text
   *
   * @param string|array $parameters
   *
   * @return string
   */
  protected static function t(string $text, string|array $parameters = [], ?IL10N $l = null):string
  {
    if (!is_array($parameters)) {
      $parameters = [ $parameters ];
    }
    return $l
      ? $l->t($text, $parameters)
      : (empty($parameters) ? $text : vsprintf($text, $parameters));
  }
}
