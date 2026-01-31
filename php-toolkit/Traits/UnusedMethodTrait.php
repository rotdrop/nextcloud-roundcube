<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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
declare(strict_types=1);

namespace OCA\RotDrop\Toolkit\Traits;

use BadMethodCallException;

/**
 * Define an unused-on-purpose method e.g. to silence PHPUnit "No expectations
 * configured" warnings.
 */
trait UnusedMethodTrait
{
  public const UNUSED_METHOD_NOT_TO_BE_CALLED_NAME = 'unusedMethodNotToBeCalled';

  /**
   * E.g. for use in \PHPUnit\Framework\TestCase::expects().
   *
   * @return void
   */
  public function unusedMethodNotToBeCalled(): void
  {
    throw new BadMethodCallException('This method must not be called');
  }
}
