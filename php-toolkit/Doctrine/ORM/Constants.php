<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022-2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Doctrine\ORM;

use Spatie\TypeScriptTransformer\Attributes as TSAttributes;

/**
 * Provide some constants for options understood by the FindLikeTrait.
 */
#[TSAttributes\TypeScript]
class Constants
{
    /**
   * @var string
   *
   * The very first array element to findBy() as defined in
   * \OCA\CAFEVDB\Database\Doctrine\ORM\Traits\FindLikeTrait may contain
   * options if it is strictly equal to this value.
   */
  public const QUERY_OPTIONS_KEY = EntityRepository::QUERY_OPTIONS_KEY;
  public const QUERY_OPTION_WILDCARDS = EntityRepository::QUERY_OPTION_WILDCARDS;
  public const WILDCARD_QUERY_OPTIONS = EntityRepository::WILDCARD_QUERY_OPTIONS;

}
