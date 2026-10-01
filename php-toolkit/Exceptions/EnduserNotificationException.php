<?php
/**
 * Some PHP utility functions for Nextcloud apps.
 *
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 * @copyright 2022, 2026 Claus-Justus Heine <himself@claus-justus-heine.de>
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

namespace OCA\RotDrop\Toolkit\Exceptions;

use OCP\AppFramework\Http;

/**
 * This exception should provide an error message which informs an
 * end-user about an error.
 *
 * The intended use is the finally have an error template which scans
 * the chain of thrown exceptions searching for
 * EnduserNotificationExceptions and display their error text only.
 */
class EnduserNotificationException extends Exception
{
  // phpcs:disable Squiz.Commenting.FunctionComment.Missing
  public function __construct(
    string $message,
    int $code = 0,
    $previous = null,
    protected int $httpStatusCode = Http::STATUS_BAD_REQUEST,
    protected ?array $context = null,
  ) {
    parent::__construct($message, $code, $previous);
  }
  // phpcs:enable


  /**
   * @param int $code
   *
   * @return EnduserNotificationException
   */
  public function setHttpStatusCode(int $code):EnduserNotificationException
  {
    $this->httpStatusCode = $code;
    return $this;
  }

  /** @return int */
  public function getHttpStatusCode():int
  {
    return $this->httpStatusCode;
  }

  /**
   * @param null|array $context
   *
   * @return EnduserNotificationException
   */
  public function setContext(?array $context):EnduserNotificationException
  {
    $this->context = $context;
    return $this;
  }

  /** @return null|array */
  public function getContext():?array
  {
    return $this->context;
  }
}
