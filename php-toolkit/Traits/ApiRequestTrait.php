<?php
/**
 * A collection of reusable traits classes for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2023 Claus-Justus Heine <himself@claus-justus-heine.de>
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

use OCP\IRequest;
use Psr\Log\LogLevel;

/**
 * Trait with some helpers to deal with non-interactive requests. Some hacks
 * are just not needed for non-interactive request, like SSO etc.
 */
trait ApiRequestTrait
{
  use LoggerTrait;

  /**
   * In order to avoid request ping-pong the auto-login should only be
   * attempted for UI logins.
   *
   * @param IRequest $request
   *
   * @param string $logLevel
   *
   * @return bool
   */
  private function isNonInteractiveRequest(IRequest $request, string $logLevel = LogLevel::DEBUG):bool
  {
    $method = $request->getMethod();
    if ($method != 'GET' && $method != 'POST') {
      $this->log($logLevel, 'Ignoring request with method '.$method);
      return true;
    }
    if ($request->getHeader('OCS-APIREQUEST') === 'true') {
      $this->log($logLevel, 'Ignoring API login');
      return true;
    }
    if (strpos($request->getHeader('Authorization'), 'Bearer ') === 0) {
      $this->log($logLevel, 'Ignoring API "bearer" auth');
      return true;
    }
    return false;
  }
}
