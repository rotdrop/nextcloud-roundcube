<?php
/**
 * Nextcloud RoundCube App.
 *
 * @author Claus-Justus Heine
 * @copyright 2020, 2021 Claus-Justus Heine <himself@claus-justus-heine.de>
 *
 * Nextcloud RoundCube App is free software: you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * Nextcloud RoundCube App is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with Nextcloud RoundCube App. If not, see
 * <http://www.gnu.org/licenses/>.
 */

namespace OCA\RoundCube\Traits;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

trait ResponseTrait
{

  private function exceptionResponse(\Throwable $throwable, string $renderAs, string $method = null)
  {
    if (empty($method)) {
      $method = __METHOD__;
    }
    $this->logException($throwable, $method);
    if ($renderAs == 'blank') {
      return self::grumble($this->exceptionChainData($throwable));
    }

    $templateParameters = [
      'error' => 'exception',
      'exception' => $throwable->getMessage(),
      'trace' => $this->exceptionChainData($throwable),
      'debug' => true,
      'admin' => 'bofh@nowhere.com',
    ];

    return new TemplateResponse($this->appName, 'errorpage', $templateParameters, $renderAs);
  }

  private function exceptionChainData(\Throwable $throwable, bool $top = true)
  {
    $previous = $throwable->getPrevious();
    return [
      'message' => ($top
                    ? $this->l->t('Error, caught an exception')
                    : $this->l->t('Caused by previous exception')),
      'exception' => $throwable->getFile().':'.$throwable->getLine().' '.$throwable->getMessage(),
      'trace' => $throwable->getTraceAsString(),
      'previous' => empty($previous) ? null : $this->exceptionChainData($previous, false),
    ];
  }

  static private function dataResponse($data, $status = Http::STATUS_OK)
  {
    return new DataResponse($data, $status);
  }

  static private function valueResponse($value, $message = '', $status = Http::STATUS_OK)
  {
    return self::dataResponse(['message' => $message, 'value' => $value], $status);
  }

  static private function response($message, $status = Http::STATUS_OK)
  {
    return self::dataResponse(['message' => $message], $status);
  }

  static private function grumble($message, $value = null, $status = Http::STATUS_BAD_REQUEST)
  {
    $trace = debug_backtrace();
    $caller = array_shift($trace);
    $data = [
      'class' => __CLASS__,
      'file' => $caller['file'],
      'line' => $caller['line'],
      'value' => $value,
    ];
    if (is_array($message)) {
      $data = array_merge($data, $message);
    } else {
      $data['message'] = $message;
    }
    return self::dataResponse($data, $status);
  }

}

// Local Variables: ***
// c-basic-offset: 2 ***
// indent-tabs-mode: nil ***
// End: ***
