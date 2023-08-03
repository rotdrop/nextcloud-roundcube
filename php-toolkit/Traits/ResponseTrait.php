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

use ReflectionClass;
use Throwable;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;

/**
 * Utility class to ease constructing HTTP responses.
 *
 * The consuming class has to define a translitate() method compatible to the
 * one defined in the UtilTrait.
 *
 * @see UtilTrait::transliterate()
 */
trait ResponseTrait
{
  /**
   * @param string $data Data-blob.
   *
   * @param string $fileName Proposed download filename.
   *
   * @param string $contentType MIME-type of content.
   *
   * @return Http\DataDownloadResponse
   */
  private function dataDownloadResponse(
    string $data,
    string $fileName,
    string $contentType,
  ):Http\DataDownloadResponse {
    $response = new Http\DataDownloadResponse($data, $fileName, $contentType);
    $response->addHeader(
      'Content-Disposition',
      'attachment; '
      . 'filename="' . $this->transliterate($fileName) . '"; '
      . 'filename*=UTF-8\'\'' . rawurlencode($fileName));

    return $response;
  }

  /**
   * Return an HTML error page, populated with the exception data. This only
   * works if a traditional "errorpage.php" file exists in the templates/
   * directory.
   *
   * @param Throwable $throwable
   *
   * @param string $renderAs
   *
   * @param null|string $method
   *
   * @return TemplateResponse
   */
  private function exceptionResponse(
    Throwable $throwable,
    string $renderAs,
    ?string $method = null,
  ):Response {
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
      'code' => $throwable->getCode(),
      'trace' => $this->exceptionChainData($throwable),
      'debug' => true,
      'admin' => 'bofh@nowhere.com',
    ];

    return new TemplateResponse($this->appName, 'errorpage', $templateParameters, $renderAs);
  }

  /**
   * Convert a potentially nested exception into a nested array to ease
   * post-processing.
   *
   * @param Throwable $throwable
   *
   * @param bool $top If \true then this is the top-level invocation.
   *
   * @return array
   */
  private function exceptionChainData(Throwable $throwable, bool $top = true):array
  {
    $previous = $throwable->getPrevious();
    $shortException = (new ReflectionClass($throwable))->getShortName();
    return [
      'message' => ($top
                    ? $this->l->t('Error, caught an exception.')
                    : $this->l->t('Caused by previous exception')),
      'exception' => $throwable->getFile().':'.$throwable->getLine().' '.$shortException.': '.$throwable->getMessage(),
      'code' => $throwable->getCode(),
      'trace' => $throwable->getTraceAsString(),
      'previous' => empty($previous) ? null : $this->exceptionChainData($previous, false),
    ];
  }

  /**
   * @param array $data
   *
   * @param int $status Default is Http::STATUS_OK.
   *
   * @return DataResponse
   */
  private static function dataResponse(array $data, int $status = Http::STATUS_OK):DataResponse
  {
    $response = new DataResponse($data, $status);
    $policy = $response->getContentSecurityPolicy();
    $policy->addAllowedFrameAncestorDomain("'self'");
    return $response;
  }

  /**
   * @param mixed $value
   *
   * @param null|string $message
   *
   * @param int $status Default is Http::STATUS_OK.
   *
   * @return DataResponse
   *
   * @see dataResponse()
   *
   * @todo Remove message/messages duplication.
   */
  private static function valueResponse(
    mixed $value,
    ?string $message = '',
    int $status = Http::STATUS_OK,
  ):DataResponse {
    return self::dataResponse(
      [
        'messages' => [ $message ],
        'message' => $message,
        'value' => $value,
      ],
      $status
    );
  }

  /**
   * @param mixed $message
   *
   * @param int $status Default is Http::STATUS_OK.
   *
   * @return DataResponse
   *
   * @see dataResponse()
   */
  private static function response(mixed $message, int $status = Http::STATUS_OK):DataResponse
  {
    $responseData = [
      'messages' => [],
      'message' => null,
    ];
    if (is_string($message)) {
      $responseData = [
        'messages' => [ $message ],
        'message' => $message,
      ];
    } elseif (is_array($message)) {
      $responseData = [
        'messages' => $message,
        'message' => implode('; ', $message),
      ];
    }
    return self::dataResponse($responseData, $status);
  }

  /**
   * @param null|string|array $message
   *
   * @param mixed $value
   *
   * @param int $status Default is Http::STATUS_BAD_REQUEST.
   *
   * @return DataResponse
   *
   * @see dataResponse()
   *
   * @todo Remove message/messages duplication.
   */
  private static function grumble(
    mixed $message,
    mixed $value = null,
    int $status = Http::STATUS_BAD_REQUEST,
  ):DataResponse {
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
      $data['messages'] = [ $message ];
      $data['message'] = $message;
    }
    return self::dataResponse($data, $status);
  }

  /**
   * @param DataResponse $response
   *
   * @return array
   *
   * @todo Remove message/messages duplication.
   */
  private static function getResponseMessages(DataResponse $response):array
  {
    $messages = [];
    $data = $response->getData();
    foreach (['message', 'messages'] as $key) {
      $messageData = $data[$key] ?? [];
      if (!is_array($messageData)) {
        $messageData = [ $messageData ];
      }
      $messages = array_merge($messages, $messageData);
    }
    return $messages;
  }
}
